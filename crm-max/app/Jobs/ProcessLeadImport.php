<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\LeadImport;
use App\Models\Note;
use App\Models\User;
use App\Services\EmailService;
use App\Services\LeadMax\LeadAssigner;
use App\Services\LeadMax\LeadCapture;
use App\Services\PlanLimitService;
use App\Support\LeadMax\ActivityContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessLeadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public const MAX_ROWS = 50000;

    public const MAX_ERRORS = 500;

    /** Accepted header spellings for each lead field (lower-case, spaces/underscores normalised). */
    public const ALIASES = [
        'first_name' => ['first name', 'firstname', 'first', 'given name', 'name'],
        'last_name' => ['last name', 'lastname', 'last', 'surname', 'family name'],
        'email' => ['email', 'e mail', 'email address', 'mail'],
        'phone' => ['phone', 'phone number', 'mobile', 'mobile number', 'telephone', 'tel', 'cell'],
        'country' => ['country', 'country name', 'nation'],
        'source' => ['source', 'lead source', 'traffic source'],
        'campaign' => ['campaign', 'campaign name', 'utm campaign'],
        'affiliate' => ['affiliate', 'affiliate code', 'affiliate email', 'partner'],
        'notes' => ['notes', 'note', 'comment', 'comments', 'description'],
    ];

    public const REQUIRED = ['first_name', 'last_name', 'email', 'phone'];

    public function __construct(public int $importId, public int $companyId) {}

    public function handle(LeadAssigner $assigner): void
    {
        $company = Company::withoutGlobalScopes()->findOrFail($this->companyId);
        app()->instance('company', $company);
        $import = LeadImport::query()->findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        try {
            $rows = self::readRows(Storage::disk('local')->path($import->path));
        } catch (\Throwable $exception) {
            $import->update(['status' => 'failed', 'message' => 'The file could not be read: '.Str::limit($exception->getMessage(), 200), 'finished_at' => now()]);

            return;
        }
        $header = array_shift($rows) ?? [];
        $map = self::mapHeader($header, $import->options['mapping'] ?? []);
        $missing = array_values(array_diff(self::REQUIRED, array_keys($map)));
        if ($missing) {
            $import->update(['status' => 'failed', 'total_rows' => count($rows), 'failed' => count($rows), 'message' => 'Missing required columns: '.implode(', ', array_map(fn ($f) => ucwords(str_replace('_', ' ', $f)), $missing)).'.', 'finished_at' => now()]);

            return;
        }
        $rows = array_values(array_filter($rows, fn ($row) => collect($row)->filter(fn ($cell) => trim((string) $cell) !== '')->isNotEmpty()));
        if (count($rows) > self::MAX_ROWS) {
            $import->update(['status' => 'failed', 'total_rows' => count($rows), 'failed' => count($rows), 'message' => 'The file has more than '.number_format(self::MAX_ROWS).' rows.', 'finished_at' => now()]);

            return;
        }

        $options = $import->options ?? [];
        $channel = ($options['via'] ?? null) === 'google_drive'
            ? 'google_drive'
            : (in_array(strtolower(pathinfo((string) $import->file_name, PATHINFO_EXTENSION)), ['xls', 'xlsx'], true) ? 'xlsx' : 'csv');
        $uploader = $import->uploaded_by ? User::withoutGlobalScopes()->find($import->uploaded_by) : null;
        $remaining = PlanLimitService::for($company)->remaining('leads_max', User::query()->whereHasRole('lead')->count());
        $affiliates = User::query()->whereHasRole('affiliate')->get(['id', 'account_id', 'email', 'first_name', 'last_name']);
        $seen = [];
        $errors = [];
        $counts = ['imported' => 0, 'updated' => 0, 'failed' => 0];
        // Imported leads get a password nobody knows. One random hash serves the whole file: bcrypt
        // costs 50-100 ms, so hashing per row made a large import spend minutes on it alone.
        $unusablePassword = bcrypt(Str::random(32));
        $fail = function (int $line, string $reason) use (&$errors, &$counts) {
            $counts['failed']++;
            if (count($errors) < self::MAX_ERRORS) {
                $errors[] = ['row' => $line, 'reason' => $reason];
            }
        };

        foreach ($rows as $index => $row) {
            $line = $index + 2; // spreadsheet line, header is line 1
            $data = collect($map)->map(fn ($column) => trim((string) ($row[$column] ?? '')))->all();
            if (isset($map['first_name']) && ! isset($map['last_name']) && str_contains($data['first_name'], ' ')) {
                [$data['first_name'], $data['last_name']] = explode(' ', $data['first_name'], 2);
            }
            $email = strtolower($data['email'] ?? '');
            foreach (self::REQUIRED as $field) {
                if (($data[$field] ?? '') === '') {
                    $fail($line, ucwords(str_replace('_', ' ', $field)).' is missing');
                    continue 2;
                }
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fail($line, 'Invalid email address');
                continue;
            }
            if (! preg_match('/^[+0-9() .\-]{5,40}$/', $data['phone'])) {
                $fail($line, 'Invalid phone number');
                continue;
            }
            if (isset($seen[$email])) {
                $fail($line, 'Duplicate of row '.$seen[$email].' in this file');
                continue;
            }
            $seen[$email] = $line;
            $fields = array_filter([
                'first_name' => Str::limit($data['first_name'], 100, ''),
                'last_name' => Str::limit($data['last_name'], 100, ''),
                'phone' => $data['phone'],
                'country' => ($data['country'] ?? '') !== '' ? $data['country'] : (($options['country'] ?? 'auto') !== 'auto' ? $options['country'] : null),
                'source' => ($data['source'] ?? '') !== '' ? $data['source'] : ($options['source'] ?? null),
                'campaign' => ($data['campaign'] ?? '') !== '' ? $data['campaign'] : ($options['campaign'] ?? null),
            ], fn ($value) => $value !== null && $value !== '');
            if (($data['affiliate'] ?? '') !== '') {
                $needle = strtolower($data['affiliate']);
                $affiliate = $affiliates->first(fn ($a) => strtolower((string) $a->account_id) === $needle || strtolower((string) $a->email) === $needle || strtolower($a->name) === $needle);
                if ($affiliate) {
                    $fields['created_by'] = $affiliate->id;
                }
            }

            $existing = User::query()->where('email', $email)->first();
            if ($existing) {
                if (! ($options['update_existing'] ?? false) || ! $existing->hasRole('lead')) {
                    $fail($line, $existing->hasRole('lead') ? 'Lead with this email already exists' : 'Email belongs to an existing client or staff member');
                    continue;
                }
                $existing->update($fields);
                ActivityLog::create(['user_id' => $existing->id, 'type' => 'lead_updated', 'title' => 'Lead updated by import', 'info' => ['actor' => $uploader?->name, 'actor_role' => 'System', 'initiator_id' => $uploader?->id, 'initiator_role' => ActivityContext::roleLabel($uploader), 'description' => $import->file_name]]);
                $counts['updated']++;
                continue;
            }
            if ($remaining !== null && $remaining <= 0) {
                $fail($line, 'Plan lead limit reached');
                continue;
            }

            $decision = match ($options['assign'] ?? 'auto') {
                'auto' => $assigner->decide($fields),
                'none' => ['agent' => null, 'rule_id' => null, 'method' => null],
                default => ['agent' => $options['assign'], 'rule_id' => null, 'method' => 'manual'],
            };
            $owner = $decision['agent'];
            // One row is one unit: the lead, its assignment log, activity and attribution
            // are kept or rolled back together.
            $lead = DB::transaction(function () use ($fields, $email, $unusablePassword, $owner, $assigner, $decision, $uploader, $import, $channel, $line) {
                $lead = User::create([...$fields, 'email' => $email, 'manager_id' => $owner, 'status' => 'new', 'password' => $unusablePassword, 'can_email' => false, 'can_trade' => false, 'approved' => false]);
                $lead->addRole('lead');
                $assigner->log($lead, $decision, 'import', $uploader?->id);
                ActivityLog::create(['user_id' => $lead->id, 'type' => 'lead_created', 'title' => 'Lead imported', 'info' => ['actor' => $uploader?->name, 'actor_role' => 'System', 'initiator_id' => $uploader?->id, 'initiator_role' => ActivityContext::roleLabel($uploader), 'description' => $import->file_name]]);
                app(LeadCapture::class)->record($lead, $channel, ['actor_id' => $uploader?->id, 'assignment_method' => $owner ? $decision['method'] : null, 'import_id' => $import->id, 'import_row' => $line]);

                return $lead;
            });
            if (($data['notes'] ?? '') !== '') {
                Note::create(['user_id' => $lead->id, 'by_id' => $uploader?->id, 'note' => Str::limit($data['notes'], 10000, '')]);
            }
            if ($options['follow_up'] ?? false) {
                ActivityLog::create(['user_id' => $lead->id, 'type' => 'reminder_created', 'title' => 'Reminder created', 'info' => ['actor' => $uploader?->name, 'subject' => 'Follow-up call', 'reminder_date' => now()->addDay()->toDateString(), 'reminder_time' => '10:00', 'priority' => 'medium', 'description' => 'Added by lead import']]);
            }
            if ($options['welcome_email'] ?? false) {
                try {
                    app(EmailService::class)->sendWelcomeEmail($lead);
                } catch (\Throwable) {
                    // A failed welcome email must not fail the import.
                }
            }
            $remaining = $remaining === null ? null : $remaining - 1;
            $counts['imported']++;
        }

        $done = $counts['imported'] + $counts['updated'];
        $import->update([
            ...$counts,
            'total_rows' => count($rows),
            'errors' => $errors,
            'status' => $counts['failed'] === 0 ? 'completed' : ($done > 0 ? 'partial' : 'failed'),
            'finished_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        LeadImport::withoutGlobalScopes()->whereKey($this->importId)->update(['status' => 'failed', 'message' => Str::limit($exception->getMessage(), 250), 'finished_at' => now()]);
    }

    /** @return array<int, array<int, mixed>> */
    public static function readRows(string $path): array
    {
        if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            return self::readCsv($path);
        }
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    /** Plain CSV parsing keeps every cell a string, so phone numbers keep their "+" and leading zeros. */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        $delimiter = collect([',', ';', "\t", '|'])->sortByDesc(fn ($d) => substr_count($first, $d))->first();
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            if ($rows === [] && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /** field => column index, using a saved mapping first and header aliases second. */
    public static function mapHeader(array $header, array $mapping = []): array
    {
        $normalise = fn ($h) => trim(preg_replace('/[\s_\-]+/', ' ', strtolower((string) $h)));
        $normalised = array_map($normalise, $header);
        $map = [];
        foreach (self::ALIASES as $field => $aliases) {
            $wanted = array_filter([$normalise($mapping[$field] ?? ''), ...$aliases]);
            foreach ($wanted as $alias) {
                $column = array_search($alias, $normalised, true);
                if ($column !== false && ! in_array($column, $map, true)) {
                    $map[$field] = $column;
                    break;
                }
            }
        }

        return $map;
    }
}

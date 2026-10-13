<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessLeadImport;
use App\Models\Company;
use App\Models\Country;
use App\Models\LeadImport;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\GoogleDrive\GoogleDriveNotConnected;
use App\Services\GoogleDrive\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Upload Leads (Figma 2078:14630). Files are processed by ProcessLeadImport on the queue. */
class LeadMaxImportController extends LeadMaxController
{
    public const TABS = ['upload', 'history', 'mapping', 'settings'];

    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public const TEMPLATE_HEADER = ['First Name', 'Last Name', 'Email', 'Phone', 'Country', 'Source', 'Campaign', 'Affiliate', 'Notes'];

    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'upload';
        $now = now();
        $month = fn (Carbon $start) => LeadImport::query()->whereBetween('created_at', [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(total_rows),0) as rows_total, COALESCE(SUM(imported + updated),0) as ok_total, COALESCE(SUM(failed),0) as failed_total')->first();
        $current = $month($now);
        $previous = $month($now->copy()->subMonthNoOverflow());
        $change = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;
        $defaults = $this->defaults();

        return view('admin.leadmax.upload-leads', [
            ...$this->common($request, 'upload-leads'),
            // Drive is additive: the button only appears once a company has linked an account.
            'driveConnected' => app(GoogleDriveService::class)->isConnected(app('company')->id),
            'tab' => $tab,
            'kpi' => [
                'total' => (int) $current->rows_total, 'totalChange' => $change($current->rows_total, $previous->rows_total), 'totalPrevious' => (int) $previous->rows_total,
                'imported' => (int) $current->ok_total, 'importedChange' => $change($current->ok_total, $previous->ok_total),
                'failed' => (int) $current->failed_total, 'failedChange' => $change($current->failed_total, $previous->failed_total),
                'successRate' => $current->rows_total > 0 ? round($current->ok_total / $current->rows_total * 100, 1) : null,
            ],
            'lastUpload' => LeadImport::query()->with('uploader')->latest()->first(),
            'uploads' => LeadImport::query()->with('uploader')->latest()->latest('id')->paginate($this->perPage($request, $tab))->withQueryString(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'details' => $request->filled('import') ? LeadImport::query()->with('uploader')->find($request->input('import')) : null,
            'defaults' => $defaults,
            'mapping' => company_settings('leadmax_import_mapping', []) ?: [],
            'agents' => User::query()->whereHasRole('manager')->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'countries' => Country::query()->orderBy('name')->pluck('name'),
            'sources' => LeadSource::query()->pluck('name')->merge($this->leadQuery()->whereNotNull('source')->where('source', '!=', '')->distinct()->pluck('source'))->unique()->sort()->values(),
            'campaigns' => $this->leadQuery()->whereNotNull('campaign')->where('campaign', '!=', '')->distinct()->orderBy('campaign')->pluck('campaign'),
        ]);
    }

    public function store(Request $request)
    {
        $import = $this->createImport($request);
        ProcessLeadImport::dispatch($import->id, app('company')->id);

        return redirect()->route('admin.leadmax.upload-leads', ['import' => $import->id])->with('success', $import->file_name.' uploaded. Import is processing.');
    }

    /**
     * Import a file the admin picked out of Google Drive.
     *
     * Downloads it server-side into the same leadmax/imports/{company} path the uploader uses,
     * then hands over to the identical pipeline — same validation of the options, same
     * LeadImport record, same queued ProcessLeadImport. Nothing downstream knows or cares that
     * the bytes came from Drive.
     *
     * A Google Sheet is exported as CSV rather than downloaded, since native Google formats
     * have no bytes to fetch; GoogleDriveService handles that.
     */
    public function fromDrive(Request $request, GoogleDriveService $drive)
    {
        $request->validate(['file_id' => ['required', 'string', 'max:255']]);

        try {
            $file = $drive->downloadToDisk(
                companyId: app('company')->id,
                fileId: $request->input('file_id'),
                directory: 'leadmax/imports/'.app('company')->id,
                maxBytes: (int) config('services.google.max_import_bytes', 20 * 1024 * 1024),
                disk: 'local',
                // Mirrors the uploader's mimes rule so Drive is never the laxer way in.
                allowedExtensions: ['csv', 'txt', 'xls', 'xlsx'],
            );
        } catch (GoogleDriveNotConnected $e) {
            return back()->with('warning', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('warning', $e->getMessage());
        }

        try {
            $import = $this->createImport($request, ['path' => $file['path'], 'name' => $file['name']]);
        } catch (\Throwable $e) {
            // Validation of the surrounding options failed: do not leave the downloaded file behind.
            Storage::disk('local')->delete($file['path']);
            throw $e;
        }

        ProcessLeadImport::dispatch($import->id, app('company')->id);

        return redirect()->route('admin.leadmax.upload-leads', ['import' => $import->id])
            ->with('success', $import->file_name.' imported from Google Drive. Import is processing.');
    }

    /**
     * CSV import from the Leads page (its Import dialog). Runs straight away so the dialog can report the
     * result, and does not depend on the Upload Leads page being switched on (Settings > Modules).
     */
    public function fromLeads(Request $request, GoogleDriveService $drive)
    {
        // The dialog offers an upload or a Google Drive pick. Everything after the file is in
        // place is identical, so the Drive branch only has to produce a path on the local disk.
        $prestored = null;

        if ($request->filled('file_id')) {
            $request->validate(['file_id' => ['string', 'max:255']]);

            try {
                $file = $drive->downloadToDisk(
                    companyId: app('company')->id,
                    fileId: $request->input('file_id'),
                    directory: 'leadmax/imports/'.app('company')->id,
                    maxBytes: (int) config('services.google.max_import_bytes', 20 * 1024 * 1024),
                    disk: 'local',
                    allowedExtensions: ['csv', 'txt', 'xls', 'xlsx'],
                );
            } catch (\Throwable $e) {
                // 422 so the dialog shows the reason inline, as it does for a bad file.
                abort(422, $e->getMessage());
            }

            $prestored = ['path' => $file['path'], 'name' => $file['name']];
        }

        try {
            $import = $this->createImport($request, $prestored);
        } catch (\Throwable $e) {
            if ($prestored) {
                Storage::disk('local')->delete($prestored['path']);
            }
            throw $e;
        }
        // Run the job in this request (not through the queue) so the result can be reported right away.
        app()->call([new ProcessLeadImport($import->id, app('company')->id), 'handle']);
        $import->refresh();
        abort_if($import->status === 'failed', 422, $import->message ?: 'The file could not be imported.');

        $message = number_format((int) $import->imported).' new '.str('lead')->plural((int) $import->imported).' imported'
            .((int) $import->updated ? ', '.number_format((int) $import->updated).' updated' : '')
            .((int) $import->failed ? ', '.number_format((int) $import->failed).' '.str('row')->plural((int) $import->failed).' skipped (see Upload Leads for details)' : '').'.';
        $request->session()->flash('leadmax_import_result', $import->file_name.': '.$message);

        return response()->json(['message' => $message, 'imported' => (int) $import->imported, 'updated' => (int) $import->updated, 'failed' => (int) $import->failed]);
    }

    /**
     * @param  array{path:string,name:string}|null  $prestored  A file already written to the
     *        local disk — used by the Google Drive path, which downloads server-side rather
     *        than receiving an upload. Everything after this point is identical either way.
     */
    private function createImport(Request $request, ?array $prestored = null): LeadImport
    {
        $data = $request->validate([
            ...($prestored ? [] : ['file' => 'required|file|max:10240|mimes:csv,txt,xls,xlsx']),
            'assign' => ['required', 'string', 'max:40'],
            'country' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:150',
            'campaign' => 'nullable|string|max:200',
            'update_existing' => 'nullable|boolean',
            'welcome_email' => 'nullable|boolean',
            'follow_up' => 'nullable|boolean',
        ]);
        $assign = $data['assign'];
        if (! is_admin()) {
            $assign = auth()->id();
        } elseif (! in_array($assign, ['auto', 'none'], true)) {
            abort_unless(User::query()->whereHasRole(['manager', 'agent'])->whereKey($assign)->exists(), 422, 'Choose an agent from this tenant.');
        }
        if ($prestored) {
            $path = $prestored['path'];
            $originalName = $prestored['name'];
        } else {
            $file = $request->file('file');
            $path = $file->storeAs('leadmax/imports/'.app('company')->id, Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'csv'), 'local');
            $originalName = $file->getClientOriginalName();
        }

        $import = LeadImport::create([
            'uploaded_by' => auth()->id(),
            'file_name' => Str::limit($originalName, 250, ''),
            'path' => $path,
            'status' => 'queued',
            'options' => [
                'assign' => $assign,
                'country' => ($data['country'] ?? null) ?: 'auto',
                'source' => ($data['source'] ?? null) ?: null,
                'campaign' => ($data['campaign'] ?? null) ?: null,
                'update_existing' => $request->boolean('update_existing'),
                'welcome_email' => $request->boolean('welcome_email'),
                'follow_up' => $request->boolean('follow_up'),
                'mapping' => company_settings('leadmax_import_mapping', []) ?: [],
                // Pre-stored files come from Google Drive; recorded as the lead's intake channel.
                'via' => $prestored ? 'google_drive' : 'upload',
            ],
        ]);

        return $import;
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::TEMPLATE_HEADER);
            fputcsv($out, ['John', 'Doe', 'john.doe@example.com', '+14165550142', 'Canada', 'Website', 'Brand_CA', '', 'Interested in forex']);
            fclose($out);
        }, 'leadmax-leads-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function errors(string $import)
    {
        $record = LeadImport::query()->findOrFail($import);

        return response()->streamDownload(function () use ($record) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Row', 'Reason']);
            foreach ($record->errors ?? [] as $error) {
                fputcsv($out, [$error['row'], $error['reason']]);
            }
            fclose($out);
        }, pathinfo($record->file_name, PATHINFO_FILENAME).'-errors.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(string $import)
    {
        abort_unless(is_admin(), 403);
        $record = LeadImport::query()->findOrFail($import);
        abort_if(in_array($record->status, ['queued', 'processing'], true), 422, 'Wait for the import to finish before deleting it.');
        Storage::disk('local')->delete($record->path);
        $record->delete();

        return redirect()->route('admin.leadmax.upload-leads', ['tab' => 'history'])->with('success', 'Upload record deleted. Imported leads were kept.');
    }

    public function saveSettings(Request $request)
    {
        abort_unless(is_admin(), 403);
        $fields = array_keys(ProcessLeadImport::ALIASES);
        $data = $request->validate([
            'section' => ['required', Rule::in(['defaults', 'mapping'])],
            'assign' => 'nullable|string|max:40', 'country' => 'nullable|string|max:100', 'source' => 'nullable|string|max:150', 'campaign' => 'nullable|string|max:200',
            'update_existing' => 'nullable|boolean', 'welcome_email' => 'nullable|boolean', 'follow_up' => 'nullable|boolean',
            'mapping' => 'nullable|array', 'mapping.*' => 'nullable|string|max:100',
        ]);
        $company = Company::query()->findOrFail(app('company')->id);
        $settings = $company->settings ?? [];
        if ($data['section'] === 'defaults') {
            $settings['leadmax_import_defaults'] = [
                'assign' => ($data['assign'] ?? null) ?: 'auto', 'country' => ($data['country'] ?? null) ?: 'auto', 'source' => ($data['source'] ?? null) ?: null, 'campaign' => ($data['campaign'] ?? null) ?: null,
                'update_existing' => $request->boolean('update_existing'), 'welcome_email' => $request->boolean('welcome_email'), 'follow_up' => $request->boolean('follow_up'),
            ];
        } else {
            $settings['leadmax_import_mapping'] = collect($data['mapping'] ?? [])->only($fields)->map(fn ($v) => trim((string) $v))->filter()->all();
        }
        $company->forceFill(['settings' => $settings])->save();
        Company::clearCache($company->fresh());
        app()->instance('company', $company->fresh());

        return redirect()->route('admin.leadmax.upload-leads', ['tab' => $data['section'] === 'defaults' ? 'settings' : 'mapping'])->with('success', 'Import settings saved.');
    }

    private function perPage(Request $request, string $tab): int
    {
        $perPage = (int) $request->input('per_page');

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : ($tab === 'history' ? self::PER_PAGE : 10);
    }

    private function defaults(): array
    {
        return [
            'assign' => 'auto', 'country' => 'auto', 'source' => null, 'campaign' => null,
            'update_existing' => true, 'welcome_email' => false, 'follow_up' => true,
            ...(company_settings('leadmax_import_defaults', []) ?: []),
        ];
    }
}

<?php

namespace App\Services\LeadMax;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class LeadsTotalsSeeder
{
    public const CURRENT = ['new' => 552, 'contacted' => 362, 'qualified' => 183, 'converted' => 54, 'disqualified' => 97];

    public const PREVIOUS = ['new' => 470, 'contacted' => 333, 'qualified' => 159, 'converted' => 45, 'disqualified' => 102];

    public function plan(): array
    {
        return ['current_week' => self::CURRENT, 'current_total' => array_sum(self::CURRENT), 'previous_week' => self::PREVIOUS, 'previous_total' => array_sum(self::PREVIOUS), 'decision' => 'Keep the image total1248; correct New to552. Percentages are calculated from real prior-period counts.'];
    }

    public function seed(Company $company): array
    {
        $prior = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);
        try {
            return DB::transaction(function () use ($company) {
                $company = Company::query()->lockForUpdate()->findOrFail($company->id);
                if ($company->setting('leadmax_leads_totals_seed')) {
                    return [...$this->plan(), 'status' => 'already_seeded'];
                }
                if (! $company->setting('leadmax_leads_reference_seed') || $company->setting('leadmax_report_as_of') !== '2025-10-01') {
                    throw new RuntimeException('Seed the reviewed visible lead references first.');
                }
                $current = $this->counts('2025-09-29 00:00:00', '2025-10-05 23:59:59');
                $previous = $this->counts('2025-09-22 00:00:00', '2025-09-28 23:59:59');
                foreach ([[$current, self::CURRENT], [$previous, self::PREVIOUS]] as [$existing, $target]) {
                    foreach ($existing as $status => $count) {
                        if (! array_key_exists($status, $target) || $count > $target[$status]) {
                            throw new RuntimeException('Existing period data exceeds the reviewed target; no records were changed.');
                        }
                    }
                }

                return Model::withoutEvents(function () use ($company, $current, $previous) {
                    $password = Hash::make(Str::random(64));
                    $added = 0;
                    foreach ([['current', $current, self::CURRENT, '2025-09-29 09:00:00', 9000], ['previous', $previous, self::PREVIOUS, '2025-09-23 09:00:00', 7000]] as [$period, $existing, $target, $date, $account]) {
                        foreach ($target as $status => $count) {
                            for ($i = $existing[$status] ?? 0; $i < $count; $i++) {
                                while (User::query()->where('account_id', (string) $account)->exists()) {
                                    $account--;
                                }
                                $key = LeadsReferenceSeeder::MARKER.':'.$company->id.':'.$period.':'.$status.':'.$i;
                                $accountId = (string) $account--;
                                $user = User::query()->forceCreate([
                                    'id' => Uuid::uuid5(Uuid::NAMESPACE_URL, $key)->toString(), 'company_id' => $company->id,
                                    'account_id' => $accountId, 'country' => 'Israel',
                                    'source' => 'Website', 'campaign' => LeadsReferenceSeeder::MARKER.'-totals', 'status' => $status,
                                    'password' => $password, 'can_email' => false, 'can_trade' => false, 'banned' => true, 'approved' => false, 'require_otp' => true,
                                    'balance' => 0, 'total_deposit' => 0, 'created_at' => $date, 'updated_at' => $date,
                                    ...DemoLeadProfiles::make(DemoLeadProfiles::totalsSerial($period, $status, $i), $accountId),
                                ]);
                                $user->addRole('lead');
                                $added++;
                            }
                        }
                    }
                    if ($this->counts('2025-09-29 00:00:00', '2025-10-05 23:59:59') != self::CURRENT || $this->counts('2025-09-22 00:00:00', '2025-09-28 23:59:59') != self::PREVIOUS) {
                        throw new RuntimeException('Seed totals failed verification.');
                    }
                    $company->settings = [...($company->settings ?? []), 'leadmax_leads_totals_seed' => ['current' => self::CURRENT, 'previous' => self::PREVIOUS, 'seeded_at' => now()->toIso8601String()]];
                    $company->saveQuietly();

                    return [...$this->plan(), 'added' => $added, 'status' => 'seeded'];
                });
            });
        } finally {
            if ($prior) {
                app()->instance('company', $prior);
            } else {
                app()->forgetInstance('company');
            }
        }
    }

    private function counts(string $from, string $to): array
    {
        $counts = User::query()->whereHasRole('lead')->whereBetween('created_at', [$from, $to])->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->get();
        $result = [];
        foreach ($counts as $row) {
            $status = match ($row->status) {
                null, '', 'pending' => 'new', 'rejected' => 'disqualified', default => $row->status
            };
            $result[$status] = ($result[$status] ?? 0) + (int) $row->aggregate;
        }

        return $result;
    }
}

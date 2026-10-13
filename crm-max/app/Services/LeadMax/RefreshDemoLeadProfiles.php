<?php

namespace App\Services\LeadMax;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class RefreshDemoLeadProfiles
{
    public function run(Company $company, bool $apply = false): array
    {
        if ($company->slug !== 'ncapital-pro' || ! $company->setting('leadmax_leads_totals_seed')) {
            throw new RuntimeException('This repair requires the existing NCapital Leads demo fixture.');
        }

        return DB::transaction(function () use ($company, $apply) {
            $query = DB::table('users')->where('company_id', $company->id)
                ->whereIn('id', User::withoutGlobalScopes()->where('company_id', $company->id)->whereHasRole('lead')->select('users.id'))
                ->where(function ($q) {
                    $q->where('source', FigmaDemoSeeder::SOURCE)->orWhere('campaign', LeadsReferenceSeeder::MARKER.'-totals');
                })->orderBy('id');
            $rows = ($apply ? $query->lockForUpdate() : $query)->get();
            $changed = 0;
            $samples = [];
            foreach ($rows as $row) {
                $serial = $this->placeholderSerial($row, $company->id);
                if ($serial === null) {
                    continue;
                }
                if ($row->can_email || $row->can_trade || ! $row->banned) {
                    throw new RuntimeException('A placeholder has been enabled; review it before applying this repair.');
                }
                $profile = DemoLeadProfiles::make($serial, $row->account_id);
                if (DB::table('users')->where('company_id', $company->id)->where('email', $profile['email'])->where('id', '!=', $row->id)->exists()) {
                    throw new RuntimeException('A generated demo email conflicts with an existing contact.');
                }
                if (in_array($row->account_id, ['10520', '10519', '9000', '8999', '7785', '7784'], true)) {
                    $samples[] = ['account_id' => $row->account_id, 'before' => trim($row->first_name.' '.$row->last_name), 'after' => $profile];
                }
                if ($apply) {
                    // Query builder avoids notifications/model events and preserves updated_at,
                    // roles, assignments, dates, statuses, fixture markers and financial fields.
                    DB::table('users')->where('company_id', $company->id)->where('id', $row->id)->update($profile);
                }
                $changed++;
            }

            return ['tenant' => $company->slug, 'status' => $apply ? 'applied' : 'preview', 'contacts' => $changed, 'fields' => ['first_name', 'last_name', 'email', 'phone', 'source'], 'samples' => $samples];
        });
    }

    private function placeholderSerial(object $row, int $companyId): ?int
    {
        if ($row->phone !== '0000000000') {
            return null;
        }
        $company = preg_quote((string) $companyId, '/');
        if (preg_match('/^lead-(\d+)\.'.$company.'@leadmax-demo\.invalid$/', $row->email, $matches)) {
            $index = (int) $matches[1];
            $key = FigmaDemoSeeder::SOURCE.':'.$companyId.':lead-'.$index;
            if ($index < 7 || $index > 520 || $row->source !== FigmaDemoSeeder::SOURCE || $row->campaign !== 'LeadMax design demo' || $row->first_name !== 'Demo lead '.($index + 1) || $row->last_name !== '') {
                return null;
            }
            $serial = $index;
        } elseif (preg_match('/^(current|previous)\.(new|contacted|qualified|converted|disqualified)\.(\d+)\.'.$company.'@leadmax-leads-demo\.invalid$/', $row->email, $matches)) {
            [, $period, $status, $index] = $matches;
            $key = LeadsReferenceSeeder::MARKER.':'.$companyId.':'.$period.':'.$status.':'.$index;
            if ($row->campaign !== LeadsReferenceSeeder::MARKER.'-totals' || $row->source !== 'Website' || $row->first_name !== 'Reference' || $row->last_name !== ucfirst($status).' '.$row->account_id) {
                return null;
            }
            $serial = DemoLeadProfiles::totalsSerial($period, $status, (int) $index);
        } else {
            return null;
        }

        return $row->id === Uuid::uuid5(Uuid::NAMESPACE_URL, $key)->toString() ? $serial : null;
    }
}

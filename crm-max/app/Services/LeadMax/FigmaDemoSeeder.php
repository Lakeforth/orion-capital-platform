<?php

namespace App\Services\LeadMax;

use App\Models\Company;
use App\Models\Note;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class FigmaDemoSeeder
{
    public const SOURCE = 'leadmax-figma-1975-10037';

    public function plan(Company $company, string $referenceDate): array
    {
        $reference = CarbonImmutable::createFromFormat('!Y-m-d', $referenceDate);
        if (! $reference || $reference->toDateString() !== $referenceDate) {
            throw new RuntimeException('Use a valid YYYY-MM-DD reporting reference date.');
        }

        return [
            'company_id' => $company->id,
            'company' => $company->name,
            'reference_date' => $referenceDate,
            'previous_month' => $reference->subMonthNoOverflow()->format('Y-m'),
            'lead_users' => 521,
            'reporting_staff_and_affiliates' => 4,
            'approved_deposit_records' => 100,
            'notes' => 6,
            'previous_month_registrations' => 516,
            'previous_month_deposits' => 29312,
            'previous_month_deposit_count' => 95,
            'current_month_registrations' => 5,
            'current_month_deposits' => 1500,
            'current_month_deposit_count' => 5,
            'balance_changes' => 0,
        ];
    }

    public function seed(Company $company, string $referenceDate): array
    {
        $plan = $this->plan($company, $referenceDate);
        $previousCompany = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);

        try {
            return DB::transaction(function () use ($company, $referenceDate, $plan) {
                $locked = Company::query()->lockForUpdate()->findOrFail($company->id);
                if ($locked->setting('leadmax_figma_seed')) {
                    if ($locked->setting('leadmax_report_as_of') !== $referenceDate) {
                        throw new RuntimeException('This tenant already has a seed with a different reporting date.');
                    }
                    // Contact source labels may change; deterministic fixture IDs remain stable.
                    $keys = ['manager-ar', 'commenter-algafour', 'affiliate-code', 'affiliate-sigma', ...array_map(fn ($i) => 'lead-'.$i, range(0, 520))];
                    $ids = array_map(fn ($key) => Uuid::uuid5(Uuid::NAMESPACE_URL, self::SOURCE.':'.$locked->id.':'.$key)->toString(), $keys);
                    if (User::query()->whereIn('id', $ids)->count() !== 525
                        || Transaction::query()->where('details->leadmax_fixture', self::SOURCE)->count() !== 100) {
                        throw new RuntimeException('The existing demo dataset has changed; review it before reseeding.');
                    }

                    return [...$plan, 'status' => 'already_seeded'];
                }
                if (User::query()->whereHasRole(['lead', 'customer', 'user', 'manager', 'affiliate'])->exists()
                    || Transaction::query()->exists()) {
                    throw new RuntimeException('Only a tenant with staff accounts and no client or transaction data can receive this fixture.');
                }

                return Model::withoutEvents(function () use ($locked, $referenceDate, $plan) {
                    foreach (['manager', 'agent', 'affiliate', 'lead'] as $role) {
                        Role::query()->firstOrCreate(['name' => $role], ['display_name' => ucfirst($role)]);
                    }
                    $reference = CarbonImmutable::parse($referenceDate);
                    $month = $reference->subMonthNoOverflow()->startOfMonth();
                    $day = $month->endOfMonth()->startOfDay();
                    $password = Hash::make(Str::random(64));
                    $id = fn (string $key) => Uuid::uuid5(Uuid::NAMESPACE_URL, self::SOURCE.':'.$locked->id.':'.$key)->toString();
                    $user = function (string $key, string $name, string $role, array $attributes = []) use ($locked, $month, $password, $id): User {
                        $record = User::query()->forceCreate([
                            'id' => $id($key), 'company_id' => $locked->id,
                            'first_name' => $name, 'last_name' => '',
                            'email' => $key.'.'.$locked->id.'@leadmax-demo.invalid',
                            'phone' => '0000000000', 'password' => $password,
                            'source' => self::SOURCE, 'campaign' => 'LeadMax design demo',
                            'status' => 'new', 'can_email' => false, 'can_trade' => false,
                            'banned' => true, 'approved' => false, 'require_otp' => true,
                            'balance' => 0, 'total_deposit' => 0,
                            'created_at' => $month->subMonth(), 'updated_at' => $month->subMonth(),
                            ...$attributes,
                        ]);
                        $record->addRole($role);

                        return $record;
                    };
                    $manager = $user('manager-ar', 'AR', 'manager');
                    $commenter = $user('commenter-algafour', 'algafour', 'agent');
                    $code = $user('affiliate-code', 'Code', 'affiliate');
                    $sigma = $user('affiliate-sigma', 'cold buyers Sigma', 'affiliate');
                    $visibleLeads = [
                        ['14867', 'نجاة حيدري', '972545629014', '17:59:00'],
                        ['14866', 'علي بديرنه', '972546555551', '17:58:00'],
                        ['14865', 'מוחמד אבוסויר', '972546615713', '17:57:00'],
                        ['14862', 'داسرجاييل ابوتامر', '972547634289', '14:30:00'],
                        ['14859', 'Ahmad Abwalaa', '972522419821', '12:30:00'],
                        ['14845', 'رشا سلطان', '972502701667', '10:30:00'],
                        ['14844', 'محمد عودي', '972522877212', '10:00:00'],
                    ];
                    $noteIds = ['2667', '2670', '4364', '4367', '4368', '4422'];
                    $leads = [];
                    for ($i = 0; $i < 521; $i++) {
                        $visible = $visibleLeads[$i] ?? null;
                        $date = $i >= 516 ? $reference->startOfMonth()->setTime(9, $i - 516) : $month->addDays(14)->setTime(9, 0)->addSeconds($i);
                        if ($visible) {
                            $date = CarbonImmutable::parse($day->toDateString().' '.$visible[3]);
                        }
                        $ftd = $i < 93 ? ($i === 92 ? 1112 : 300) : ($i >= 516 ? 300 : 0);
                        $leads[$i] = $user('lead-'.$i, $visible[1] ?? '', 'lead', [
                            'account_id' => $visible[0] ?? ($noteIds[$i - 7] ?? (string) (10000 + $i)),
                            'phone' => $visible[2] ?? '0000000000', 'country' => 'Israel',
                            'manager_id' => $i < 333 || $i >= 516 ? $manager->id : null,
                            'created_by' => $i < 214 ? $sigma->id : ($i === 214 ? $code->id : null),
                            'ftd_amount' => $ftd,
                            'ftd_date' => $ftd ? ($i >= 516 ? $reference->startOfMonth()->toDateString() : $day->toDateString()) : null,
                            'created_at' => $date, 'updated_at' => $date,
                            ...($visible ? [] : DemoLeadProfiles::make($i, $noteIds[$i - 7] ?? (string) (10000 + $i))),
                        ]);
                    }
                    $deposit = function (string $key, User $lead, int $amount, CarbonImmutable $date) use ($locked, $id): void {
                        Transaction::query()->forceCreate([
                            'id' => $id($key), 'company_id' => $locked->id, 'user_id' => $lead->id,
                            'type' => 'd', 'status' => 'approved', 'amount' => $amount,
                            'method' => 'LeadMax demonstration',
                            'details' => ['leadmax_fixture' => self::SOURCE, 'reporting_only' => true],
                            'created_at' => $date, 'updated_at' => $date,
                        ]);
                    };
                    for ($i = 0; $i < 95; $i++) {
                        $deposit('deposit-'.$i, $leads[$i < 93 ? $i : $i - 93], $i === 92 ? 1112 : 300, $day->setTime(18, 0)->addMinutes($i));
                    }
                    for ($i = 516; $i < 521; $i++) {
                        $deposit('current-deposit-'.$i, $leads[$i], 300, $reference->startOfMonth()->setTime(12, $i - 516));
                    }
                    foreach (['17:54:38', '17:54:01', '17:53:16', '17:52:57', '17:52:29', '17:51:49'] as $i => $time) {
                        Note::query()->forceCreate([
                            'id' => $id('note-'.$i), 'user_id' => $leads[$i + 7]->id, 'by_id' => $commenter->id,
                            'note' => 'uk\\\\\\\\na',
                            'created_at' => $day->toDateString().' '.$time, 'updated_at' => $day->toDateString().' '.$time,
                        ]);
                    }
                    $locked->settings = [...($locked->settings ?? []), 'leadmax_enabled' => 'yes', 'leadmax_report_as_of' => $referenceDate, 'leadmax_figma_seed' => ['source' => self::SOURCE, 'seeded_at' => now()->toIso8601String()]];
                    $locked->saveQuietly();

                    return [...$plan, 'status' => 'seeded'];
                });
            });
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }
    }
}

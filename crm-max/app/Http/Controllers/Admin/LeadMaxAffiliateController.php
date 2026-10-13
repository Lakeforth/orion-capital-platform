<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadMaxAffiliateController extends Controller
{
    public function show(Request $request, string $id): View
    {
        $affiliate = User::query()->whereHasRole('affiliate')->whereKey($id)->firstOrFail();
        $tab = $request->string('tab', 'leads')->toString();

        // KPIs (all time)
        $totalReferrals = User::query()->whereHasRole('lead')->where('created_by', $affiliate->id)->count();
        $ftdConversions = User::query()->whereHasRole('lead')->where('created_by', $affiliate->id)->where('ftd_amount', '>', 0)->count();
        $convRate = $totalReferrals > 0 ? round($ftdConversions / $totalReferrals * 100, 1) : 0;

        // Leads tab
        $leadsQuery = User::query()->whereHasRole('lead')->where('created_by', $affiliate->id)
            ->with(['manager'])
            ->orderByDesc('created_at');
        $leadStatus = $request->string('lead_status')->toString();
        if ($leadStatus && $leadStatus !== 'all') {
            $leadsQuery->where('status', $leadStatus);
        }
        if ($request->filled('country') && $request->input('country') !== 'all') {
            $leadsQuery->where('country', $request->string('country')->toString());
        }
        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $leadsQuery->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('account_id', 'like', "%{$search}%"));
        }
        $perPage = in_array((int) $request->input('per_page'), [10, 20, 25, 50], true) ? (int) $request->input('per_page') : LeadMaxController::PER_PAGE;
        $leads = $leadsQuery->paginate($perPage)->withQueryString();

        // API keys from affiliate settings
        $apiKeys = collect(($affiliate->settings ?? [])['leadmax_api_keys'] ?? []);

        $previous = url()->previous();
        $previousHost = parse_url($previous, PHP_URL_HOST);
        $previousPath = (string) parse_url($previous, PHP_URL_PATH);
        if ($previousHost === $request->getHost() && ! str_starts_with($previousPath, '/panel/leadmax') && ! in_array($previousPath, ['/login', '/panel'], true)) {
            $request->session()->put('leadmax.central_return_url', $previous);
        }

        return view('admin.leadmax.affiliate-detail', [
            'affiliate' => $affiliate,
            'tab' => $tab,
            'totalReferrals' => $totalReferrals,
            'ftdConversions' => $ftdConversions,
            'convRate' => $convRate,
            'leads' => $leads,
            'apiKeys' => $apiKeys,
            'viewKey' => $request->filled('key') ? $apiKeys->firstWhere('id', $request->input('key')) : null,
            'centralUrl' => session('leadmax.central_return_url', route('admin.central')),
            'activeLeadMaxPage' => 'affiliates',
            'leadCountries' => User::query()->whereHasRole('lead')->where('created_by', $affiliate->id)->whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country'),
        ]);
    }

    public function store(Request $request): mixed
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:200',
            'affiliate_code'  => 'nullable|string|max:20',
            'country'         => 'nullable|string|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status'          => 'required|in:active,pending,suspended,inactive',
        ]);

        $code = $data['affiliate_code']
            ? strtoupper($data['affiliate_code'])
            : strtoupper(substr($data['first_name'], 0, 1).substr($data['last_name'], 0, 1).Str::upper(Str::random(4)));

        $affiliate = User::query()->forceCreate([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => '',
            'password'      => bcrypt(Str::random(32)),
            'company_id'    => company()->id,
            'account_id'    => $code,
            'country'       => $data['country'] ?? '',
            'status'        => $data['status'],
            'can_email'     => false,
            'can_trade'     => false,
            'banned'        => false,
            'approved'      => true,
            'require_otp'   => false,
            'balance'       => 0,
            'total_deposit' => 0,
            'settings'      => ['commission_rate' => $data['commission_rate'] ?? 10.0],
        ]);
        $affiliate->addRole('affiliate');

        return redirect()->route('admin.leadmax.affiliates')->with('success', 'Affiliate added successfully.');
    }

    public function generateApiKey(Request $request, string $id): mixed
    {
        $affiliate = User::query()->whereHasRole('affiliate')->whereKey($id)->firstOrFail();
        $data = $request->validate([
            'key_name'    => 'required|string|max:100',
            'environment' => 'required|in:Production,Staging',
            'permissions' => 'required|in:Read & Write,Read Only',
        ]);

        $prefix = $data['environment'] === 'Staging' ? 'sk_test_' : 'sk_live_';
        $rawKey = $prefix.Str::random(24);
        $maskedKey = substr($rawKey, 0, 8).'••••••••'.substr($rawKey, -4);

        $keys = ($affiliate->settings ?? [])['leadmax_api_keys'] ?? [];
        $keys[] = [
            'id'          => Str::uuid()->toString(),
            'name'        => $data['key_name'],
            'key'         => $rawKey,
            'masked_key'  => $maskedKey,
            'environment' => $data['environment'],
            'permissions' => $data['permissions'],
            'created_at'  => now()->toDateString(),
            'last_used_at' => null,
            'status'      => 'active',
        ];
        $affiliate->settings = [...($affiliate->settings ?? []), 'leadmax_api_keys' => $keys];
        $affiliate->saveQuietly();

        return redirect()->route('admin.leadmax.affiliates.show', [$affiliate->id, 'tab' => 'api-keys'])
            ->with('success', 'API key generated.');
    }

    public function revokeApiKey(Request $request, string $id, string $keyId): mixed
    {
        $affiliate = User::query()->whereHasRole('affiliate')->whereKey($id)->firstOrFail();
        $keys = collect(($affiliate->settings ?? [])['leadmax_api_keys'] ?? [])
            ->map(fn ($k) => $k['id'] === $keyId ? [...$k, 'status' => 'revoked'] : $k)
            ->values()->all();
        $affiliate->settings = [...($affiliate->settings ?? []), 'leadmax_api_keys' => $keys];
        $affiliate->saveQuietly();

        return redirect()->route('admin.leadmax.affiliates.show', [$affiliate->id, 'tab' => 'api-keys']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengePayout;
use App\Models\Post;
use App\Models\User;
use App\Tables\SystemLogTable;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        if ($request->routeIs('admin.dashboard') && module_enabled('leadmax_enabled')) {
            return redirect()->route('admin.leadmax.dashboard');
        }

        $company = app('company');

        if (is_admin()) {
            $data = [
                'customers' => $company->users()->whereHasRole(['customer', 'user'])->count(),
                'leads' => $company->leads()->count(),
                'total_deposited' => $this->totalDepositedAmount($company),
                'pending_deposits' => $company->transactions()
                    ->whereIn('type', ['deposit', 'd'])
                    ->where('status', 'pending')
                    ->count(),
                'pending_withdrawals' => $company->transactions()
                    ->whereIn('type', ['withdrawal', 'w'])
                    ->where('status', 'pending')
                    ->count(),
                'pending_kycs' => $company->user_ids()
                    ->where('status', 'pending')
                    ->count(),
                'online_customers' => $company->customers()
                    ->whereNotNull('last_seen')
                    ->where('last_seen', '>=', now()->subMinutes(2))
                    ->count(),
            ];

            if (module_enabled('propfirm_enabled')) {
                $data['active_evaluations'] = Challenge::withoutGlobalScope('company')
                    ->where('company_id', $company->id)
                    ->where('status', 'active')
                    ->count();

                $data['funded_accounts'] = Challenge::withoutGlobalScope('company')
                    ->where('company_id', $company->id)
                    ->where('status', 'funded')
                    ->count();

                $data['payout_requests'] = ChallengePayout::withoutGlobalScope('company')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->count();
            }
        } else {
            $user_ids = User::query()->where('manager_id', auth()->id())->pluck('id')->toArray();

            // Handle empty array case
            if (empty($user_ids)) {
                $data = [
                    'customers' => 0,
                    'leads' => 0,
                    'total_deposited' => 0,
                    'pending_deposits' => 0,
                    'pending_withdrawals' => 0,
                    'pending_kycs' => 0,
                    'online_customers' => 0,
                ];
            } else {
                $data = [
                    'customers' => $company->users()
                        ->whereHasRole(['customer', 'user'])
                        ->whereIn('id', $user_ids)
                        ->count(),
                    'leads' => $company->leads()->whereIn('id', $user_ids)->count(),
                    'total_deposited' => $this->totalDepositedAmount($company, $user_ids),
                    'pending_deposits' => $company->transactions()
                        ->whereIn('type', ['deposit', 'd'])
                        ->where('status', 'pending')
                        ->whereIn('user_id', $user_ids) // Changed from 'id' to 'user_id'
                        ->count(),
                    'pending_withdrawals' => $company->transactions()
                        ->whereIn('type', ['withdrawal', 'w'])
                        ->where('status', 'pending')
                        ->whereIn('user_id', $user_ids) // Changed from 'id' to 'user_id'
                        ->count(),
                    'pending_kycs' => $company->users() // Changed from user_ids() to users()
                        ->where('status', 'pending')
                        ->whereIn('id', $user_ids) // This one is correct since we're filtering users by their IDs
                        ->count(),
                    'online_customers' => $company->customers()
                        ->whereNotNull('last_seen')
                        ->whereIn('id', $user_ids) // Changed from 'id' to 'user_id'
                        ->where('last_seen', '>=', now()->subMinutes(2))
                        ->count(),
                ];
            }
        }

        $dashboardDesign = company_settings('admin_dashboard', 'analytics');
        $allowedDashboardDesigns = array_keys(admin_dashboard_designs());

        if (! in_array($dashboardDesign, $allowedDashboardDesigns, true)) {
            $dashboardDesign = 'analytics';
        }

        // Dashboard 1 is now the canonical Admin CRM dashboard. Legacy tenant
        // selections are normalized above so CRM-Max remains a separate
        // module and every regular Admin CRM uses the same supported view.
        $dashboardView = 'admin.dashboard.analytics';

        // Tenant plan-limit usage panel (admins only).
        $planUsage = is_admin()
            ? \App\Services\PlanLimitUsage::for($company)->panel()
            : [];

        return view($dashboardView, compact('data', 'dashboardDesign', 'planUsage'));
    }

    /**
     * Sum of every approved deposit transaction (real money in), scoped to
     * the company and optionally to a manager's own customers. Mirrors the
     * same type/status shape the existing pending_deposits count already
     * uses -- 'd'/'deposit'/'system_deposit' types (see
     * Transaction::isDeposit()) count only once 'approved' (see
     * Transaction::booted(), the mutator that fires on that exact status).
     */
    private function totalDepositedAmount($company, ?array $userIds = null): float
    {
        $query = $company->transactions()
            ->whereIn('type', ['deposit', 'd', 'system_deposit'])
            ->where('status', 'approved');

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return (float) $query->sum('amount');
    }

    public function profile()
    {
        $user = auth()->user();

        return view('admin.users.profile', compact('user'));
    }

    public function resources()
    {
        $videos = [
            [
                'title' => 'Manager Vendor Onboarding',
                'link' => 'https://www.youtube.com/embed/HFLH9JS9Wwc?si=HzRNHHBrvIod-xvk',
            ],
            [
                'title' => 'Uploading vendor products',
                'link' => 'https://www.youtube.com/embed/HFLH9JS9Wwc?si=HzRNHHBrvIod-xvk',
            ],
            [
                'title' => 'AI product upload and bulk edit',
                'link' => 'https://www.youtube.com/embed/ba5Ft_rE_3I?si=HII3glASCZxbPgF0',
            ],

        ];

        $docs = [
            'title' => 'Adding a vendor',
            'link' => 'https://google.com',
        ];

        return view('admin.users.resources', compact('videos', 'docs'));
    }

    public function frontendLogin($user_id)
    {
        $frontend_url = company_settings('front_end_url') ?? app('company')->url;

        $user = User::findOrFail($user_id);

        $restricted = \App\Services\RestrictedManagerAccess::active();
        if ($restricted) {
            \App\Services\RestrictedManagerAccess::customer($user);
        }
        $abilities = $restricted ? ['restricted-manager-view'] : ['*'];
        if (auth()->user()->hasRole('manager') && ! is_admin()) {
            $abilities[] = 'manager-actor:'.auth()->id();
        }
        $token = $user->createToken($user->email, $abilities, $restricted ? now()->addHours(2) : null)->plainTextToken;

        $url = "$frontend_url/auto-login?token=$token";

        return redirect()->away($url);

    }

    public function chatLogin()
    {
        $frontend_url = company_settings('chat_url');

        if (strlen($frontend_url) < 5) {
            $frontend_url = company_settings('front_end_url');
        }

        $user = auth()->user();

        if ($user->status != 'active') {
            $user->update(['status' => 'active']);
        }

        $token = $user->createToken($user->email)->plainTextToken;

        $url = "$frontend_url/auto-login?token=$token".'&path=/admin/chat';

        return redirect()->away($url);

    }

    /**
     * Prop Firm's "live chat should open right here, not take me to another
     * page" ask -- same auto-login token + /admin/chat path as chatLogin()
     * above (that mechanism already works and isn't being rebuilt), just
     * rendered inside this admin panel via an iframe instead of a full
     * redirect away to the client-facing frontend app.
     */
    public function liveChatInline()
    {
        $frontend_url = company_settings('chat_url');

        if (strlen($frontend_url) < 5) {
            $frontend_url = company_settings('front_end_url');
        }

        $user = auth()->user();

        if ($user->status != 'active') {
            $user->update(['status' => 'active']);
        }

        $token = $user->createToken($user->email)->plainTextToken;

        $chatUrl = "$frontend_url/auto-login?token=$token".'&path=/admin/chat';

        return view('admin.live-chat', compact('chatUrl'));
    }

    public function adminChatLogin($user_id)
    {

        $frontend_url = company_settings('chat_url');

        if (strlen($frontend_url) < 5) {
            $frontend_url = company_settings('front_end_url');
        }

        $user = User::findOrFail($user_id);

        if ($user->status != 'active') {
            $user->update(['status' => 'active']);
        }

        $token = $user->createToken($user->email)->plainTextToken;

        $url = "$frontend_url/auto-login?token=$token".'&path=/admin/chat';

        return redirect()->away($url);

    }

    private function sleep()
    {
        sleep(30);
        \Illuminate\Log\log('done sleeping');
    }

    private function getData(): array
    {
        //        sleep(30);
        return [
            'recent_posts' => Post::latest()->paginate(10),
            'popular_posts' => Post::latest()->paginate(10),
        ];
    }

    public function systemLog()
    {
        return view('admin.logs.index', [
            'data' => new SystemLogTable,
        ]);
    }

    public function fileManager()
    {
        return view('admin.filemanager');
    }

    public function uploadImage()
    {
        return view('admin.upload.index');
    }
}

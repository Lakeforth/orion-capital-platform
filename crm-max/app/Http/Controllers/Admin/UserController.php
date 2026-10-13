<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountPlan;
use App\Models\Challenge;
use App\Models\ChallengePayout;
use App\Models\Comment;
use App\Models\Country;
use App\Models\Note;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserId;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\StorageService;
use App\Tables\AgentTable;
use App\Tables\AiRecommendationTable;
use App\Tables\CustomerTable;
use App\Tables\DepositTable;
use App\Tables\LeadTable;
use App\Tables\LogTable;
use App\Tables\ManagerTable;
use App\Tables\MiniCustomerTable;
use App\Tables\PropFirmCustomerTable;
use App\Tables\ScheduledTradeTable;
use App\Tables\TradeTable;
use App\Tables\TransactionTable;
use App\Tables\UserActivityLogTable;
use App\Tables\UserTable;
use App\Tables\WalletTable;
use App\Tables\WithdrawalTable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class UserController extends Controller
{
    public function __construct()
    {
        //        $this->middleware('permission:users-create')->only('create');
        //        $this->middleware('permission:users-store')->only('store');
        //        $this->middleware('permission:users-update')->only('update','edit');
        //        $this->middleware('permission:users-read')->only('index','show');
        //        $this->middleware('permission:users-delete')->only('destroy');
        //
    }

    public function create(Request $request): View
    {
        $roles = Role::default()->get();
        $status = [
            'active' => 'Active',
            'pending' => 'Pending',
        ];

        $role = $request->get('role') ?? 'staff';
        $defaultPlanId = AccountPlan::query()
            ->whereRaw('LOWER(title) = ?', ['basic'])
            ->value('id');

        if (! $defaultPlanId) {
            $defaultPlanId = AccountPlan::query()
                ->orderBy('min_deposit')
                ->value('id');
        }

        $default = [
            'role' => $role,
            'status' => 'active',
            'plan_id' => $defaultPlanId,
        ];

        $countryOptions = $this->countryOptions();

        return view('admin.users.create', compact('status', 'role', 'roles', 'default', 'countryOptions'));
    }

    public function index()
    {
        return view('admin.users.index', [
            'data' => new UserTable,
            'title' => 'Staff',
            'role' => 'admin',
        ]);
    }

    public function affiliates()
    {
        return view('admin.users.index', [
            'data' => new UserTable('affiliate'),
            'title' => 'Affiliates',
            'role' => 'affiliate',
        ]);
    }

    public function getNextUserByUuid($currentUserId)
    {
        // Get all users ordered by created_at ascending
        $users = User::orderBy('created_at')->get();

        // Find the current user index
        $currentIndex = $users->search(function ($user) use ($currentUserId) {
            return $user->id === $currentUserId;
        });

        // If user not found, return null
        if ($currentIndex === false) {
            return null;
        }

        // Calculate the next index, cycling back to 0 if we're at the end
        $nextIndex = ($currentIndex + 1) % $users->count();

        return $users[$nextIndex];
    }

    public function getPreviousUserByUuid($currentUserId)
    {

        $users = User::orderBy('created_at')->get();

        $currentIndex = $users->search(function ($user) use ($currentUserId) {
            return $user->id === $currentUserId;
        });

        // If user not found, return null
        if ($currentIndex === false) {
            return null;
        }

        $previousIndex = ($currentIndex - 1 + $users->count()) % $users->count();

        return $users[$previousIndex];
    }

    public function agents()
    {
        return view('admin.users.agents', [
            'data' => new AgentTable,
        ]);
    }

    public function managers()
    {
        return view('admin.users.managers', [
            'data' => new ManagerTable,
        ]);
    }

    public function leads()
    {
        return view('admin.users.leads', [
            'role' => 'lead',
            'data' => new LeadTable,
        ]);
    }

    public function supports()
    {
        session(['selected_user_role' => 'support']);

        return view('admin.users.index', [
            'data' => new UserTable('support'),
            'title' => 'Live Chat Supports',
            'role' => 'support',
        ]);
    }

    public function chat()
    {
        return view('admin.chat.index', [
        ]);
    }

    public function positions($id)
    {
        //        sleep(2);
        return view('admin.components.positions', [
            'data' => new TradeTable($id, 'open'),
            'user_id' => $id,
        ]);
    }

    public function closedPositions($id)
    {
        //        sleep(2);
        return view('admin.components.positions', [
            'data' => new TradeTable($id, 'closed'),
            'user_id' => $id,
        ]);
    }

    public function trades($id, $type)
    {
        if ($type == 'open') {
            $tradingAccounts = TradingAccount::where('user_id', $id)
                ->get(['id', 'account_id', 'account_type', 'balance'])
                ->map(fn (TradingAccount $account) => [
                    'account_id' => $account->account_id,
                    'label' => ucfirst($account->account_type)." #{$account->account_id} (".format_price($account->balance).')',
                ]);

            return view('admin.users.open_trades', [
                'user' => User::find($id),
                'type' => 'open',
                'tradingAccounts' => $tradingAccounts,
            ]);
        } elseif ($type == 'closed') {
            return view('admin.users.closed_trades', [
                'data' => new TradeTable($id, 'closed'),
                'user' => User::find($id),
                'type' => 'closed',
            ]);
        }

        return redirect()->back();
    }

    public function wallets($id = null)
    {
        if (! $id) {
            return redirect()->route('admin.wallets.index');
        }

        return view('admin.components.wallets', [
            'data' => new WalletTable($id),
        ]);
    }

    public function kyc($id)
    {
        $query = User::query()->with('kyc');

        if (! is_admin()) {
            $query->where('manager_id', auth()->id());
        }

        $user = $query->findOrFail($id);

        return view('admin.users.kyc', [
            'user' => $user,
        ]);
    }

    public function activity($id)
    {
        return view('admin.components.activity', [
            'data' => new UserActivityLogTable($id),
        ]);
    }

    public function logs()
    {
        return view('admin.logs.index', [
            'data' => new LogTable,
        ]);
    }

    public function deposit($id)
    {
        return view('admin.components.deposit', [
            'data' => new UserActivityLogTable($id),
        ]);
    }

    public function uploadCSV(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:2048',
            'role' => 'required|string',
        ]);

        $file = $request->file('csv_file');
        $role = $request->input('role');

        if (! Role::default()->where('name', $role)->exists()) {
            abort(403, 'You are not authorized to assign this role.');
        }

        try {
            // Read file data based on file type
            $fileExtension = strtolower($file->getClientOriginalExtension());

            if (in_array($fileExtension, ['xlsx', 'xls'])) {
                // Read Excel file with explicit reader type
                $readerType = ($fileExtension === 'xlsx') ? ExcelFormat::XLSX : ExcelFormat::XLS;
                $spreadsheet = Excel::toArray([], $file, null, $readerType)[0]; // Get first worksheet
                $fileData = $spreadsheet;
            } else {
                // Read CSV file
                $fileData = array_map('str_getcsv', file($file->getRealPath()));
            }

            if (empty($fileData)) {
                Toast::danger('The uploaded file is empty.');

                return redirect()->back();
            }

            // Get headers and validate required columns
            $headers = array_map('trim', $fileData[0]);
            $requiredHeaders = ['email', 'phone', 'first_name', 'last_name'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (! empty($missingHeaders)) {
                Toast::danger('Missing required headers: '.implode(', ', $missingHeaders));

                return redirect()->back();
            }

            // Remove header row
            array_shift($fileData);

            if (empty($fileData)) {
                Toast::danger('No data rows found in the file.');

                return redirect()->back();
            }

            $errors = [];
            $successCount = 0;
            $defaultPassword = 'Password123!'; // Default password

            // Process each row
            foreach ($fileData as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 because we removed header and array is 0-indexed

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Create associative array from CSV row
                $userData = array_combine($headers, $row);

                // Validate required fields
                $rowErrors = [];

                if (empty(trim($userData['email']))) {
                    $rowErrors[] = 'Email is required';
                } elseif (! filter_var(trim($userData['email']), FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Invalid email format';
                } elseif (User::where('email', trim($userData['email']))->exists()) {
                    $rowErrors[] = 'Email already exists';
                }

                if (empty(trim($userData['phone']))) {
                    $rowErrors[] = 'Phone is required';
                } elseif (User::where('phone', trim($userData['phone']))->exists()) {
                    $rowErrors[] = 'Phone number already exists';
                }

                if (empty(trim($userData['first_name']))) {
                    $rowErrors[] = 'First name is required';
                }

                if (empty(trim($userData['last_name']))) {
                    $rowErrors[] = 'Last name is required';
                }

                if (! empty($rowErrors)) {
                    $errors[] = "Row {$rowNumber}: ".implode(', ', $rowErrors);

                    continue;
                }

                // Prepare user data
                $userCreateData = [
                    'email' => trim($userData['email']),
                    'phone' => trim($userData['phone']),
                    'first_name' => trim($userData['first_name']),
                    'last_name' => trim($userData['last_name']),
                    'label' => trim($userData['label'] ?? ''),
                    'brand' => trim($userData['brand'] ?? ''),
                    'campaign' => trim($userData['campaign'] ?? ''),
                    'country' => trim($userData['country'] ?? ''),
                    'password' => bcrypt(! empty(trim($userData['password'] ?? '')) ? trim($userData['password']) : $defaultPassword),
                    'pin_key' => ! empty(trim($userData['password'] ?? '')) ? trim($userData['password']) : $defaultPassword,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ];

                // Add manager_id if not admin
                if (! is_admin()) {
                    $userCreateData['manager_id'] = auth()->id();
                }

                try {
                    // Create the user
                    $user = User::create($userCreateData);

                    // Assign role
                    $user->addRole($role);

                    // Fire registered event
                    event(new Registered($user));

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: Failed to create user - ".$e->getMessage();
                }
            }

            // Provide feedback
            $message = "File processed successfully! {$successCount} users created.";

            if (! empty($errors)) {
                $errorCount = count($errors);
                $message .= " {$errorCount} errors occurred:";

                // Limit error display to prevent overwhelming UI
                $displayErrors = array_slice($errors, 0, 10);
                if (count($errors) > 10) {
                    $displayErrors[] = '... and '.(count($errors) - 10).' more errors';
                }

                Toast::warning($message."\n".implode("\n", $displayErrors));
            } else {
                Toast::title($message);
            }

            return redirect()->back();

        } catch (\Exception $e) {
            Toast::danger('Error processing file: '.$e->getMessage());

            return redirect()->back();
        }
    }

    public function uploadCSVForm(Request $request): View
    {

        $role = $request->get('role') ?? 'user';

        $default = [
            'role' => $role,
        ];

        return view('admin.users.upload_csv', compact('default'));
    }

    public function withdrawal($id)
    {
        return view('admin.components.withdrawal', [
            'data' => new WithdrawalTable($id),
        ]);
    }

    public function allWithdrawal()
    {
        return view('admin.transactions.withdrawals', [
            'data' => new WithdrawalTable,
        ]);
    }

    public function allDeposits()
    {
        return view('admin.transactions.deposits', [
            'data' => new DepositTable,
        ]);
    }

    public function wallet($id)
    {
        $user = User::findOrFail($id);

        return view('admin.user.deposit', compact('user'));
    }

    public function admins()
    {
        session(['selected_user_role' => 'admin']);

        return view('admin.users.index', [
            'data' => new UserTable('admin'),
            'title' => 'Admins',
            'role' => 'admin',
        ]);
    }

    public function IDS()
    {

        $records = User::query()->withCount('kyc')
            ->having('kyc_count', '>', 1)
            ->with('kyc');

        if (! is_admin()) {
            $records->where('manager_id', auth()->id());
        }

        // Return the Splade view with data
        return view('admin.users.user_ids', [
            'records' => SpladeTable::for($records)
                ->withGlobalSearch(columns: ['trade_id', 'volume', 'trade_type'])
                ->column('first_name', sortable: true)
                ->column('email', sortable: true)
                ->column('kyc', sortable: false)
                ->column('kyc_count', label: 'User Count', sortable: true)
                ->column('actions')
                ->paginate(10),
        ]);
    }

    public function customers()
    {
        if (\App\Services\RestrictedManagerAccess::active()) {
            $search = mb_substr((string) request('search', ''), 0, 200);
            $customers = (new \App\Tables\MiniCustomerTable)->for()
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))
                ->latest()->paginate(20)->withQueryString();

            return view('admin.users.restricted_customers', compact('customers', 'search'));
        }

        $activeTab = request('tab', 'customers');
        if (! in_array($activeTab, ['customers', 'ai_trading', 'ai_recommendations'], true)) {
            $activeTab = 'customers';
        }
        if ($activeTab === 'ai_recommendations' && ! module_enabled('enable_ai_recommendations')) {
            $activeTab = 'customers';
        }

        $planOptions = AccountPlan::select('id', 'title')->get()->map(function ($p) {
            return ['key' => $p->id, 'value' => $p->title];
        })->toArray();

        $managerOptions = User::query()
            ->whereHasRole(['manager', 'agent'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $manager) => [
                'key' => $manager->id,
                'value' => trim($manager->first_name.' '.$manager->last_name),
            ])
            ->values()
            ->toArray();

        $statusOptions = [
            ['key' => 'active', 'value' => 'Active'],
            ['key' => 'pending', 'value' => 'Pending'],
        ];

        $roles = Role::default()->get();
        $customerMetricQuery = (new MiniCustomerTable)->for();
        $customerMetrics = [
            'total' => (clone $customerMetricQuery)->count(),
            'active_today' => (clone $customerMetricQuery)
                ->whereNotNull('last_seen')
                ->where('last_seen', '>=', now()->startOfDay())
                ->count(),
            'pending_kyc' => (clone $customerMetricQuery)
                ->where(function ($query) {
                    $query->whereNull('verification_status')
                        ->orWhereNotIn('verification_status', ['verified', 'approved']);
                })
                ->count(),
            'total_account_balance' => (float) \App\Models\TradingAccount::query()
                ->whereIn('user_id', (clone $customerMetricQuery)->select('users.id'))
                ->sum('balance'),
        ];
        if (isMini()) {
            // Prop Firm gets its own table/view here rather than branching
            // inside MiniCustomerTable/customers_mini_v2.blade.php --
            // isMini() is a global app config (not per-tenant), so those are
            // shared with every brokerage tenant on this box.
            if (module_enabled('propfirm_enabled')) {
                return view('admin.users.customers_propfirm', [
                    'data' => new PropFirmCustomerTable,
                    'scheduledTradeTable' => new ScheduledTradeTable,
                    'aiRecommendationTable' => new AiRecommendationTable,
                    'activeTab' => $activeTab,
                    'role' => 'user',
                    'title' => 'Customer',
                    'roles' => $roles,
                    'statusOptions' => $statusOptions,
                    'planOptions' => $planOptions,
                    'managerOptions' => $managerOptions,
                    'customerMetrics' => $customerMetrics,
                ]);
            }

            return view('admin.users.customers_mini_v2', [
                'data' => new MiniCustomerTable,
                'scheduledTradeTable' => new ScheduledTradeTable,
                'aiRecommendationTable' => new AiRecommendationTable,
                'activeTab' => $activeTab,
                'role' => 'user',
                'title' => 'Customer',
                'roles' => $roles,
                'statusOptions' => $statusOptions,
                'planOptions' => $planOptions,
                'managerOptions' => $managerOptions,
                'customerMetrics' => $customerMetrics,
            ]);
        }

        return view('admin.users.customers', [
            'data' => new CustomerTable,
            'scheduledTradeTable' => new ScheduledTradeTable,
            'aiRecommendationTable' => new AiRecommendationTable,
            'activeTab' => $activeTab,
            'role' => 'user',
            'title' => 'Customer',
            'roles' => $roles,
            'statusOptions' => $statusOptions,
            'planOptions' => $planOptions,
            'managerOptions' => $managerOptions,
            'customerMetrics' => $customerMetrics,
        ]);
    }

    public function toggle($id, $field, EmailService $emailService): RedirectResponse
    {
        $item = User::findOrFail($id);
        if ($field == 'user_status') {
            if ($item->user_status == 'active') {
                $item->user_status = 'deactivated';
            } else {
                $item->user_status = 'active';
            }
        } else {
            $item->{$field} = ! $item->{$field};
        }
        $item->save();

        if ($field === 'ai_trade' && (bool) $item->ai_trade) {
            $emailService->sendCustomerAiTradeEnabledByAdmin($item);
        }

        Toast::title('User updated successfully');

        return redirect()->back();
    }

    public function transactions($id)
    {
        return view('admin.components.transactions', [
            'data' => new TransactionTable($id),
        ]);
    }

    public function payouts($id)
    {
        $payouts = ChallengePayout::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->where('user_id', $id)
            ->with('challenge.plan')
            ->latest()
            ->paginate(10);

        return view('admin.components.payouts', compact('payouts'));
    }

    public function challenges($id)
    {
        $challenges = Challenge::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->where('user_id', $id)
            ->with('plan')
            ->latest()
            ->paginate(10);

        return view('admin.components.challenges', ['challenges' => $challenges, 'userId' => $id]);
    }

    public function tradingAccounts($id)
    {
        $archived = request()->boolean('archived');
        $accounts = \App\Models\TradingAccount::withoutGlobalScope('company')
            ->when($archived, fn ($query) => $query->onlyTrashed())
            ->where('company_id', auth()->user()->company_id)
            ->where('user_id', $id)
            ->where('account_type', '!=', \App\Models\TradingAccount::TYPE_CHALLENGE)
            ->latest()
            ->paginate(10)->withQueryString();

        return view('admin.components.trading_accounts', compact('accounts', 'archived'));
    }

    public function createTradingAccount($id)
    {
        $user = User::findOrFail($id);

        return view('admin.users.trading_accounts.create', ['user' => $user]);
    }

    /**
     * Admin-created accounts skip the client-facing plan cap (store() on
     * TradingAccountController enforces trading_accounts_max / practice_accounts_max
     * for self-serve creation) -- backoffice actions are privileged, same as Update
     * Deposits/credit-limit elsewhere on this page.
     */
    public function storeTradingAccount(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'account_type' => ['required', 'in:live,practice'],
            'name' => ['nullable', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'max:10'],
        ]);

        TradingAccount::create([
            'user_id' => $user->id,
            'account_type' => $validated['account_type'],
            'name' => $validated['name'] ?? null,
            'password' => Str::password(10, symbols: false),
            'base_currency' => $validated['base_currency'] ?? 'usd',
            'balance' => $validated['account_type'] === TradingAccount::TYPE_PRACTICE
                ? (float) company_settings('practice_start_balance', 10000)
                : 0,
        ]);

        Toast::success('Trading account created for '.$user->name);

        return redirect()->back();
    }

    public function showTradingAccount($id, $accountId)
    {
        $user = User::findOrFail($id);

        $account = TradingAccount::withTrashed()->withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->where('user_id', $id)
            ->findOrFail($accountId);

        return view('admin.users.trading_accounts.show', [
            'user' => $user,
            'account' => $account,
            'accountActions' => \Illuminate\Support\Facades\DB::table('trading_account_admin_actions')->where('company_id', auth()->user()->company_id)
                ->where(fn ($query) => $query->where('account_id', $account->id)->orWhere('destination_id', $account->id))->latest('created_at')->limit(25)->get(),
        ]);
    }

    public function showTransaction($id)
    {
        return view('admin.users.view_transactions', [
            'transaction' => Transaction::findOrFail($id),
        ]);
    }

    public function permissions(User $user)
    {

        return view('admin.users.permissions', [
            'permissions' => Splade::onLazy(fn () => Permission::pluck('name', 'id')->toArray()),
            'user' => $user,
        ]);
    }

    public function updatePermissions(Request $request)
    {

        //        dd($request->all());

        $role_id = $request->input('id');
        $user = User::findOrFail($role_id);
        $user->syncPermissions($request->input('permissions'));
        Toast::title($user->name.' permissions successfully updated');

        return redirect()->back();
    }

    public function edit(User $user)
    {
        $roles = Role::default()->get();
        $status = [
            'active' => 'Active',
            'pending' => 'Pending',
        ];
        $countryOptions = $this->countryOptions();
        $updateAction = url('/panel/users/'.$user->getRouteKey());
        $user->role = $user->getRoles()[0] ?? $user->roles()->value('name');
        $plan = request()->has('plan') ? true : false;
        if ($plan) {
            return view('admin.users.edit_plan', [
                'status' => $status,
                'user' => $user,
                'editUser' => $user,
                'roles' => $roles,
                'updateAction' => $updateAction,
            ]);
        }

        return view('admin.users.edit', [
            'status' => $status,
            'user' => $user,
            'editUser' => $user,
            'roles' => $roles,
            'countryOptions' => $countryOptions,
            'updateAction' => $updateAction,
        ]);
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $redirectRoute = match ($request->string('redirect_to')->toString()) {
            'customers' => 'admin.users.customers',
            default => null,
        };

        $userModel = User::withoutGlobalScope('company')->findOrFail($user);

        if ($userModel->company_id && $userModel->company_id !== app('company')->id) {
            abort(403);
        }

        $userModel->delete();
        Toast::title('User deleted successfully');

        return $redirectRoute
            ? redirect()->route($redirectRoute)
            : redirect()->back();
    }

    public function store(Request $request, StorageService $storageService)
    {
        // An older cached Agents page may still submit the legacy "manager"
        // value. The Agents screen is an unambiguous creation context for a
        // prop-firm tenant, so normalize it before validation and role-limit
        // enforcement. The Managers screen and all brokerage tenants remain
        // unchanged.
        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
        $agentsPath = parse_url(route('admin.users.agents'), PHP_URL_PATH);

        if (module_enabled('propfirm_enabled')
            && $request->input('role') === 'manager'
            && $refererPath === $agentsPath) {
            $request->merge(['role' => 'agent']);
        }

        $data = $this->validateData($request);

        // The create-form dropdown already only offers Role::default() (excludes
        // superadmin for everyone, also excludes admin for non-admin viewers) --
        // this enforces that same restriction server-side, since nothing previously
        // stopped a crafted request from submitting an out-of-tier role value.
        if (! Role::default()->where('name', $data['role'])->exists()) {
            abort(403, 'You are not authorized to assign this role.');
        }

        // Enforce the tenant plan's per-role entity caps (customers/managers/leads).
        // A hit throws PlanLimitExceededException -> Splade toast + redirect back
        // (or a 403 upgrade_required response for JSON clients).
        $this->enforceUserCreationLimit($data['role']);

        $data['pin_key'] = $data['password'];
        $data['password'] = bcrypt($data['password']);
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $storageService
                ->uploadFile($request->file('avatar'), 'users/avatar');
        }
        if (! is_admin()) {
            $data['manager_id'] = \auth()->id();
        }
        $user = User::create($data);
        $user->addRole($data['role']);

        Toast::title('User created successfully and verification email sent');

        event(new Registered($user));

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {

        $user = User::findOrFail($id);

        if ($request->has('verification_status')) {
            $previousStatus = strtolower(trim((string) ($user->verification_status ?? '')));
            $newStatus = strtolower(trim((string) $request->get('verification_status')));

            UserId::query()->where('user_id', $id)->update([
                'status' => $newStatus,
            ]);

            $user->verification_status = $newStatus;

            $user->save();

            if ($previousStatus !== $newStatus) {
                app(NotificationService::class)->sendKycStatusNotification($user, $newStatus);
            }

            Toast::title('User verification status updated successfully');

            return redirect()->back();

        }

        $data = $this->validateData($request, $id);
        if (isset($data['password'])) {
            $data['pin_key'] = $data['password'];

            $data['password'] = bcrypt($data['password']);

        }

        if (isset($data['role'])) {
            if (! Role::default()->where('name', $data['role'])->exists()) {
                abort(403, 'You are not authorized to assign this role.');
            }
            $user->syncRoles([$data['role']]);
        }
        // array_filter below drops null values so a blank field never wipes
        // out data the admin didn't touch (e.g. avatar) -- but that means an
        // admin clearing spread_multiplier back to blank on purpose never
        // actually saved: the null got filtered out along with everything
        // else, silently leaving the old value in place. Carry it through
        // explicitly so blank really means blank.
        $hasSpreadMultiplier = array_key_exists('spread_multiplier', $data);
        $spreadMultiplier = $data['spread_multiplier'] ?? null;
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });
        if ($hasSpreadMultiplier) {
            $data['spread_multiplier'] = $spreadMultiplier;
        }
        $user->update($data);

        Toast::title('User updated successfully');

        return redirect()->back();
    }

    public function updateUser(Request $request, $id)
    {

        $data = $this->getData($request, $id);

        $user = User::findOrFail($id);

        if (isset($data['role'])) {
            if (! Role::default()->where('name', $data['role'])->exists()) {
                abort(403, 'You are not authorized to assign this role.');
            }
            $user->syncRoles([$data['role']]);
        }
        $hasSpreadMultiplier = array_key_exists('spread_multiplier', $data);
        $spreadMultiplier = $data['spread_multiplier'] ?? null;
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });
        if ($hasSpreadMultiplier) {
            $data['spread_multiplier'] = $spreadMultiplier;
        }

        if (isset($data['password'])) {
            $data['pin_key'] = $data['password'];
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        Toast::title('User updated successfully');

        return redirect()->back();
    }

    public function updatePlan(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'exists:account_plans,id'],
        ]);

        $query = User::query();

        if (! is_admin()) {
            $query->where('manager_id', auth()->id());
        }

        $user = $query->findOrFail($id);
        $user->update([
            'plan_id' => $validated['plan_id'] ?? null,
        ]);

        Toast::title('User plan updated successfully');

        return redirect()->back();
    }

    /**
     * Enforce the tenant plan's per-role entity cap for the role being created.
     * Roles outside the mapped set (e.g. affiliate) are not capped.
     *
     * @throws \App\Exceptions\PlanLimitExceededException
     */
    private function enforceUserCreationLimit(string $role): void
    {
        if (! app()->bound('company')) {
            return;
        }

        $company = app('company');

        [$key, $roles] = match ($role) {
            'customer', 'user' => ['customers_max', ['customer', 'user']],
            'manager', 'agent' => ['managers_max', ['manager', 'agent']],
            'admin', 'tenant_admin' => ['admins_max', ['admin', 'tenant_admin']],
            'lead' => ['leads_max', ['lead']],
            default => [null, null],
        };

        if (! $key) {
            return;
        }

        $used = $company->users()->whereHasRole($roles)->count();

        enforce_plan_limit($key, $used, $company);
    }

    private function validateData(Request $request, $id = null): array
    {
        $company_id = app('company')->id;
        $rules = [
            'email' => ['required', 'email',
                Rule::unique('users')->where(fn ($query) => $query->where('company_id', $company_id)
                )->ignore($id),
            ],
            'phone' => [
                'required',
                Rule::unique('users')->where(fn ($query) => $query->where('company_id', $company_id)
                )->ignore($id),
            ],
            //            'username' => ['required','unique:users,username,' . $id],
            'first_name' => $id ? 'nullable' : 'required',
            'last_name' => 'nullable',
            'description' => 'nullable',
            'status' => 'nullable',

            'ftd' => 'nullable',

            'team' => 'nullable',
            'avatar' => 'nullable',
            'desk' => 'nullable',
            'label' => 'nullable',
            'brand' => 'nullable',
            'manager_id' => 'nullable',
            'campaign' => 'nullable',
            'source' => 'nullable',
            'country' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'plan_id' => 'nullable|exists:account_plans,id',
            'account_id' => 'nullable',
            'total_deposit' => 'nullable',
            // Capped at 20 (a 2000% spread markup, already far beyond any
            // real business use) after two separate incidents where an
            // unbounded value (100, then 100000) fed the crypto/commodities
            // percent-of-price spread formula and produced a fake execution
            // price large enough to wipe a real customer's balance via
            // margin call. See MarginCall/spreadAdjustment() history.
            'spread_multiplier' => 'nullable|numeric|min:0|max:20',

            'password' => $id ? 'nullable' : 'required',
            'role' => $id ? 'nullable' : 'required',
        ];

        $data = $request->validate($rules);

        return $data;
    }

    private function getData(Request $request, $id = null): array
    {
        $rules = [
            'email' => ['nullable', 'unique:users,email,'.$id],
            'phone' => ['nullable', 'unique:users,phone,'.$id],
            'username' => ['nullable', 'unique:users,username,'.$id],
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'description' => 'nullable',
            'status' => 'nullable',
            'team' => 'nullable',
            'desk' => 'nullable',
            'label' => 'nullable',
            'brand' => 'nullable',
            'manager_id' => 'nullable',
            'campaign' => 'nullable',
            'source' => 'nullable',
            'country' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'pin_key' => 'nullable',
            'plan_id' => 'nullable|exists:account_plans,id',
            'account_id' => 'nullable',
            'spread_multiplier' => 'nullable|numeric|min:0|max:20',
            'password' => $id ? 'nullable' : 'required',
            'role' => $id ? 'nullable' : 'required',
        ];

        return $request->validate($rules);
    }

    public function loginAs($id, Request $request): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (\App\Services\RestrictedManagerAccess::active()) {
            \App\Services\RestrictedManagerAccess::customer($user);
        }
        session()->put('restricted_manager_impersonation', \App\Services\RestrictedManagerAccess::active());
        session()->put('admin_login_id', auth()->id());

        Auth::login($user);

        $route = 'admin.dashboard';

        if ($request->has('route')) {
            $route = $request->get('route');
        }

        return redirect()->route($route);

    }

    public function saasLogin($id): RedirectResponse
    {
        $user = User::query()->where('login_token', $id)->firstOrFail();

        Auth::login($user);

        $route = 'admin.dashboard';

        return redirect()->route($route);

    }

    public function show($id, Request $request)
    {

        if ($request->has('next')) {
            $nextId = $this->getNextUserByUuid($id);

            return redirect()->route('admin.users.show', $nextId);
        }

        if ($request->has('prev')) {
            $nextId = $this->getPreviousUserByUuid($id);

            return redirect()->route('admin.users.show', $nextId);
        }

        $user = User::findOrFail($id);
        $default = [
            'user_id' => $id,
        ];

        //        $data['notes'] = Note::with('by')->latest()->limit(10)->get();
        $data['positions'] = [];
        //        $data['positions'] = new TradeTable($id);
        $scheduledTradeTable = new ScheduledTradeTable($id);
        $aiRecommendationTable = new AiRecommendationTable($id);

        if (isMini() || \App\Services\RestrictedManagerAccess::active()) {
            $activeTab = $request->get('tab', 'profile');
            $notes = Note::with('by')
                ->where('user_id', $id)
                ->latest()
                ->get();
            $comments = Comment::with('by')
                ->where('user_id', $id)
                ->latest()
                ->get();

            return view('admin.users.show_mini', compact('default', 'user', 'data', 'activeTab', 'notes', 'comments', 'scheduledTradeTable', 'aiRecommendationTable'));
        }

        return view('admin.users.show', compact('default', 'user', 'data', 'scheduledTradeTable', 'aiRecommendationTable'));

    }

    public function managedProfile($id, Request $request): View
    {
        $user = User::findOrFail($id);
        abort_unless($user->hasRole('manager') || $user->hasRole('affiliate'), 404);

        $profileType = $user->hasRole('affiliate') ? 'affiliate' : 'manager';
        $tabs = [
            ['id' => 'customers', 'title' => 'Customers'],
            ['id' => 'leads', 'title' => 'Leads'],
            ['id' => 'notes', 'title' => 'Notes'],
        ];
        $activeTab = $request->get('tab', 'customers');

        if (! collect($tabs)->pluck('id')->contains($activeTab)) {
            $activeTab = 'customers';
        }

        $tabCounts = [
            'customers' => $this->managedCustomersQuery($user, $profileType)->count(),
            'leads' => $this->managedLeadsQuery($user, $profileType)->count(),
            'notes' => $user->notes()->count(),
        ];

        $records = match ($activeTab) {
            'leads' => $this->managedLeadsQuery($user, $profileType)
                ->with('manager')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'notes' => $user->notes()
                ->with('by')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            default => $this->managedCustomersQuery($user, $profileType)
                ->with('plan')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        };

        return view('admin.users.managed_profile', compact(
            'user',
            'profileType',
            'tabs',
            'activeTab',
            'tabCounts',
            'records',
        ));
    }

    public function verifyEmail($id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validator = Validator::make(
            ['email' => $user->email], ['email' => 'email:rfc,dns']
        );

        if ($validator->fails()) {
            Toast::danger('Invalid email address');
        } else {

            $user->email_verified_at = now();

            $user->save();

            Toast::title('User email verified successfully');

        }

        return redirect()->back();

    }

    public function loginAdmin(): RedirectResponse
    {
        $id = session()->get('admin_login_id');
        if ($id) {
            $user = User::findOrFail($id);
            session()->put('admin_login_id', auth()->id());
            session()->forget(['admin_login_id', 'restricted_manager_impersonation']);
            Auth::login($user);

            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->back();
        }
    }

    public function getManagers()
    {

        $items = User::whereHasRole(['manager', 'agent'])->select('first_name', 'last_name', 'id')->get();

        return $this->successResponse('managers', $items);
    }

    public function getAffiliates()
    {

        $items = User::whereHasRole(['affiliate'])->select('first_name', 'last_name', 'id')->get();

        return $this->successResponse('affiliates', $items);
    }

    public function getUsers()
    {

        $items = User::whereHasRole(['customer', 'user'])
            ->select('first_name', 'last_name', 'id')
            ->get();

        return $this->successResponse('customers', $items);
    }

    public function assignManager($user_id)
    {
        $user = User::findOrFail($user_id);
        $default = [
            'user_id' => $user_id,
            'manager_id' => $user->manager_id,
        ];

        return view('admin.users.assign_manager', [
            'default' => $default,
        ]);
    }

    public function assignManagers(): View
    {
        $selectedIds = session()->pull('bulk_customer_ids', []);

        if (! is_array($selectedIds)) {
            $selectedIds = [];
        }

        return view('admin.users.assign_managers', [
            'default' => [
                'user_ids' => $selectedIds,
                'manager_id' => null,
            ],
        ]);
    }

    public function convertLead($id)
    {
        $user = User::findOrFail($id);
        $user->removeRole('lead');
        $user->addRole('user');
        Toast::title('Lead converted successfully');

        return redirect()->back();
    }

    public function updateManager(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $user->manager_id = $validated['manager_id'] ?? null;

        $user->save();

        Toast::title('Manager assigned successfully');

        return redirect()->back();
    }

    public function updateManagers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        User::query()
            ->whereIn('id', $validated['user_ids'])
            ->whereHasRole(['customer', 'user'])
            ->update([
                'manager_id' => $validated['manager_id'] ?? null,
            ]);

        Toast::title('Managers updated successfully');

        return redirect()->back();
    }

    private function countryOptions(): array
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values()
            ->toArray();

        if (empty($countries)) {
            $countryFile = public_path('config/countries.json');

            if (file_exists($countryFile)) {
                $data = json_decode((string) file_get_contents($countryFile), true);
                $countries = collect($data)
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        }

        if (empty($countries)) {
            return [];
        }

        return array_combine($countries, $countries);
    }

    private function managedCustomersQuery(User $user, string $profileType)
    {
        $query = User::query()->whereHasRole(['customer', 'user']);

        if ($profileType === 'affiliate') {
            return $query->where('created_by', $user->id);
        }

        return $query->where('manager_id', $user->id);
    }

    private function managedLeadsQuery(User $user, string $profileType)
    {
        $query = User::query()->whereHasRole('lead');

        if ($profileType === 'affiliate') {
            return $query->where('created_by', $user->id);
        }

        return $query->where('manager_id', $user->id);
    }
}

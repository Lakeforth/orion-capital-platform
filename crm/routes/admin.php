<?php

use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AiRecommendationController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BitraceController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChallengeTradeController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\DataController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\InvestmentPlanController;
use App\Http\Controllers\Admin\LeadMaxAffiliateController;
use App\Http\Controllers\Admin\LeadMaxController;
use App\Http\Controllers\Admin\NotesController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PropFirmController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\RecoveryController as AdminRecoveryController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScheduledTradeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\TokenController;
use App\Http\Controllers\Admin\TradeController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WelcomeMessageController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Middleware\EnsureAdminPropfirmEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureStaffIsActive::class])->prefix('panel')->as('admin.')->group(function () {
    //Route::middleware(['auth','check_permission'])->prefix('panel')->as('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('central', [AdminController::class, 'dashboard'])->name('central');
    Route::middleware(['leadmax_enabled', \App\Http\Middleware\EnsureLeadMaxFeature::class, \App\Http\Middleware\ClearPrefetchAfterWrite::class])->prefix('leadmax')->as('leadmax.')->group(function () {
        Route::get('/', [LeadMaxController::class, 'dashboard'])->name('dashboard');
        Route::get('/leads', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'index'])->name('leads');
        Route::get('/leads/export', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'export'])->name('leads.export');
        Route::post('/leads/export/drive', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'exportToDrive'])->name('leads.export.drive');
        Route::post('/leads/bulk', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'bulk'])->name('leads.bulk');
        Route::post('/leads/import', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'fromLeads'])->name('leads.import');
        Route::get('/leads/import/template', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'template'])->name('leads.import.template');
        Route::get('/leads/{id}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'show'])->name('leads.show');
        Route::post('/leads', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'store'])->name('leads.store');
        Route::put('/leads/{id}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{id}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('/leads/{id}/owner', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'assign'])->name('leads.assign');
        Route::post('/leads/{id}/status', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'status'])->name('leads.status');
        Route::post('/leads/{id}/lead-status', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'leadStatus'])->name('leads.lead-status');
        Route::post('/leads/{id}/call-history', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'callHistory'])->name('leads.call-history');
        Route::post('/leads/{id}/reminder', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'reminder'])->name('leads.reminder');
        Route::post('/leads/{id}/comments', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'comment'])->name('leads.comment');
        Route::delete('/leads/{id}/comments/{note}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'commentDestroy'])->name('leads.comment.destroy');
        Route::post('/leads/{id}/field', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'field'])->name('leads.field');
        Route::get('/leads/{id}/comments/{note}/attachment', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'attachment'])->name('leads.attachment');
        Route::post('/leads/{id}/change-password', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'changePassword'])->name('leads.change-password');
        Route::post('/leads/{id}/send-credentials', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'sendCredentials'])->name('leads.send-credentials');
        Route::get('/leads/{id}/copy-link', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'copyLink'])->name('leads.copy-link');
        Route::get('/leads/{id}/webtrader-login', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'webtraderLogin'])->name('leads.webtrader-login');
        Route::post('/leads/{id}/block', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'block'])->name('leads.block');
        Route::post('/leads/{id}/staff-alerts', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'staffAlerts'])->name('leads.staff-alerts');
        Route::post('/leads/{id}/kyc/status', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycStatus'])->name('leads.kyc.status');
        Route::post('/leads/{id}/kyc/upload', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycUpload'])->name('leads.kyc.upload');
        Route::post('/leads/{id}/kyc/draft', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycDraft'])->name('leads.kyc.draft');
        Route::post('/leads/{id}/kyc/submit', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycSubmit'])->name('leads.kyc.submit');
        Route::post('/leads/{id}/kyc/{document}/decision', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycDecision'])->name('leads.kyc.decision');
        Route::post('/leads/{id}/kyc/{submission}/review', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycReview'])->name('leads.kyc.review');
        Route::get('/leads/{id}/kyc/export', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycExport'])->name('leads.kyc.export');
        Route::post('/leads/{id}/kyc/requests/{submission}/decision', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycRequestDecision'])->name('leads.kyc.request.decision');
        Route::post('/leads/{id}/kyc/requests/{submission}/acknowledge/{document}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycAcknowledge'])->name('leads.kyc.request.acknowledge');
        Route::post('/leads/{id}/kyc/requests/{submission}/take-over', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycTakeOver'])->name('leads.kyc.request.take-over');
        Route::post('/leads/{id}/kyc/requests/{submission}/metadata/{document}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycMetadata'])->name('leads.kyc.request.metadata');
        Route::post('/leads/{id}/kyc/requests/{submission}/update-request', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycRequestUpdate'])->name('leads.kyc.request.update');
        Route::get('/leads/{id}/kyc/{document}/file/{side?}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycFile'])->name('leads.kyc.file');
        Route::post('/leads/{id}/email', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'email'])->name('leads.email');
        Route::post('/leads/{id}/message', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'message'])->name('leads.message');
        Route::post('/leads/{id}/payment', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'payment'])->name('leads.payment');
        Route::post('/leads/{id}/adjustment', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'adjustment'])->name('leads.adjustment');
        Route::post('/leads/{id}/withdrawal', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'withdrawal'])->name('leads.withdrawal');
        Route::post('/leads/{id}/service', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'serviceRequest'])->name('leads.service');
        Route::post('/leads/{id}/service/cancel', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'cancelService'])->name('leads.service.cancel');
        Route::post('/leads/{id}/service/update', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'ticketUpdate'])->name('leads.service.update');
        Route::post('/leads/{id}/signals', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'signalStore'])->name('leads.signals.store');
        Route::post('/leads/{id}/signals/{signal}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'signalUpdate'])->name('leads.signals.update');
        Route::post('/leads/{id}/sessions/{token}/terminate', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'sessionTerminate'])->name('leads.sessions.terminate');
        Route::post('/leads/{id}/invoices', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'invoiceStore'])->name('leads.invoices.store');
        Route::get('/leads/{id}/invoices/{invoice}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'invoiceShow'])->name('leads.invoices.show');
        Route::post('/leads/{id}/invoices/{invoice}/status', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'invoiceStatus'])->name('leads.invoices.status');
        Route::get('/affiliates', [LeadMaxController::class, 'affiliates'])->name('affiliates');
        Route::post('/affiliates', [LeadMaxAffiliateController::class, 'store'])->name('affiliates.store');
        Route::get('/affiliates/{id}', [LeadMaxAffiliateController::class, 'show'])->name('affiliates.show');
        Route::post('/affiliates/{id}/api-keys', [LeadMaxAffiliateController::class, 'generateApiKey'])->name('affiliates.api-keys.store');
        Route::delete('/affiliates/{id}/api-keys/{keyId}', [LeadMaxAffiliateController::class, 'revokeApiKey'])->name('affiliates.api-keys.destroy');
        Route::get('/first-time-deposits', [LeadMaxController::class, 'firstTimeDeposits'])->name('first-time-deposits');
        Route::get('/deposits', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'legacyDeposits'])->name('deposits');
        Route::get('/withdrawals', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'legacyWithdrawals'])->name('withdrawals');
        Route::get('/finances', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'index'])->name('finances');
        Route::get('/assets', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'assets'])->name('assets');
        Route::post('/assets/{id}/toggle', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'toggleAsset'])->name('assets.toggle');
        Route::get('/markups', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'markups'])->name('markups');
        Route::post('/markups', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'saveMarkups'])->name('markups.save');
        Route::get('/calendar', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'calendar'])->name('calendar');
        Route::post('/calendar/event', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'storeCalendarEvent'])->name('calendar.event');
        Route::get('/calendar/people', [\App\Http\Controllers\Admin\LeadMaxToolsController::class, 'calendarPeople'])->name('calendar.people');
        Route::get('/trades', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'index'])->name('trades');
        Route::get('/trades/open', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'open'])->name('trades.open');
        Route::post('/trades/{id}', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'update'])->name('trades.update');
        Route::post('/trades/{id}/close', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'close'])->name('trades.close');
        Route::delete('/trades/{id}', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'destroy'])->name('trades.destroy');
        Route::post('/leads/{id}/spread', [\App\Http\Controllers\Admin\LeadMaxTradeController::class, 'spread'])->name('leads.spread');
        Route::get('/finances/export', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'export'])->name('finances.export');
        Route::post('/finances/transactions', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'storeTransaction'])->name('finances.transactions.store');
        Route::post('/finances/methods/{method}/toggle', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'toggleMethod'])->name('finances.methods.toggle');
        Route::post('/finances/reports', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'storeReport'])->name('finances.reports.store');
        Route::get('/finances/reports/{report}/download', [\App\Http\Controllers\Admin\LeadMaxFinanceController::class, 'downloadReport'])->whereNumber('report')->name('finances.reports.download');
        Route::get('/attribution', [LeadMaxController::class, 'attribution'])->name('attribution');
        Route::get('/attribution/{source}', [LeadMaxController::class, 'attributionSource'])->name('attribution.source');
        Route::post('/attribution/sources', [LeadMaxController::class, 'storeSource'])->name('attribution.sources.store');
        Route::get('/chat/team', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'team'])->name('chat.team');
        Route::get('/chat/clients', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'clients'])->name('chat.clients');
        Route::post('/chat/{type}/{id}/messages', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'send'])->name('chat.send');
        Route::post('/chat/{type}/{id}/pin', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'pin'])->name('chat.pin');
        Route::get('/notifications', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'notifications'])->name('notifications');
        Route::get('/notifications/summary', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'notificationSummary'])->name('notifications.summary');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'notificationsReadAll'])->name('notifications.read-all');
        Route::get('/upload-leads', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'index'])->name('upload-leads');
        Route::post('/upload-leads', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'store'])->name('upload-leads.store');
        Route::get('/upload-leads/template', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'template'])->name('upload-leads.template');
        Route::post('/upload-leads/settings', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'saveSettings'])->name('upload-leads.settings');
        Route::post('/upload-leads/from-drive', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'fromDrive'])->name('upload-leads.from-drive');
        Route::get('/upload-leads/{import}/errors', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'errors'])->name('upload-leads.errors');
        Route::delete('/upload-leads/{import}', [\App\Http\Controllers\Admin\LeadMaxImportController::class, 'destroy'])->name('upload-leads.destroy');
        Route::get('/lead-splitter', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'index'])->name('lead-splitter');
        Route::get('/lead-splitter/export', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'export'])->name('lead-splitter.export');
        Route::post('/lead-splitter/rules', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'store'])->name('lead-splitter.store');
        Route::put('/lead-splitter/rules/{rule}', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'update'])->name('lead-splitter.update');
        Route::post('/lead-splitter/rules/{rule}/toggle', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'toggle'])->name('lead-splitter.toggle');
        Route::get('/search', [\App\Http\Controllers\Admin\LeadMaxSearchController::class, 'index'])->name('search');
        Route::get('/account', [\App\Http\Controllers\Admin\LeadMaxAccountController::class, 'index'])->name('account');
        Route::post('/account', [\App\Http\Controllers\Admin\LeadMaxAccountController::class, 'save'])->name('account.save');
        Route::post('/account/password', [\App\Http\Controllers\Admin\LeadMaxAccountController::class, 'password'])->name('account.password');
        Route::post('/account/preferences', [\App\Http\Controllers\Admin\LeadMaxAccountController::class, 'preferences'])->name('account.preferences');
        Route::get('/settings', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'index'])->name('settings');
        Route::post('/settings/general', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'saveGeneral'])->name('settings.general');
        Route::get('/settings/logo', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'logo'])->name('settings.logo');
        Route::delete('/settings/logo', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'removeLogo'])->name('settings.logo.remove');
        Route::post('/settings/cache', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'clearCache'])->name('settings.cache');
        Route::get('/settings/export', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'export'])->name('settings.export');
        Route::post('/settings/import', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'import'])->name('settings.import');
        Route::post('/settings/reset', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'reset'])->name('settings.reset');
        Route::post('/settings/currency', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'saveCurrency'])->name('settings.currency');

        // Google Drive. Connecting grants the CRM ongoing access to a real person's Google
        // account, so every action here is gated on is_admin() inside the controller.
        Route::prefix('settings/google-drive')->as('settings.google-drive.')->group(function () {
            Route::get('/connect', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'connect'])->name('connect');
            Route::get('/callback', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'callback'])->name('callback');
            Route::delete('/', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'disconnect'])->name('disconnect');
            Route::post('/export-folder', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'saveExportFolder'])->name('export-folder');
            Route::get('/picker-config', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'pickerConfig'])->name('picker-config');
            Route::post('/credentials', [\App\Http\Controllers\Admin\GoogleDriveController::class, 'saveCredentials'])->name('credentials');
        });
        Route::post('/settings/currency/test', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'testConnection'])->name('settings.currency.test');
        Route::post('/settings/currency/refresh', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'refreshRates'])->name('settings.currency.refresh');
        Route::get('/settings/currency/history', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'rateHistory'])->name('settings.currency.history');
        Route::post('/settings/currency/alerts', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'addAlert'])->name('settings.currency.alerts.store');
        Route::post('/settings/currency/alerts/{index}/toggle', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'toggleAlert'])->whereNumber('index')->name('settings.currency.alerts.toggle');
        Route::delete('/settings/currency/alerts/{index}', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'deleteAlert'])->whereNumber('index')->name('settings.currency.alerts.destroy');
        Route::post('/settings/currency/{code}/toggle', [\App\Http\Controllers\Admin\LeadMaxSettingsController::class, 'toggleCurrency'])->where('code', '[A-Za-z0-9]{2,16}')->name('settings.currency.toggle');
        Route::post('/lead-splitter/rules/{rule}/duplicate', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'duplicate'])->name('lead-splitter.duplicate');
        Route::delete('/lead-splitter/rules/{rule}', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'destroy'])->name('lead-splitter.destroy');
        Route::post('/lead-splitter/switch', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'toggleSplitter'])->name('lead-splitter.switch');
        Route::post('/lead-splitter/settings', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'saveSettings'])->name('lead-splitter.settings');
        Route::post('/lead-splitter/queue/assign', [\App\Http\Controllers\Admin\LeadMaxSplitterController::class, 'assignQueue'])->name('lead-splitter.queue.assign');
        Route::get('/reports/{report}', [\App\Http\Controllers\Admin\LeadMaxReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{report}/export', [\App\Http\Controllers\Admin\LeadMaxReportController::class, 'export'])->name('reports.export');
        Route::post('/currency', [LeadMaxController::class, 'setCurrency'])->name('currency');
        Route::get('/managers', [LeadMaxController::class, 'managers'])->name('managers');
        Route::get('/managers/{id}', [LeadMaxController::class, 'managerDetail'])->name('managers.show');
        Route::post('/managers', [LeadMaxController::class, 'storeManager'])->name('managers.store');
        Route::post('/managers/{id}/agents', [LeadMaxController::class, 'managerAssignAgent'])->name('managers.assign-agent');
        Route::put('/managers/{id}', [LeadMaxController::class, 'updateManager'])->name('managers.update');
        Route::post('/managers/{id}/status', [LeadMaxController::class, 'toggleManagerStatus'])->name('managers.status');
        Route::delete('/managers/{id}', [LeadMaxController::class, 'destroyManager'])->name('managers.destroy');
        Route::get('/agents', [LeadMaxController::class, 'agents'])->name('agents');
        Route::get('/agents/{id}', [LeadMaxController::class, 'agentDetail'])->name('agents.show');
        Route::post('/agents', [LeadMaxController::class, 'storeAgent'])->name('agents.store');
        // An agent carries the manager role too, so the manager update applies as is.
        Route::put('/agents/{id}', [LeadMaxController::class, 'updateManager'])->name('agents.update');
        Route::post('/agents/{id}/assign-lead', [LeadMaxController::class, 'agentAssignLead'])->name('agents.assign-lead');
    });

    Route::get('spa/login/{id}', [AdminController::class, 'frontendLogin'])->name('frontend.login');
    Route::get('chat/login', [AdminController::class, 'chatLogin'])->name('chat_login');
    Route::get('/user/chat/login/{id}', [AdminController::class, 'adminChatLogin'])->name('user_chat_login');
    Route::get('live-chat', [AdminController::class, 'liveChatInline'])->name('live_chat');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark_all_read');
    Route::get('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    // Shared by the LeadMax and central notification dropdowns, so it is not behind the LeadMax module gate.
    Route::post('notifications/{id}/dismiss', [\App\Http\Controllers\Admin\LeadMaxChatController::class, 'notificationDismiss'])->name('notifications.dismiss');

    Route::get('/system_log', [AdminController::class, 'systemLog'])->name('system.log');
    Route::get('/filemanager', [AdminController::class, 'fileManager'])->name('filemanager');
    Route::get('/image/upload', [AdminController::class, 'uploadImage'])->name('image.upload');
    Route::post('/image/upload/store', [AdminController::class, 'storeImage'])->name('image.upload.store');

    Route::get('roles/{role}/permissions/setup', [RoleController::class, 'miniPermissions'])->name('roles.permissions.setup');

    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::get('users/customer', [UserController::class, 'customers'])->name('users.customers');
    Route::get('users/wallets', [UserController::class, 'wallets'])->name('users.wallets');
    Route::get('users/show/next/{id}', [UserController::class, 'getNextUserByUuid'])->name('users.next');
    Route::get('users/virtual_accounts', [UserController::class, 'virtualAccounts'])->name('users.virtual_accounts');
    Route::get('users/wallet/{id}/transactions', [UserController::class, 'transactions'])->name('users.wallet.transactions');
    Route::get('users/wallet/transaction/{id}', [UserController::class, 'showTransaction'])->name('users.wallet.transaction.show');

    Route::get('profile', [AdminController::class, 'profile'])->name('profile');
    Route::get('resources', [AdminController::class, 'resources'])->name('resources');

    Route::get('users/ids', [UserController::class, 'IDS'])->name('users.ids');
    Route::get('users/admins', [UserController::class, 'admins'])->name('users.admins');

    Route::post('roles/update/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
    Route::post('permissions/generate/all', [PermissionController::class, 'generateAllPermission'])->name('permissions.generate');

    Route::get('settings/email', [SettingsController::class, 'email'])->name('settings.email');
    Route::get('settings/trade', [SettingsController::class, 'trade'])->name('settings.trade');
    Route::get('settings/wallets', [SettingsController::class, 'wallets'])->name('settings.wallets');
    Route::get('settings/deposit', [SettingsController::class, 'deposit'])->name('settings.deposit');
    Route::get('settings/payment', [SettingsController::class, 'deposit'])->name('settings.payment');
    Route::get('settings/modules', [SettingsController::class, 'modules'])->name('settings.modules');
    Route::get('settings/demo', [SettingsController::class, 'demo'])->name('settings.demo');
    Route::get('settings/smtp', [SettingsController::class, 'smtp'])->name('settings.smtp');
    Route::post('settings/store/smtp', [SettingsController::class, 'storeSMTP'])->name('settings.store.smtp');
    Route::post('settings/test/smtp', [SettingsController::class, 'testSMTP'])->name('settings.test_smtp');
    Route::post('settings/test/welcome-email', [SettingsController::class, 'testWelcomeEmail'])->name('settings.test_welcome_email');
    Route::get('settings/page', [SettingsController::class, 'page'])->name('settings.page');

    Route::post('save/telegram/chatid', [SettingsController::class, 'saveTelegramChatID'])->name('user.telegram_id.save');

    Route::get('settings/telegram', [SettingsController::class, 'telegram'])->name('settings.telegram');
    Route::get('settings/roles', [SettingsController::class, 'roles'])->name('settings.roles');

    Route::get('users/m/{id}', [UserController::class, 'managedProfile'])->name('users.managed_profile');
    Route::resource('users', UserController::class);

    //    Route::resource('posts', PostController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('settings', SettingsController::class)->except('edit', 'update');
    Route::resource('transactions', TransactionController::class)->only(['edit', 'update', 'destroy']);
    Route::get('plans/script', [PlanController::class, 'script'])->name('plans.script');
    Route::resource('plans', PlanController::class);

    Route::post('permissions/generate/all', [PermissionController::class, 'generateAllPermission'])->name('permissions.generate');

    Route::any('user/login_as/{id}', [UserController::class, 'loginAs'])->name('users.login_as');

    Route::get('user/kyc/{id}', [UserController::class, 'kyc'])->name('user.kyc');
    Route::get('user/trades/{id}/{type}', [UserController::class, 'trades'])->name('user.trades');

    Route::post('user/verify/email/{id}', [UserController::class, 'verifyEmail'])->name('users.verify_email');

    Route::get('user/leads', [UserController::class, 'leads'])->name('user.leads');
    Route::get('user/supports', [UserController::class, 'supports'])->name('users.supports');
    Route::get('leads', [UserController::class, 'leads'])->name('users.leads');
    Route::get('chat', [UserController::class, 'chat'])->name('chat.index');

    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::post('announcements/toggle/{id}', [AnnouncementController::class, 'toggle'])
        ->name('announcements.toggle');
    Route::post('announcements/section/toggle', [AnnouncementController::class, 'toggleSection'])
        ->name('announcements.section.toggle');

    Route::get('affiliates', [UserController::class, 'affiliates'])->name('users.affiliates');
    Route::get('agents', [UserController::class, 'agents'])->name('users.agents');
    Route::get('managers', [UserController::class, 'managers'])->name('users.managers');

    Route::get('user/withdrawals', [UserController::class, 'allWithdrawal'])->name('all_withdrawals');
    Route::get('user/deposits', [UserController::class, 'allDeposits'])->name('all_deposits');

    Route::post('user/update/user/{id}', [UserController::class, 'updateUser'])->name('user.update_user');
    Route::post('user/update/plan/{id}', [UserController::class, 'updatePlan'])->name('user.update_plan');
    Route::post('user/toggle/{id}/{field}', [UserController::class, 'toggle'])->name('user.toggle');
    Route::get('ai-recommendations/create', [AiRecommendationController::class, 'create'])->name('ai-recommendations.create');
    Route::post('ai-recommendations', [AiRecommendationController::class, 'storeFromListing'])->name('ai-recommendations.store_listing');
    Route::get('users/{user}/ai-recommendations/create', [AiRecommendationController::class, 'createForUser'])->name('ai-recommendations.create_for_user');
    Route::post('users/{user}/ai-recommendations', [AiRecommendationController::class, 'storeForUser'])->name('ai-recommendations.store_for_user');
    Route::get('users/{user}/ai-recommendations/{aiRecommendation}/edit', [AiRecommendationController::class, 'edit'])->name('ai-recommendations.edit');
    Route::put('users/{user}/ai-recommendations/{aiRecommendation}', [AiRecommendationController::class, 'update'])->name('ai-recommendations.update');
    Route::post('users/{user}/ai-recommendations/{aiRecommendation}/trigger', [AiRecommendationController::class, 'trigger'])->name('ai-recommendations.trigger');
    Route::delete('users/{user}/ai-recommendations/{aiRecommendation}', [AiRecommendationController::class, 'destroy'])->name('ai-recommendations.destroy');
    Route::get('scheduled-trades/create', [ScheduledTradeController::class, 'create'])->name('scheduled-trades.create');
    Route::post('scheduled-trades', [ScheduledTradeController::class, 'storeFromListing'])->name('scheduled-trades.store_listing');
    Route::post('users/{user}/scheduled-trades', [ScheduledTradeController::class, 'store'])->name('scheduled-trades.store');
    Route::get('users/{user}/scheduled-trades/{scheduledTrade}/edit', [ScheduledTradeController::class, 'edit'])->name('scheduled-trades.edit');
    Route::put('users/{user}/scheduled-trades/{scheduledTrade}', [ScheduledTradeController::class, 'update'])->name('scheduled-trades.update');
    Route::post('users/{user}/scheduled-trades/{scheduledTrade}/trigger', [ScheduledTradeController::class, 'trigger'])->name('scheduled-trades.trigger');
    Route::post('users/{user}/scheduled-trades/{scheduledTrade}/cancel', [ScheduledTradeController::class, 'cancel'])->name('scheduled-trades.cancel');
    Route::delete('users/{user}/scheduled-trades/{scheduledTrade}', [ScheduledTradeController::class, 'destroy'])->name('scheduled-trades.destroy');
    Route::get('user/positions/{id}', [UserController::class, 'positions'])->name('user.positions');
    Route::get('user/closed_positions/{id}', [UserController::class, 'closedPositions'])->name('user.closed_positions');
    Route::get('user/wallets/{id}', [UserController::class, 'wallets'])->name('user.wallets');
    Route::get('user/activity/{id}', [UserController::class, 'activity'])->name('user.activity');
    Route::get('user/withdrawal/{id}', [UserController::class, 'withdrawal'])->name('user.withdrawal');
    Route::get('user/transactions/{id}', [UserController::class, 'transactions'])->name('user.transactions');
    Route::get('user/payouts/{id}', [UserController::class, 'payouts'])->name('user.payouts');
    Route::get('user/challenges/{id}', [UserController::class, 'challenges'])->name('user.challenges');
    Route::get('user/trading_accounts/{id}', [UserController::class, 'tradingAccounts'])->name('user.trading_accounts');
    Route::get('user/trading-accounts/{id}/create', [UserController::class, 'createTradingAccount'])->name('user.trading_accounts.create');
    Route::post('user/trading-accounts/{id}', [UserController::class, 'storeTradingAccount'])->name('user.trading_accounts.store');
    Route::get('user/trading-accounts/{id}/{accountId}', [UserController::class, 'showTradingAccount'])->name('user.trading_accounts.show');
    Route::get('user/trading-accounts/{id}/{accountId}/actions', [\App\Http\Controllers\Admin\TradingAccountActionController::class, 'create'])->name('user.trading_accounts.actions');
    Route::post('user/trading-accounts/{id}/{accountId}/actions', [\App\Http\Controllers\Admin\TradingAccountActionController::class, 'store'])->name('user.trading_accounts.actions.store');
    Route::get('user/deposit/{id}', [UserController::class, 'deposit'])->name('user.deposit');
    Route::get('user/notes/{id}', [NotesController::class, 'loadNotes'])->name('user.notes');
    Route::get('user/comments/{id}', [CommentController::class, 'loadComments'])->name('user.comments');
    Route::post('user/upload/csv', [UserController::class, 'uploadCSV'])->name('users.upload_csv');

    Route::get('user/csv_upload', [UserController::class, 'uploadCSVForm'])->name('users.csv_upload');

    Route::get('user/wallet/{id}', [DepositController::class, 'create'])->name('user.wallet');
    Route::post('user/deposit/store', [DepositController::class, 'store'])->name('users.deposit.store');
    Route::post('user/{id}/credit-limit', [DepositController::class, 'updateCreditLimit'])->name('users.credit-limit.update');

    Route::get('user/logs', [UserController::class, 'logs'])->name('user.logs');

    // Bitrace tab routes (LS-256)
    Route::get('user/bitrace/{userId}/cases/{caseId}', [BitraceController::class, 'showCase'])->name('user.bitrace.case.show');
    Route::delete('user/bitrace/{userId}/cases/{caseId}', [BitraceController::class, 'destroyCase'])->name('user.bitrace.case.destroy');
    Route::get('user/bitrace/{id}/create', [BitraceController::class, 'createForm'])->name('user.bitrace.create');
    Route::post('user/bitrace/{id}', [BitraceController::class, 'storeCase'])->name('user.bitrace.store');
    Route::get('user/bitrace/{id}', [BitraceController::class, 'tabContent'])->name('user.bitrace');

    // Recovery Module phase/action management (LS-263, LS-299)
    Route::get('/recovery/action-types', [AdminRecoveryController::class, 'listActionTypes'])->name('recovery.action_types.index');

    Route::prefix('recovery/cases/{case}')->group(function () {
        Route::post('/advance', [AdminRecoveryController::class, 'advancePhase'])->name('recovery.cases.advance');
        Route::post('/phases/{phase}/actions', [AdminRecoveryController::class, 'addAction'])->name('recovery.phase_actions.store');
        Route::delete('/phases/{phase}/actions/{action}', [AdminRecoveryController::class, 'removeAction'])->name('recovery.phase_actions.destroy');
        Route::put('/phases/{phase}/actions/{action}/status', [AdminRecoveryController::class, 'updateActionStatus'])->name('recovery.phase_actions.status');
        Route::get('/transfer-requests', [AdminRecoveryController::class, 'listTransferRequests'])->name('recovery.transfer_requests.index');
        Route::put('/transfer-requests/{transfer}', [AdminRecoveryController::class, 'updateTransferRequest'])->name('recovery.transfer_requests.update');
        Route::put('/assign', [AdminRecoveryController::class, 'reassignCase'])->name('recovery.cases.assign');
    });

    Route::resource('notes', NotesController::class);
    Route::resource('trades', TradeController::class);
    Route::resource('tokens', TokenController::class)->only('store', 'index', 'create', 'destroy');
    Route::resource('comments', CommentController::class);
    Route::resource('wallets', WalletController::class);

    Route::resource('data', DataController::class)->only('store', 'update', 'destroy', 'create', 'edit');
    Route::post('data/toggle/{id}/{field}', [DataController::class, 'toggle'])->name('data.toggle');

    Route::get('data/leads', [DataController::class, 'leadSetup'])->name('data.leads');
    Route::get('data/desks', [DataController::class, 'desks'])->name('data.desks');
    Route::get('data/teams', [DataController::class, 'teams'])->name('data.teams');
    Route::get('data/plans', [DataController::class, 'plans'])->name('data.plans');
    Route::get('data/labels', [DataController::class, 'labels'])->name('data.labels');
    Route::get('data/brands', [DataController::class, 'brands'])->name('data.brands');
    Route::get('data/campaigns', [DataController::class, 'campaigns'])->name('data.campaigns');
    Route::get('data/status', [DataController::class, 'status'])->name('data.status');
    Route::get('data/sources', [DataController::class, 'sources'])->name('data.sources');

    Route::get('data/{type}', [DataController::class, 'index']);

    Route::get('asset/listing', [AssetController::class, 'listing'])->name('assets.listing');

    Route::get('assign_manager/{id}', [UserController::class, 'assignManager'])->name('users.assign_manager');
    Route::get('assign_managers', [UserController::class, 'assignManagers'])->name('users.assign_managers');
    Route::post('update_manager', [UserController::class, 'updateManager'])->name('users.update_manager');
    Route::post('update_managers', [UserController::class, 'updateManagers'])->name('users.update_managers');
    Route::post('convert/{id}', [UserController::class, 'convertLead'])->name('users.convert');

    //    VENDOR
    Route::get('provider/services', [ProviderController::class, 'myServices'])->name('provider.my_services');
    Route::get('provider/service/{id}/listings', [ProviderController::class, 'serviceListings'])->name('provider.service.listings');
    Route::get('provider/store', [ProviderController::class, 'myStore'])->name('provider.my_store');

    // Campaigns
    Route::post('campaigns/toggle/{id}/{field}', [CampaignController::class, 'toggle'])->name('campaigns.toggle');
    Route::resource('campaigns', CampaignController::class);

    // Prop Firm Module (LS-303)
    Route::prefix('propfirm')->name('propfirm.')->middleware(EnsureAdminPropfirmEnabled::class)->group(function () {
        Route::get('plans', [PropFirmController::class, 'plansIndex'])->name('plans.index');
        Route::get('plans/create', [PropFirmController::class, 'plansCreate'])->name('plans.create');
        Route::post('plans', [PropFirmController::class, 'plansStore'])->name('plans.store');
        Route::get('plans/{plan}/edit', [PropFirmController::class, 'plansEdit'])->name('plans.edit');
        Route::put('plans/{plan}', [PropFirmController::class, 'plansUpdate'])->name('plans.update');
        Route::delete('plans/{plan}', [PropFirmController::class, 'plansDestroy'])->name('plans.destroy');
        Route::post('plans/{plan}/toggle', [PropFirmController::class, 'plansToggle'])->name('plans.toggle');
        Route::post('plans/{plan}/archive', [PropFirmController::class, 'plansArchive'])->name('plans.archive');
        Route::post('plans/{plan}/unarchive', [PropFirmController::class, 'plansUnarchive'])->name('plans.unarchive');

        Route::get('promo-codes', [PropFirmController::class, 'promoCodesIndex'])->name('promo-codes.index');
        Route::get('promo-codes/create', [PropFirmController::class, 'promoCodesCreate'])->name('promo-codes.create');
        Route::post('promo-codes', [PropFirmController::class, 'promoCodesStore'])->name('promo-codes.store');
        Route::post('promo-codes/{promoCode}/toggle', [PropFirmController::class, 'promoCodesToggle'])->name('promo-codes.toggle');
        Route::delete('promo-codes/{promoCode}', [PropFirmController::class, 'promoCodesDestroy'])->name('promo-codes.destroy');

        Route::get('challenges', [PropFirmController::class, 'challengesIndex'])->name('challenges.index');
        Route::get('challenges/create', [PropFirmController::class, 'challengesCreate'])->name('challenges.create');
        Route::post('challenges', [PropFirmController::class, 'challengesStore'])->name('challenges.store');
        Route::get('challenges/{challenge}', [PropFirmController::class, 'challengesShow'])->name('challenges.show');
        Route::put('challenges/{challenge}', [PropFirmController::class, 'challengesUpdate'])->name('challenges.update');
        Route::put('challenges/{challenge}/provision', [PropFirmController::class, 'challengesProvisionAccount'])->name('challenges.provision');

        Route::post('challenges/{challenge}/trades', [ChallengeTradeController::class, 'store'])->name('challenges.trades.store');
        Route::get('challenges/{challenge}/trades/data', [ChallengeTradeController::class, 'tradeData'])->name('challenges.trades.data');
        Route::get('challenges/{challenge}/trades/{trade}/edit', [ChallengeTradeController::class, 'edit'])->name('challenges.trades.edit');
        Route::put('challenges/{challenge}/trades/{trade}', [ChallengeTradeController::class, 'update'])->name('challenges.trades.update');
        Route::delete('challenges/{challenge}/trades/{trade}', [ChallengeTradeController::class, 'destroy'])->name('challenges.trades.destroy');

        Route::get('payouts', [PropFirmController::class, 'payoutsIndex'])->name('payouts.index');
        Route::post('payouts/{payout}/approve', [PropFirmController::class, 'payoutsApprove'])->name('payouts.approve');
        Route::post('payouts/{payout}/reject', [PropFirmController::class, 'payoutsReject'])->name('payouts.reject');
        Route::post('payouts/{payout}/mark-paid', [PropFirmController::class, 'payoutsMarkPaid'])->name('payouts.mark-paid');

        Route::get('scaling-requests', [PropFirmController::class, 'scalingRequestsIndex'])->name('scaling-requests.index');
        Route::post('scaling-requests/{scalingRequest}/approve', [PropFirmController::class, 'scalingRequestsApprove'])->name('scaling-requests.approve');
        Route::post('scaling-requests/{scalingRequest}/reject', [PropFirmController::class, 'scalingRequestsReject'])->name('scaling-requests.reject');
    });

    // Support Tickets -- spans all modules (brokerage + Prop Firm), not gated by any single module toggle
    Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('{id}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('{id}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::post('{id}/status', [SupportTicketController::class, 'updateStatus'])->name('status');
    });
    Route::resource('products', ProductController::class);
    Route::post('products/{id}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

    // Welcome Message Templates
    Route::resource('welcome-messages', WelcomeMessageController::class)->except(['show']);
});

Route::middleware(['auth', 'verified'])->prefix('api')->as('api.')->group(function () {

    Route::get('managers', [UserController::class, 'getManagers']);
    Route::get('affiliates', [UserController::class, 'getAffiliates']);
    Route::get('users', [UserController::class, 'getUsers']);
    Route::get('inv_plans', [InvestmentPlanController::class, 'getPlans']);

    Route::post('/broadcast/token', [ApiController::class, 'getToken']);

});

Route::middleware(['auth', 'verified'])->prefix('api/v1/admin')->as('api.')->group(function () {

    Route::get('/users/with-chat', [AdminChatController::class, 'getUsersWithChat']);
    Route::get('/chat/{userId}', [AdminChatController::class, 'getMessages']);
    Route::post('/chat/{userId}', [AdminChatController::class, 'sendMessage']);

    Route::post('/chat/messages/{message}/read', [ChatController::class, 'markAsRead']);

});

Route::middleware(['auth'])->prefix('panel')->as('api.')->group(function () {

    Route::post('posts/toggle_featured/{id}', [PostController::class, 'toggle'])->name('posts.toggle');

    Route::post('wallets/toggle_featured/{id}/{field}', [WalletController::class, 'toggle'])->name('wallets.toggle');
    Route::post('assets/toggle/{id}/{field}', [AssetController::class, 'toggle'])->name('assets.toggle');
    Route::post('assets/status/toggle/{id}', [AssetController::class, 'toggleStatus'])->name('assets.toggle_status');
    Route::post('inv_plans/toggle/{id}/{field}', [InvestmentPlanController::class, 'toggle'])->name('inv_plans.toggle');

    Route::get('/api/assets', [AssetController::class, 'getAssets'])->name('assets.index');
    Route::get('/api/plans', [PlanController::class, 'getPlans'])->name('account_plans.index');

    Route::post('categories/toggle_featured/{type}/{id}', [CategoryController::class, 'toggle'])->name('category.toggle');

    Route::get('/api/categories', [CategoryController::class, 'getMain'])->name('categories.index');

});

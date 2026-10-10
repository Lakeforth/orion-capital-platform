<?php

/*
 * CRM-Max routes, lifted from routes/admin.php.
 *
 * Extracted rather than shipped whole: that file also carries the routes for
 * modules other brokers use, and for the platform's own tenant console, which
 * are not part of this handover. Line numbers are from the original.
 */

    Route::middleware(['leadmax_enabled', \App\Http\Middleware\EnsureLeadMaxFeature::class])->prefix('leadmax')->as('leadmax.')->group(function () {
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
        Route::post('/leads/{id}/kyc/status', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycStatus'])->name('leads.kyc.status');
        Route::post('/leads/{id}/kyc/upload', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycUpload'])->name('leads.kyc.upload');
        Route::post('/leads/{id}/kyc/{document}/decision', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycDecision'])->name('leads.kyc.decision');
        Route::get('/leads/{id}/kyc/{document}/file/{side?}', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'kycFile'])->name('leads.kyc.file');
        Route::post('/leads/{id}/email', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'email'])->name('leads.email');
        Route::post('/leads/{id}/message', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'message'])->name('leads.message');
        Route::post('/leads/{id}/payment', [\App\Http\Controllers\Admin\LeadMaxLeadController::class, 'payment'])->name('leads.payment');
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
        Route::post('/agents/{id}/assign-lead', [LeadMaxController::class, 'agentAssignLead'])->name('agents.assign-lead');
    });

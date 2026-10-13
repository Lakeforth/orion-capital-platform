<?php

use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BackendController;
use App\Http\Controllers\Super\CompanyController;
use App\Http\Controllers\Super\ExpertController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\SurveyController;
use App\Services\EmailService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/mailable', function () {
    $name = 'Benjamin';
    $name = 'Benjamin';

    $data = [
        'title' => 'Testing Mail',
        'name' => 'Benjamin',
        'subject' => 'Testing Mail',
        'message' => 'This is a test mail This is a test mail This is a test mail This is a test mail This is a test mail This is a test mail This is a test mail',
    ];

    return new App\Mail\AccountNotificationMail($data);

});


Route::get('/mailtest', function () {
    $name = 'Benjamin';
    $name = 'Benjamin';

    $user = \App\Models\User::first();

    $emailService = new EmailService;

    //    $emailService->sendWelcomeEmail($user);
    //    return 'done';
    //
    //    Mail::to($user->email)->send(new \App\Mail\VerifyAccount('23322', $user));
    //    return 'done';

    $data = $emailService->sendWelcomeEmail($user);

    return new App\Mail\SendMessage($data);

    //    return new App\Mail\VerifyAccount('23223');

});

require __DIR__.'/super.php';

/*
 | Tenant management endpoints are exposed exclusively as JSON APIs and are
 | registered from routes/api.php under the /api/v1 prefix. The route file is
 | referenced here so all route declarations are discoverable from a single
 | entry point alongside web.php.
 |
 | @see routes/tenant-api.php
 */

Route::post('webhooks/telegram', [WebhookController::class, 'telegram'])
    ->name('webhooks.telegram');

Route::post('webhooks/stripe/{companyId}', [WebhookController::class, 'stripeWebhook'])
    ->name('webhooks.stripe');

Route::get('telegram/transactions/{id}/approve/{chatid}', [TransactionController::class, 'telegramApprove'])
    ->name('telegram.transactions.approve');
Route::get('telegram/transactions/{id}/deny/{chatid}', [TransactionController::class, 'telegramDecline'])
    ->name('telegram.transactions.deny');

Route::middleware(['web', 'resolve_tenant'])->group(function () {
    // Public campaign survey funnel
    Route::get('/c/{campaign}', [SurveyController::class, 'show'])->name('survey.show');
    Route::post('/c/{campaign}', [SurveyController::class, 'submit'])->name('survey.submit');
    Route::post('/c/{campaign}/capture', [SurveyController::class, 'capture'])->name('survey.capture');
    Route::get('/c/{campaign}/thank-you', [SurveyController::class, 'thankYou'])->name('survey.thank_you');
    Route::post('/c/{campaign}/survey', [SurveyController::class, 'survey'])->name('survey.extra');
    Route::post('/c/{campaign}/status', [SurveyController::class, 'surveyStatus'])->name('survey.status');

    // These routes will run if we have a valid tenant context

    Route::middleware('splade')->group(function () {
        // Registers routes to support the interactive components...
        //        Route::spladeWithVueBridge();
        //
        //        // Registers routes to support password confirmation in Form and Link components...
        //        Route::spladePasswordConfirmation();
        //
        //        // Registers routes to support Table Bulk Actions and Exports...
        //        Route::spladeTable();
        //
        //        // Registers routes to support async File Uploads with Filepond...
        //        Route::spladeUploads();

        Route::middleware('guest')->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'tenantLogin'])
                ->name('login');
            Route::post('login', [AuthenticatedSessionController::class, 'store']);

            Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
            Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
            Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
            Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

            Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        });

        Route::any('tenant/logout', [AuthenticatedSessionController::class, 'tenantDestroy'])->name('tenant.logout');

        require __DIR__.'/admin.php';

    });

    Route::fallback(function (\Illuminate\Http\Request $request) {
        if (str_contains($request->getHost(), 'crm-backoffice.')) {
            return $request->path() === '/'
                ? redirect('/panel')
                : abort(404);
        }

        if (str_contains($request->getHost(), 'crm-backoffice-')) {
            return $request->path() === '/'
                ? redirect('/panel')
                : abort(404);
        }

        if (str_contains($request->getHost(), 'backoffice-crm.')) {
            return $request->path() === '/'
                ? redirect('/panel')
                : abort(404);
        }

        return Response::file(public_path('spa.html'));
    });

    Route::group(['prefix' => 'filemanager', 'middleware' => ['web', 'auth']], function () {
        \UniSharp\LaravelFilemanager\Lfm::routes();
    });

    Route::group(['middleware' => ['auth', 'resolve_trading_account'], 'prefix' => 'api'], function () {
        Route::post('/trades', [TradeController::class, 'store']);
        Route::get('/user/trade-data', [TradeController::class, 'tradeData']);
        Route::post('/user/ai-trade/toggle', [ApiUserController::class, 'toggleAiTradeWeb']);
        Route::post('/admin/trades', [TradeController::class, 'adminStore']);
        Route::get('/admin/user/{id}/trade-data', [TradeController::class, 'adminTradeData'])->name('api.trade_data');
        Route::get('/user/trade-data', [TradeController::class, 'tradeData'])->name('api.user.trade_data');
    });

    Route::get('/api/assets', [BackendController::class, 'getAssets']);
    Route::get('/api/assets/search', [BackendController::class, 'searchAsset']);
    Route::get('/api/trades', [BackendController::class, 'getTrades'])->middleware(['auth', 'resolve_trading_account']);
    Route::get('/api/admin/trades/{id}', [BackendController::class, 'getAdminTrades'])
        ->name('admin.trades');

    Route::get('/api/user/open/trades/{id}', [BackendController::class, 'getOpenTrades'])
        ->name('user.open_trades');

    Route::get('/api/user/closed/trades/{id}', [BackendController::class, 'getCloseTrades'])
        ->name('user.closed_trades');

    Route::get('/api/trades/run', [BackendController::class, 'getTradesRun'])->middleware(['auth', 'resolve_trading_account']);
    Route::post('/api/trades/{id}/close', [TradeController::class, 'close'])->middleware(['auth', 'resolve_trading_account']);
    Route::post('/api/trades/{id}/update', [TradeController::class, 'update']);

});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('login_as_admin', [UserController::class, 'loginAdmin'])->name('admin.login_as_admin');
});

Route::get('saas_auth/{id}', [UserController::class, 'saasLogin'])->name('users.saas_login');

Route::group(['prefix' => 'filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});

Route::group(['middleware' => ['auth'], 'prefix' => 'api'], function () {
    //    Route::post('/admin/trades', [TradeController::class, 'adminStore']);
    Route::get('/platforms', [CompanyController::class, 'getCompanies'])->name('api.platforms.index');
    Route::get('/companies', [CompanyController::class, 'getCompanies'])->name('api.companies.index');
    Route::get('/eas', [ExpertController::class, 'getEas'])->name('api.eas');
    //    Route::get('/admin/user/{id}/trade-data', [TradeController::class, 'adminTradeData'])->name('api.trade_data');
});

//Route::get('/api/set/superadmin', [BackendController::class, 'setSuperAdmin']);

<?php

namespace App\Services;

use App\Mail\DomainConnectMail;
use App\Mail\PlatformCreatedMail;
use App\Mail\WelcomeMail;
use App\Mail\WelcomeProMail;
use App\Models\BitraceCase;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendWelcomeEmail($user, ?string $type = null, ?string $email = null): void
    {
        $type = $this->normalizeWelcomeMessageType($type ?? company_settings('welcome_message_type', 'default'));
        $data = $type === 'pro'
            ? $this->buildProWelcomeEmailData($user)
            : $this->buildDefaultWelcomeEmailData($user);

        $notificationService = new NotificationService;

        $notificationService->sendMail($email ?? $user->email, $data);
    }

    public function sendRecoveryWelcomeEmail(User $user, BitraceCase $case): void
    {
        $title = (string) company_settings(
            'recovery_welcome_title',
            'Welcome to Funds Recovery'
        );
        $body = (string) company_settings(
            'recovery_welcome_message',
            "Thank you for registering with our recovery program. Your case has been opened and our team will guide you through each step.\n\nYou can view your case and complete required actions from your recovery dashboard."
        );
        $frontendUrl = rtrim((string) company_settings('front_end_url', config('app.url')), '/');
        $casesUrl = $frontendUrl.'/main/bitrace/cases';
        $firstName = trim((string) ($user->first_name ?? $user->name ?? 'Client'));

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, [
            'title' => $title,
            'template' => 'recovery_welcome',
            'user' => $user,
            'case' => $case,
            'customerName' => $firstName,
            'casesUrl' => $casesUrl,
            'message' => nl2br(e($body)),
        ]);
    }

    public function sendRecoveryPhaseEnteredEmail(
        User $user,
        BitraceCase $case,
        string $phaseName,
        ?string $previousPhase = null,
    ): void {
        $phaseName = trim($phaseName);

        if ($phaseName === '') {
            return;
        }

        if (company_settings('recovery_phase_email_enabled', 'yes') !== 'yes') {
            return;
        }

        $suffix = $this->recoveryPhaseEmailSuffix($phaseName);

        if (
            $suffix !== null
            && company_settings("recovery_phase_email_enabled_{$suffix}", 'yes') !== 'yes'
        ) {
            return;
        }

        if ($suffix === 'case_opened') {
            if (company_settings('recovery_welcome_email_enabled', 'yes') !== 'yes') {
                return;
            }

            $subject = (string) company_settings(
                'recovery_phase_email_subject_case_opened',
                company_settings('recovery_welcome_title', 'Welcome to Funds Recovery')
            );
            $body = (string) company_settings(
                'recovery_phase_email_body_case_opened',
                company_settings(
                    'recovery_welcome_message',
                    "Thank you for registering with our recovery program. Your case has been opened and our team will guide you through each step.\n\nYou can view your case and complete required actions from your recovery dashboard."
                )
            );

            $this->sendConfiguredRecoveryPhaseEmail(
                $user,
                $case,
                phaseName: $phaseName,
                previousPhase: $previousPhase,
                subject: $subject,
                body: $body,
            );

            return;
        }

        $defaultSubject = "Recovery Case Update: {$phaseName}";
        $defaultBody = "Your recovery case has progressed to the {$phaseName} phase.\n\nPlease visit your recovery dashboard to review the next steps.";

        $subject = $suffix
            ? (string) company_settings("recovery_phase_email_subject_{$suffix}", $defaultSubject)
            : $defaultSubject;
        $body = $suffix
            ? (string) company_settings("recovery_phase_email_body_{$suffix}", $defaultBody)
            : $defaultBody;

        $this->sendConfiguredRecoveryPhaseEmail(
            $user,
            $case,
            phaseName: $phaseName,
            previousPhase: $previousPhase,
            subject: $subject,
            body: $body,
        );
    }

    private function sendConfiguredRecoveryPhaseEmail(
        User $user,
        BitraceCase $case,
        string $phaseName,
        ?string $previousPhase,
        string $subject,
        string $body,
    ): void {
        $frontendUrl = rtrim((string) company_settings('front_end_url', config('app.url')), '/');
        $caseUrl = $frontendUrl.'/main/bitrace/cases/'.$case->id;
        $firstName = trim((string) ($user->first_name ?? $user->name ?? 'Client'));
        $siteName = get_site_name();

        $renderedSubject = $this->renderRecoveryPhaseTemplate($subject, $user, $case, $phaseName, $previousPhase, $caseUrl, $siteName);
        $renderedBody = $this->renderRecoveryPhaseTemplate($body, $user, $case, $phaseName, $previousPhase, $caseUrl, $siteName);

        (new NotificationService)->sendMail($user->email, [
            'title' => $renderedSubject,
            'template' => 'recovery_welcome',
            'user' => $user,
            'case' => $case,
            'customerName' => $firstName,
            'casesUrl' => $caseUrl,
            'message' => nl2br(e($renderedBody)),
        ]);
    }

    private function recoveryPhaseEmailSuffix(string $phaseName): ?string
    {
        return match (true) {
            strcasecmp($phaseName, RecoveryCaseStageService::CASE_OPENED) === 0 => 'case_opened',
            strcasecmp($phaseName, RecoveryCaseStageService::UNDER_REVIEW) === 0 => 'under_review',
            strcasecmp($phaseName, RecoveryCaseStageService::INVESTIGATION) === 0 => 'investigation',
            strcasecmp($phaseName, RecoveryCaseStageService::RECOVERY) === 0 => 'recovery',
            strcasecmp($phaseName, RecoveryCaseStageService::CLOSED) === 0 => 'closed',
            default => null,
        };
    }

    private function renderRecoveryPhaseTemplate(
        string $template,
        User $user,
        BitraceCase $case,
        string $phaseName,
        ?string $previousPhase,
        string $caseUrl,
        string $siteName,
    ): string {
        $placeholders = [
            '{{first_name}}' => trim((string) ($user->first_name ?? $user->name ?? 'Client')),
            '{{last_name}}' => trim((string) ($user->last_name ?? '')),
            '{{full_name}}' => trim((string) ($user->name ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')))),
            '{{email}}' => (string) ($user->email ?? ''),
            '{{case_id}}' => (string) ($case->id ?? ''),
            '{{case_number}}' => (string) ($case->case_number ?? ''),
            '{{phase}}' => $phaseName,
            '{{previous_phase}}' => (string) ($previousPhase ?? ''),
            '{{cases_url}}' => $caseUrl,
            '{{site_name}}' => $siteName,
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    private function buildDefaultWelcomeEmailData($user): array
    {
        $login = $this->getMailFrontendUrl();
        $siteName = get_site_name();
        $verification = $login.'/main/verification';

        return [
            'title' => "Welcome to {$siteName} – Your Trading Journey & Verification Process",
            'user' => $user,
            'message' => "<h2>Hello {$user->name}</h2>
We are excited to welcome you to <strong>{$siteName}</strong>, where your trading journey begins with confidence and opportunity. Thank you for registering with us. We are committed to supporting you as you work toward achieving your financial goals.<br/><br/>

As a {$siteName} client, you now have access to our cutting-edge trading platform, insightful market analysis, and our dedicated team of professionals, ready to assist you with informed trading decisions. Whether you're a seasoned trader or new to the markets, our platform is designed to provide the tools and resources you need to succeed.<br/><br/>

<a href=$login><strong>LOGIN HERE</strong></a><br/><br/>

<strong>Getting Started:</strong><br/>
 1. <strong>Login to Your Account:</strong> Simply visit the website URL provided to you by your assigned account manager and enter your credentials to access your account.<br/>
 2. <strong>Explore the Platform:</strong> Familiarize yourself with our intuitive trading interface and market insights.<br/>
 3. <strong>Deposit Funds:</strong> Easily deposit funds via Crypto and Bank Transfers to begin your trading activities.<br/><br/>

<strong>Verification Requirement:</strong><br/>

To ensure the security of your account, we require the following verification steps before you can begin trading:<br/>
 1. <strong>Identity Verification:</strong> A clear photo of a valid government-issued ID (passport, driver's license, etc.).<br/>
 2. <strong>Proof of Address:</strong> A recent utility bill, bank statement, or government document showing your name and address.<br/>
 3. <strong>Selfie:</strong> A recent selfie for identity verification.<br/><br/>

<a href=$verification><strong>VERIFY HERE</strong></a><br/>

Please ensure all documents are clear and legible. Once submitted, our team will process your documents promptly and notify you once verification is complete.<br/><br/>

<strong>Key Information:</strong><br/>
 - <strong>Customer Support:</strong> Our team is available 24/7 to assist with any questions you may have. Please feel free to reach out via live chat in your account portfolio.<br/><br/>

At {$siteName}, we prioritize security, transparency, and customer satisfaction. We are here to support you every step of the way.<br/><br/>

Once again, welcome to {$siteName}! We are thrilled to have you as part of our growing community and wish you success in your trading endeavors.

<br/>
<br/>
Cheers, <br/>
<strong>The $siteName Client Relation Team</strong>",
        ];
    }

    private function buildProWelcomeEmailData($user): array
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $frontendUrl = $this->getMailFrontendUrl();
        $chatUrl = $this->resolveProWelcomeChatUrl($frontendUrl);
        $depositUrl = $frontendUrl.'/main/dashboard?deposit';

        return [
            'title' => 'Your Trading Journey Starts Now | Fund Your Account Today',
            'user' => $user,
            'message' => "<h2>Hello {$firstName},</h2>
<p>We know starting something new can feel both exciting and overwhelming. At <strong>{$siteName}</strong>, we’re here to make sure your first steps into trading are simple, secure, and rewarding.</p>
<p>You’ve already opened the door to opportunity; now let’s help you step through it with confidence.</p>
<p><strong>&gt;&gt;Deposit today and begin trading immediately.</strong></p>
<p><strong>Here’s how easy it is:</strong></p>
<ul>
    <li>Log in to your client area.</li>
    <li>Click Deposit Funds.</li>
    <li>Choose Credit/Debit card or Cryptocurrency for instant processing.</li>
</ul>
<p>Once your deposit is confirmed, your account will be live and ready to trade across Forex, Stocks, Commodities, and Cryptocurrencies.</p>
<p>Encounter any challenge with your account? Chat with our Support Team via Whatsapp.</p>
<p><a href=\"{$chatUrl}\"><strong>CHAT SUPPORT</strong></a></p>
<p>Remember, every moment counts in the markets, and your journey to success starts with this first deposit.</p>
<p><a href=\"{$depositUrl}\"><strong>DEPOSIT NOW</strong></a></p>
<br/>
<p>Warm regards,</p>
<p><strong>{$siteName} Team</strong></p>",
        ];
    }

    private function resolveProWelcomeChatUrl(string $frontendUrl): string
    {
        $whatsAppEnabled = company_settings('enable_whatsapp_chat', 'no') === 'yes';
        $whatsAppNumber = (string) company_settings('whatsapp_number', company_settings('whatsap_number', ''));
        $normalizedNumber = preg_replace('/\D+/', '', $whatsAppNumber ?? '');

        if ($whatsAppEnabled && strlen((string) $normalizedNumber) > 7) {
            return 'https://wa.me/'.$normalizedNumber;
        }

        return $frontendUrl;
    }

    private function normalizeWelcomeMessageType(?string $type): string
    {
        return in_array($type, ['default', 'pro'], true) ? $type : 'default';
    }

    public function sendWithdrawalApprovalEmail($user, $amount): void
    {
        $site_name = get_site_name();
        $data['title'] = 'Withdrawal Confirmation';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$user->name}</h2>
We are pleased to inform you that your withdrawal of <strong>$amount</strong> from your trading account has been successfully processed.
<br/>
Thank you for being a part of $site_name!
<br/>
<br/>
<br/>
Cheers, <br/>
<strong>The $site_name Finance Team</strong>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);

    }

    public function sendDepositApprovalEmail($user, $amount, ?Transaction $transaction = null): void
    {
        $site_name = get_site_name();
        $orderIdHtml = $this->depositOrderIdHtml($transaction);
        $data['title'] = 'Deposit Confirmation – Successful Transaction';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$user->name}</h2>
We are pleased to inform you that your deposit of <strong>$amount</strong> has been successfully processed and is now available in your $site_name account. You can log in to your account to review your updated balance.

{$orderIdHtml}

<br/>
Thank you for being a part of $site_name!
<br/>
<br/>
<br/>
Cheers, <br/>
<strong>The $site_name Finance Team</strong>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);

    }

    public function sendPendingPaymentEmail($user, string $amountText, ?Transaction $transaction = null): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $orderIdHtml = $this->depositOrderIdHtml($transaction);

        $data['title'] = 'Payment Pending – Processing Your Transaction';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$firstName},</h2>
<p>We’ve received your payment request, and it is currently being processed.</p>
<p>The amount of <strong>{$amountText}</strong> is awaiting confirmation. Depending on your bank or payment method, this may take a short while to confirm. Please do not resend funds during this period.</p>
<p>You can track your transaction status directly in your {$siteName} account.</p>
{$orderIdHtml}
<br/>
<p>Thank you for your patience,</p>
<p><strong>The {$siteName} Team</strong></p>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);
    }

    public function sendKycRequiredAccountsAssistanceEmail($user, ?Transaction $transaction = null): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $orderIdHtml = $this->depositOrderIdHtml($transaction);

        $data['title'] = 'Identity Verification Required – Accounts Department Assistance';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$firstName},</h2>
<p>To comply with financial regulations, we require you to complete identity verification before continuing with trading, deposits, or withdrawals.</p>
<p><strong>Standby for the following:</strong></p>
<ol>
    <li>Our Accounts Department will contact you directly to guide you through the verification process.</li>
    <li>Please check your email inbox and messages regularly for instructions from the Accounts team.</li>
    <li>Once you receive guidance, follow the steps to upload the necessary documents securely.</li>
</ol>
{$orderIdHtml}
<p>Your security is our priority. We will never ask for your password, recovery phrase, or 2FA codes via email or phone.</p>
<br/>
<p>Thank you,</p>
<p><strong>{$siteName} Accounts Team</strong></p>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);
    }

    public function sendKycRequiredActionEmail($user, ?Transaction $transaction = null): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $verificationUrl = $this->getMailFrontendUrl().'/main/verification';
        $orderIdHtml = $this->depositOrderIdHtml($transaction);

        $data['title'] = 'Action Required – Verify Your Identity';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$firstName},</h2>
<p>To comply with financial regulations, we need you to complete identity verification before you can continue trading, depositing, or withdrawing funds.</p>
<p>Please upload the following documents:</p>
<ul>
    <li>A valid government-issued ID</li>
    <li>Proof of address</li>
</ul>
{$orderIdHtml}
<p>Submit using the secure link below. Until verification is complete, certain account features will remain paused.</p>
<p><a href=\"{$verificationUrl}\"><strong>COMPLETE VERIFICATION</strong></a></p>
<p>Your security is our priority. {$siteName} will never ask for your password or recovery phrase via email.</p>
<br/>
<p>Thank you,</p>
<p><strong>The {$siteName} Team</strong></p>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);
    }

    public function sendPaymentConfirmedEmail($user, string $amountText): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $transactionUrl = $this->getMailFrontendUrl().'/main/transactions';

        $data['title'] = 'Payment Successful – Funds Received';
        $data['user'] = $user;
        $data['message'] = "<h2>Hello {$firstName},</h2>
<p>Your payment has been successfully confirmed. The amount of <strong>{$amountText}</strong> has been credited to your {$siteName} account.</p>
<p>You can view the transaction details and your updated wallet balance.</p>
<p><a href=\"{$transactionUrl}\"><strong>VIEW TRANSACTION</strong></a></p>
<p>For your safety, please remember {$siteName} will never ask for your password or 2FA codes via email.</p>
<br/>
<p>Thank you for choosing {$siteName},</p>
<p><strong>The {$siteName} Team</strong></p>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data);
    }

    public function sendDepositKycRequiredOnApprovalIfNeeded(Transaction $transaction): void
    {
        $context = [
            'transaction_id' => $transaction->id,
            'company_id' => $transaction->company_id,
            'user_id' => $transaction->user_id,
            'transaction_status' => strtolower((string) ($transaction->status ?? '')),
            'is_deposit' => $transaction->isDeposit(),
        ];

        if (! $transaction->isDeposit()) {
            Log::info('deposit_kyc_required_on_approval.skip', array_merge($context, [
                'reason' => 'transaction_is_not_deposit',
            ]));

            return;
        }

        $kycSettingKey = $this->approvalKycSettingKey($transaction);
        $sendOnApproval = (string) company_settings($kycSettingKey, 'no');

        if ($sendOnApproval !== 'yes') {
            Log::info('deposit_kyc_required_on_approval.skip', array_merge($context, [
                'approval_kyc_setting_key' => $kycSettingKey,
                'send_kyc_required_on_approval' => $sendOnApproval,
                'reason' => 'setting_disabled',
            ]));

            return;
        }

        $user = $transaction->user;

        if (! $user) {
            Log::info('deposit_kyc_required_on_approval.skip', array_merge($context, [
                'approval_kyc_setting_key' => $kycSettingKey,
                'send_kyc_required_on_approval' => $sendOnApproval,
                'reason' => 'user_not_found',
            ]));

            return;
        }

        $userStatus = strtolower(trim((string) ($user->verification_status ?? '')));

        if (! $this->userNeedsDepositKycRequiredEmail($user)) {
            Log::info('deposit_kyc_required_on_approval.skip', array_merge($context, [
                'approval_kyc_setting_key' => $kycSettingKey,
                'send_kyc_required_on_approval' => $sendOnApproval,
                'user_verification_status' => $userStatus,
                'reason' => 'user_kyc_state_not_eligible',
            ]));

            return;
        }

        $template = (string) company_settings(
            'deposit_kyc_approval_message_template',
            company_settings('deposit_kyc_message_template', 'action_required_verify_identity')
        );

        Log::info('deposit_kyc_required_on_approval.send', array_merge($context, [
            'approval_kyc_setting_key' => $kycSettingKey,
            'send_kyc_required_on_approval' => $sendOnApproval,
            'user_verification_status' => $userStatus,
            'deposit_kyc_approval_message_template' => $template,
        ]));

        $this->sendConfiguredDepositKycTemplate($user, $template, $transaction);
    }

    private function sendConfiguredDepositKycTemplate($user, string $template, ?Transaction $transaction = null): void
    {
        match ($template) {
            'accounts_department_assistance',
            'Identity Verification Required – Accounts Department Assistance' => $this->sendKycRequiredAccountsAssistanceEmail($user, $transaction),
            default => $this->sendKycRequiredActionEmail($user, $transaction),
        };
    }

    public function sendAdminUnreadMessage(Message $message, $company_id): void
    {
        $user = $message->sender;
        $site_name = get_site_name();
        $admin_email = company_settings('admin_email_address', settings('admin_email_address'));

        if (! str_contains($admin_email, '@')) {
            return;
        }

        $chat_url = route('admin.chat_login');
        $data['title'] = "New unread message from {$user->name}";
        $data['user'] = $user;
        $data['message'] = "<h2>Hello</h2>
You have unread message from {$user->name}

<br/>
<br/>
<strong>Message:</strong><br/>
{$message->message}<br/><br/>
<strong>Sent at :</strong><br/>
{$message->created_at}<br/><br/>
<br/>
You can respond <a href=$chat_url><strong> HERE</strong></a><br/><br/>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($user->email, $data, $company_id);

    }

    public function sendAdminDepositNotification(Transaction $transaction, $company_id): void
    {
        $user = $transaction->user;

        $admin_email = company_settings('admin_email_address', settings('admin_email_address'));

        if (! str_contains($admin_email, '@')) {
            return;
        }

        $amt = format_price($transaction->amount);

        $url = route('admin.all_deposits');

        $data['title'] = "New deposit of $amt pending approval";
        $data['user'] = $user;
        $data['message'] = "<h2>Hello Admin</h2>
{$user->name} made a deposit of <strong>$amt</strong>

<br/>
<br/>

You can view all deposits <a href=$url><strong> HERE</strong></a><br/><br/>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($admin_email, $data, $company_id);

    }

    public function sendAdminWithdrawalNotification(Transaction $transaction, $company_id): void
    {
        $user = $transaction->user;

        $admin_email = company_settings('admin_email_address', settings('admin_email_address'));

        if (! str_contains($admin_email, '@')) {
            return;
        }

        $amt = format_price($transaction->amount);

        $url = route('admin.all_withdrawals');

        $data['title'] = "New withdrawal of $amt pending approval";
        $data['user'] = $user;
        $data['message'] = "<h2>Hello Admin</h2>
{$user->name} requested a withdrawal of <strong>$amt</strong>

<br/>
<br/>

You can view all withdrawal <a href=$url><strong> HERE</strong></a><br/><br/>";

        $notificationService = new NotificationService;

        $notificationService->sendMail($admin_email, $data, $company_id);

    }

    public function sendAdminAiTradeEnabledByCustomer(User $user): void
    {
        $adminEmail = $this->getAdminNotificationEmail();

        if (! $this->canSendAiTradeMail($user, 'customer_enabled_admin', $adminEmail)) {
            return;
        }

        $data = $this->buildAiTradeMailData(
            user: $user,
            enabled: true,
            audience: 'admin'
        );

        (new NotificationService)->sendMail($adminEmail, $data, $user->company_id);
    }

    public function sendAdminAiTradeDisabledByCustomer(User $user): void
    {
        $adminEmail = $this->getAdminNotificationEmail();

        if (! $this->canSendAiTradeMail($user, 'customer_disabled_admin', $adminEmail)) {
            return;
        }

        $data = $this->buildAiTradeMailData(
            user: $user,
            enabled: false,
            audience: 'admin'
        );

        (new NotificationService)->sendMail($adminEmail, $data, $user->company_id);
    }

    public function sendCustomerAiTradeEnabledByAdmin(User $user): void
    {
        if (! $this->canSendAiTradeMail($user, 'admin_enabled_customer', $user->email)) {
            return;
        }

        $data = $this->buildAiTradeMailData(
            user: $user,
            enabled: true,
            audience: 'customer'
        );

        (new NotificationService)->sendMail($user->email, $data, $user->company_id);
    }

    public function sendAiTradeDailySummary(User $user, array $summary, CarbonInterface $date, bool $force = false): void
    {
        if (! filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $companyId = $user->company_id ?? (app()->bound('company') ? app('company')->id : 'global');
        $cacheKey = "ai_trade_daily_summary:{$companyId}:{$user->id}:".$date->toDateString();

        if (! $force && ! Cache::add($cacheKey, true, now()->addDays(2))) {
            return;
        }

        if ($force) {
            Cache::put($cacheKey, true, now()->addDays(2));
        }

        $data = $this->buildAiTradeDailySummaryData($user, $summary, $date);

        (new NotificationService)->sendMail($user->email, $data, $user->company_id);
    }

    private function getRecipientFirstName($user): string
    {
        return trim((string) ($user->first_name ?: $user->name ?: 'there'));
    }

    private function getMailSiteName(): string
    {
        if (app()->bound('company')) {
            $company = app('company');

            return company_settings('site_name', $company->name ?? config('app.name'));
        }

        return config('app.name');
    }

    private function getMailFrontendUrl(): string
    {
        if (app()->bound('company')) {
            $company = app('company');

            return rtrim(
                company_settings('front_end_url', $company->url ?? config('app.url')),
                '/'
            );
        }

        return rtrim(config('app.url'), '/');
    }

    private function getMailSupportUrl(): string
    {
        $frontendUrl = $this->getMailFrontendUrl();
        $supportUrl = trim((string) company_settings('support_url', ''));

        if ($supportUrl !== '') {
            return $supportUrl;
        }

        return $this->resolveProWelcomeChatUrl($frontendUrl);
    }

    private function getMailLogoUrl(): string
    {
        $logo = (string) company_settings(
            'front_end_logo',
            company_settings('admin_logo', asset('images/logo.png'))
        );

        $logo = (string) getFullImage($logo);

        if ($logo === '') {
            return asset('images/logo.png');
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return str_starts_with($logo, '/')
            ? url($logo)
            : asset(ltrim($logo, '/'));
    }

    private function getAdminNotificationEmail(): ?string
    {
        $fallbackEmail = app()->bound('company') ? app('company')->email : settings('admin_email_address');
        $adminEmail = (string) company_settings('admin_email_address', $fallbackEmail);

        return filter_var($adminEmail, FILTER_VALIDATE_EMAIL) ? $adminEmail : null;
    }

    private function canSendAiTradeMail(User $user, string $actionKey, ?string $recipient): bool
    {
        if (! filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $companyId = $user->company_id ?? (app()->bound('company') ? app('company')->id : 'global');
        $cacheKey = "ai_trade_mail:{$companyId}:{$user->id}:{$actionKey}";

        return Cache::add($cacheKey, true, now()->addDay());
    }

    private function buildAiTradeMailData(User $user, bool $enabled, string $audience): array
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $statusLabel = $enabled ? 'Active' : 'Inactive';
        $accentText = $enabled ? 'Activated' : 'Deactivated';
        $variant = $enabled ? 'success' : 'warning';
        $isAdminAudience = $audience === 'admin';
        $actionDate = now()->format('M d, Y \a\t g:i A');
        $ctaUrl = $isAdminAudience
            ? url('/panel/users/'.$user->getRouteKey())
            : $this->getMailFrontendUrl().'/main/dashboard';

        $intro = match (true) {
            $isAdminAudience && $enabled => 'A client enabled AI Trading on their account.',
            $isAdminAudience => 'A client has turned off AI Trading on their account.',
            default => 'AI Trading has been enabled on your account by an administrator.',
        };

        $summary = match (true) {
            $isAdminAudience && $enabled => 'AI trading is now active for this account. You will receive notifications for key AI trading events.',
            $isAdminAudience => 'AI trading is now inactive for this account. You will receive notifications for key AI trading events.',
            default => 'AI trading is now active on your account. Visit your trading dashboard to review the latest activity.',
        };

        return [
            'template' => 'ai_trade',
            'title' => $isAdminAudience
                ? "AI Trading {$accentText} for {$user->name}"
                : 'AI Trading Activated on Your Account',
            'user' => $user,
            'site_name' => $siteName,
            'logo_url' => $this->getMailLogoUrl(),
            'support_url' => $this->getMailSupportUrl(),
            'support_email' => company_settings('support_email', app()->bound('company') ? app('company')->email : null),
            'variant' => $variant,
            'hero_title' => 'AI Trading',
            'hero_accent' => $accentText,
            'greeting' => "Hi {$firstName}",
            'intro' => $intro,
            'details_heading' => 'Client Details',
            'details_rows' => [
                ['label' => 'Status', 'value' => $statusLabel, 'accent' => true],
                ['label' => 'Client Name', 'value' => $user->name],
                ['label' => 'Trading Account', 'value' => $user->email],
                ['label' => 'Updated on', 'value' => $actionDate],
                ['label' => 'Trading Mode', 'value' => 'AI Assisted'],
                ['label' => 'Risk level', 'value' => 'Moderate'],
            ],
            'summary' => $summary,
            'cta_text' => $isAdminAudience ? 'View Client Activities' : 'View Trading Dashboard',
            'cta_url' => $ctaUrl,
            'footer_note' => "Trade on the go with {$siteName}",
        ];
    }

    private function buildAiTradeDailySummaryData(User $user, array $summary, CarbonInterface $date): array
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $pnl = (float) ($summary['pnl'] ?? 0);
        $isPositive = $pnl > 0;
        $isNegative = $pnl < 0;
        $variant = $isNegative ? 'warning' : 'success';
        $dayLabel = $isPositive ? 'Positive day' : ($isNegative ? 'Negative day' : 'Flat day');
        $changePercent = $summary['change_percent'] ?? null;
        $changeLabel = $changePercent === null
            ? 'No previous day comparison'
            : sprintf('%s%s%% from yesterday', $changePercent > 0 ? '+' : '', number_format((float) $changePercent, 1));

        return [
            'template' => 'ai_trade',
            'kind' => 'daily_summary',
            'title' => 'Daily AI Trading Summary',
            'user' => $user,
            'site_name' => $siteName,
            'logo_url' => $this->getMailLogoUrl(),
            'support_url' => $this->getMailSupportUrl(),
            'support_email' => company_settings('support_email', app()->bound('company') ? app('company')->email : null),
            'variant' => $variant,
            'hero_title' => 'Daily AI',
            'hero_accent' => 'Trading Summary',
            'greeting' => "Hi {$firstName}",
            'intro' => "Here's how your AI strategy performed today.",
            'summary_date' => $date->format('M d, Y'),
            'performance_heading' => "Today's performance AI",
            'performance_value' => format_price($pnl),
            'performance_value_color' => $isNegative ? '#d64545' : '#111827',
            'performance_delta' => $changeLabel,
            'performance_delta_color' => $isNegative ? '#d64545' : '#16b66a',
            'performance_badge' => $dayLabel,
            'performance_badge_background' => $isNegative ? '#fce8e8' : '#e6f8ef',
            'performance_badge_color' => $isNegative ? '#c0392b' : '#16a765',
            'stats_cards' => [
                ['value' => (string) ($summary['total_trades'] ?? 0), 'label' => 'Total trades'],
                ['value' => (string) ($summary['win_rate'] ?? '0%'), 'label' => 'Win Rate'],
                ['value' => (string) ($summary['best_pair'] ?? '--'), 'label' => 'Best pair'],
                ['value' => 'Pro', 'label' => 'Strategy'],
            ],
            'insight_title' => 'AI Insight',
            'insight_body' => (string) ($summary['insight'] ?? 'AI Trading monitored the market today and remained selective about entries.'),
            'transactions_heading' => 'Transactions',
            'transactions' => $summary['transactions'] ?? [],
            'cta_text' => 'View Full Trading Report',
            'cta_url' => $this->getMailFrontendUrl().'/main/dashboard',
            'footer_note' => "Trade on the go with {$siteName}",
        ];
    }

    private function userNeedsDepositKycRequiredEmail($user): bool
    {
        $status = strtolower(trim((string) ($user->verification_status ?? '')));

        return ! in_array($status, ['approved', 'completed'], true);
    }

    private function approvalKycSettingKey(Transaction $transaction): string
    {
        $method = strtolower(trim((string) ($transaction->method ?? '')));

        return $method === 'crypto'
            ? 'send_crypto_deposit_kyc_on_approval'
            : 'send_card_deposit_kyc_on_approval';
    }

    private function depositOrderIdHtml(?Transaction $transaction = null): string
    {
        if (! $transaction?->order_id) {
            return '';
        }

        return "<p><strong>Order ID:</strong> {$transaction->order_id}</p>";
    }

    public function sendCredentialsEmail($user, string $plainPassword, $language = 'en', string $autoLoginToken = '', ?string $logoUrl = null): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $startUrl = $this->getMailFrontendUrl();
        $supportEmail = company_settings('support_email', '');
        $autoLoginUrl = $autoLoginToken
            ? "{$startUrl}/auto-login?token={$autoLoginToken}"
            : $startUrl;
        $whatsAppNumber = (string) company_settings('whatsapp_number', company_settings('whatsap_number', ''));
        $normalizedWhatsApp = preg_replace('/\D+/', '', $whatsAppNumber ?? '');
        $supportUrl = (strlen((string) $normalizedWhatsApp) > 7)
            ? 'https://wa.me/'.$normalizedWhatsApp
            : ($supportEmail ? "mailto:{$supportEmail}" : $startUrl);

        // Figma "LiveTrader - Email Templates" design (node 82:1023), rendered via
        // mail.campaign-welcome. The design's logo placeholder is the campaign's
        // own logo, not the tenant/company logo -- callers pass it in as $logoUrl.
        if ($language === 'fr') {
            $data = [
                'template' => 'campaign_welcome',
                'title' => "Bienvenue chez {$siteName}. Votre compte est prêt",
                'user' => $user,
                'logo_url' => $logoUrl,
                'site_name' => $siteName,
                'heading' => 'Bienvenue, ravis de vous rencontrer',
                'greeting' => "Bonjour {$firstName},",
                'body_text' => "Votre compte a été créé avec succès et vous êtes prêt à commencer. Alimentez votre compte, explorez les marchés en direct et accédez à des outils de trading puissants conçus pour vous aider à trader en toute confiance.",
                'username_label' => "Nom d'utilisateur :",
                'email' => $user->email,
                'password_label' => 'Mot de passe :',
                'plain_password' => $plainPassword,
                'cta_text' => 'Commencer à trader →',
                'cta_url' => $autoLoginUrl,
                'frontend_url' => $startUrl,
                'support_url' => $supportUrl,
            ];
        } else {
            $data = [
                'template' => 'campaign_welcome',
                'title' => "Welcome to {$siteName}. Your Account Is Ready",
                'user' => $user,
                'logo_url' => $logoUrl,
                'site_name' => $siteName,
                'heading' => "Welcome, We're pleased to meet you",
                'greeting' => "Hi {$firstName},",
                'body_text' => "Your account has been successfully created and you're ready to get started. Fund your account, explore live markets, and access powerful trading tools designed to help you trade with confidence.",
                'username_label' => 'Username:',
                'email' => $user->email,
                'password_label' => 'Password:',
                'plain_password' => $plainPassword,
                'cta_text' => 'Start Trading →',
                'cta_url' => $autoLoginUrl,
                'frontend_url' => $startUrl,
                'support_url' => $supportUrl,
            ];
        }

        (new NotificationService)->sendMail($user->email, $data);
    }

    public function sendTemplateEmail($user, string $plainPassword, $template, string $autoLoginToken = ''): void
    {
        $siteName = $this->getMailSiteName();
        $firstName = $this->getRecipientFirstName($user);
        $startUrl = $this->getMailFrontendUrl();
        $supportEmail = company_settings('support_email', '');
        $autoLoginUrl = $autoLoginToken ? "{$startUrl}/auto-login?token={$autoLoginToken}" : $startUrl;

        $placeholders = [
            '{{first_name}}' => $firstName,
            '{{email}}' => $user->email,
            '{{password}}' => $plainPassword,
            '{{login_url}}' => $autoLoginUrl,
            '{{site_name}}' => $siteName,
            '{{support_email}}' => $supportEmail,
        ];

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $template->subject);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $template->body);

        $data = [
            'title' => $subject,
            'user' => $user,
            'message' => $body,
        ];

        (new NotificationService)->sendMail($user->email, $data);
    }

    public function sendPlatformUrls($company): void
    {
        $user = $company->owner;

        $data = [
            'title' => 'New Platform Creation Alert',
            'user' => $user,
            'customerName' => $this->getRecipientFirstName($user),
            'clientAreaUrl' => company_dashboard_url($company),
            'backOfficeUrl' => company_admin_url($company),
            'tradeRoomUrl' => company_webtrader_url($company),
            'landingPageUrl' => company_settings('front_end_url', $company->url ?? config('app.url')),
            'startUrl' => $this->getMailFrontendUrl(),
            'supportEmail' => company_settings('support_email', ''),
            'logoUrl' => $this->getMailLogoUrl(),
            'supportUrl' => $this->getMailSupportUrl(),
        ];

        Mail::to($user->email)->send(new PlatformCreatedMail($data, $company->id));
    }

    public function sendDomainUrls($company, $domain): void
    {
        $siteName = $this->getMailSiteName();
        $user = $company->owner;

        $firstName = $this->getRecipientFirstName($user);
        $startUrl = $this->getMailFrontendUrl();
        $supportEmail = company_settings('support_email', '');

        $scheme = config('platform.url_scheme', 'https');
        $domainUrls = collect(config('platform.urls', []))
            ->map(fn (array $config) => [
                'title' => $config['title'],
                'url' => sprintf(
                    '%s://%s.%s',
                    $scheme,
                    $config['prefix'],
                    $domain
                ),
            ])
            ->all();

        $data = [
            'title' => "{$siteName} Domain Connection Alert",
            'user' => $user,
            'customerName' => $firstName,
            'clientAreaUrl' => $domainUrls['client_area']['url'],
            'backOfficeUrl' => $domainUrls['crm_backoffice']['url'],
            'tradeRoomUrl' => $domainUrls['webtrader']['url'],
            'landingPageUrl' => "{$scheme}://{$domain}",
            'startUrl' => $startUrl,
            'supportEmail' => $supportEmail,
            'logoUrl' => $this->getMailLogoUrl(),
            'supportUrl' => $this->getMailSupportUrl(),
        ];

        Mail::to($user->email)->send(new DomainConnectMail($data, $company->id));
    }

    public function sendWelcomeEmailV2($user, string $plainPassword, string $autoLoginToken = '', $isPro = false): void
    {
        $startUrl = $this->getMailFrontendUrl();
        $autoLoginUrl = $autoLoginToken
            ? "{$startUrl}/auto-login?token={$autoLoginToken}"
            : $startUrl;

        $data = [
            'customerName' => $this->getRecipientFirstName($user),
            'accountUsername' => $user->email,
            'accountPassword' => $plainPassword,
            'ctaUrl' => $autoLoginUrl,
        ];

        if ($isPro) {
            Mail::to($user->email)->send(new WelcomeProMail($data));
        } else {
            Mail::to($user->email)->send(new WelcomeMail($data));
        }
    }
}

<x-splade-input name="name" label="Campaign Name" required />
<x-splade-input name="owner" label="Owner" />
<x-splade-select label="Origin" name="origin" :options="array_combine(get_origins(), get_origins())" placeholder="Where does the lead list come from?" />
<x-splade-select label="Source" name="source" required :options="array_combine(get_sources(), get_sources())" />
<x-splade-select label="Language" name="language" required :options="['English' => 'English', 'French' => 'French']" />
<x-splade-input type="tel" name="whatsapp_number" label="Whatsapp Number" />
<x-splade-textarea name="whatsapp_message" label="Whatsapp Message" />
<x-splade-input name="cold_lead_chat_id" label="Cold Lead Telegram Chat ID" />
<x-splade-input name="warm_lead_chat_id" label="Warm Lead Telegram Chat ID" />
<x-splade-input name="hot_lead_chat_id" label="Hot Lead Telegram Chat ID" />
<x-splade-input name="tags" label="Tags" />
<x-splade-input name="notes" label="Notes" />
<x-splade-input name="metadata" label="Metadata" />

<div class="mt-4">
    <x-splade-select
        label="Attach Product (optional)"
        name="product_id"
        placeholder="No product attached"
        :options="\App\Models\Product::where('is_active', true)->pluck('name', 'id')->toArray()"
    />
</div>

<div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-5 space-y-3">
    <h4 class="font-semibold text-gray-800">Use New Survey</h4>
    <p class="text-sm text-gray-500">When enabled, new survey design will be used for collecting leads.</p>
    <x-splade-checkbox name="use_new_survey" value="1" label="Use New Survey" />
</div>

<div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-5 space-y-3">
    <h4 class="font-semibold text-gray-800">Third Party Integration</h4>
    <p class="text-sm text-gray-500">When enabled, leads captured via the web form survey will be sent to the third party API.</p>
    <x-splade-checkbox name="send_to_third_party" value="1" label="Send leads to third party API" />
</div>

<div class="mt-4 rounded-xl border bg-slate-50 p-5 space-y-4">
    <h4 class="font-semibold text-gray-800">Welcome Message</h4>
    <x-splade-select
        label="Email Template"
        name="welcome_message_id"
        :options="$welcomeMessages->pluck('name', 'id')->toArray()"
        placeholder="Select a template (optional)"
    />
    <x-splade-checkbox name="welcome_message_enabled" value="1" label="Send welcome email to leads" />
</div>
<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-3">
    <h4 class="font-semibold text-gray-800">End Zone</h4>
    <p class="text-sm text-gray-500">What happens after the lead submits the form?</p>
    <x-splade-select
        name="end_zone"
        label="Destination"
        :options="['platform' => 'Auto-login to Trading Platform', 'thank_you' => 'Thank You Page']"
    />
</div>

<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-3">
    <h4 class="font-semibold text-gray-800">Tracking Script</h4>
    <p class="text-sm text-gray-500">
        Paste a Facebook/Meta Pixel snippet (or any other tracking script) here. It runs on this campaign's own
        landing page only, so each campaign can be tracked as a separate ad conversion event.
    </p>
    <x-splade-textarea
        name="tracking_script"
        label="Tracking Script"
        rows="8"
        placeholder="<script>...</script>"
        class="font-mono text-xs"
    />
</div>

<div class="flex flex-col items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
    <ImageUpload
        button-id="campaign_logo"
        input-id="fileInput"
        preview-id="filePreview"
        v-model="form.campaign_logo"
        button-text="Select File"
        type="image"
    >
        <p>
            Campaign Logo <br />
            <span class="text-xs text-gray-500">Max file size: 3MB</span>
        </p>
    </ImageUpload>
</div>

<div class="flex flex-col items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
    <ImageUpload
        button-id="campaign_favicon"
        input-id="fileInput"
        preview-id="filePreview"
        v-model="form.campaign_favicon"
        model-value="{{ $campaign->campaign_favicon ?? '' }}"
        button-text="Select File"
        type="image"
    >
        <p>
            Campaign Favicon <br />
            <span class="text-xs text-gray-500">Max file size: 2MB</span>
        </p>
    </ImageUpload>
</div>

<div class="flex flex-col items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
    <ImageUpload
        button-id="campaign_favicon"
        input-id="faviconFileInput"
        preview-id="faviconFilePreview"
        v-model="form.campaign_favicon"
        button-text="Select File"
        type="image"
    >
        <p>Campaign Favicon</p>
        <p class="text-xs text-gray-500">Falls back to the tenant's default favicon when not set.</p>
    </ImageUpload>
</div>

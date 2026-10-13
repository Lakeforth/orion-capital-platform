<x-admin-layout>
    <x-slot name="header">

        @include('admin.settings.partials')
    </x-slot>


    <div>
        <x-splade-form
            id="settings-form"
            :default="\App\Support\SettingsForm::data($settings, 'admin.settings.index')"
            action="{{ route('admin.settings.store') }}"
            class="bg-white rounded-lg shadow overflow-hidden"
        >
            <div class="py-4">
                <div class="border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 border-collapse">
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Frontend Logo</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <ImageUpload
                                    button-id="front_end_logo"
                                    input-id="fileInput"
                                    preview-id="filePreview"
                                    v-model="form.front_end_logo"
                                    button-text="Select File"
                                    class="flex"
                                    type="image"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Client Area Login Logo</label>
                                <p class="mt-1 text-xs font-normal text-gray-500">Used only on the client area sign-in page. Leave empty to use the Frontend Logo.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <ImageUpload
                                    button-id="login_logo"
                                    input-id="fileInput"
                                    preview-id="filePreview"
                                    v-model="form.login_logo"
                                    button-text="Select File"
                                    class="flex"
                                    type="image"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Admin Logo</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <ImageUpload
                                    button-id="admin_logo"
                                    input-id="fileInput"
                                    preview-id="filePreview"
                                    v-model="form.admin_logo"
                                    button-text="Select File"
                                    class="flex"
                                    type="image"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Small Logo</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <ImageUpload
                                    button-id="small_logo"
                                    input-id="fileInput"
                                    preview-id="filePreview"
                                    v-model="form.small_logo"
                                    button-text="Select File"
                                    class="flex"
                                    type="image"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Favicon</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <ImageUpload
                                    button-id="favicon"
                                    input-id="fileInput"
                                    preview-id="filePreview"
                                    v-model="form.favicon"
                                    button-text="Select File"
                                    class="flex"
                                    type="image"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Site Name</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="site_name" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Site URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="site_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Front-end URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="front_end_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Webtrader URL</label>
                                <p class="mt-1 text-xs text-gray-500">Leave empty to use the active webtrader domain.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input
                                    name="webtrader_url"
                                    type="url"
                                    placeholder="https://webtrader.yourdomain.com"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Chat URL (leave empty to use frontend url)</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="chat_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">CRM URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="crm_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">GetResponse API Key</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input
                                    name="get_response_api_key"
                                    placeholder="null"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">GetResponse Campaign ID</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input
                                    name="get_response_campaign_id"
                                    placeholder="null"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Privacy Policy URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="privacy_policy_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Terms &amp; Conditions URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="terms_and_condition_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Support Email</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="support_email" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Support URL</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="support_url" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Plan Limit Message</label>
                                <p class="text-xs text-gray-400 mt-1">Shown to customers when a plan limit is reached (e.g. max open trades). Defaults to "Please contact support."</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="plan_limit_message" class="w-full" placeholder="Please contact support." />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Contact Phone</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="contact_phone" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MailWizz List UID</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="mailwizz_list_uid" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="whatsap_number" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Enable WhatsApp Chat</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-checkbox name="enable_whatsapp_chat" value="yes" false-value="no" toggle />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Enable Plan</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-checkbox name="enable_plan" value="yes" false-value="no" toggle />
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Enable Autotrader</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-checkbox name="enable_autotrader" value="yes" false-value="no" toggle />
                            </td>
                        </tr>

                        @if(module_enabled('propfirm_enabled'))
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Prop Firm Sidebar Color</label>
                                <p class="text-xs text-gray-400 mt-1">Background color of the client area sidebar.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="propfirm_sidebar_color" type="color" class="w-20 h-10 p-1" />
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Prop Firm Accent Color</label>
                                <p class="text-xs text-gray-400 mt-1">Used for primary buttons and highlighted nav items across the client area.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input name="propfirm_accent_color" type="color" class="w-20 h-10 p-1" />
                            </td>
                        </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </x-splade-form>
    </div>

</x-admin-layout>

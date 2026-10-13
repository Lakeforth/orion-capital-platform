<x-admin-layout>
    <x-slot name="header">

        @include('admin.settings.partials')
    </x-slot>


    <div>
        <x-splade-form
            id="settings-form"
            :default="\App\Support\SettingsForm::data($settings, 'admin.settings.page')"
            action="{{ route('admin.settings.store') }}"
            class="bg-white rounded-lg shadow overflow-hidden"
        >
            <div class="py-4">
                <div class="border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 border-collapse">
                        <tbody class="bg-white divide-y divide-gray-200">


                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Login Page Design</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="login_page_design"
                                    :options="login_page_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Register Page Design</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="register_page_design"
                                    :options="register_page_design()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Admin CRM Login Design</label>
                                <p class="mt-1 text-xs text-gray-400">This tenant's own /login page (backoffice), not the client-facing login above.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="admin_login_design"
                                    :options="admin_login_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Forgot Password Page Design</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="forgot_password_page_design"
                                    :options="forgot_password_page_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Webtrader Login Page Design</label>
                                <p class="mt-1 text-xs text-gray-400">The standalone trade-room subdomain's own login page, not the client-facing login above.</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="webtrader_login_page_design"
                                    :options="webtrader_login_page_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Dashboard Page Design</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="dashboard_page_design"
                                    :options="dashboard_page_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Admin Dashboard</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="admin_dashboard"
                                    :options="admin_dashboard_designs()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Login Page Header</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-textarea name="login_page_header" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Login Page Footer</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-textarea name="login_page_footer" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Register Page Header</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-textarea name="register_page_header" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Register Page Footer</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-textarea name="register_page_footer" class="w-full" />
                            </td>
                        </tr>


                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">Country Page Footer</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-textarea name="country_page_footer" class="w-full" />
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </x-splade-form>
    </div>

</x-admin-layout>

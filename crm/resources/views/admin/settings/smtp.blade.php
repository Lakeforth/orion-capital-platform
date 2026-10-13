<x-admin-layout>
    <x-slot name="header">

        @include('admin.settings.partials')
    </x-slot>


    <div>
        <x-splade-form
            id="settings-form"
            :default="$settings"
            action="{{ route('admin.settings.store.smtp') }}"
            class="bg-white rounded-lg shadow overflow-hidden"
        >
            <div class="py-4">
                <div class="border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 border-collapse">
                        <tbody class="bg-white divide-y divide-gray-200">

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MAIL_HOST</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required placeholder="smtp.mailgun.org" name="mail_host" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    MAIL_PORT</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required type="number" placeholder="578" name="mail_port" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MAIL_ENCRYPTION</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required placeholder="SSL" name="mail_encryption" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MAIL_USERNAME</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required name="mail_username" class="w-full" />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MAIL_PASSWORD</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required name="mail_password" class="w-full" />
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">MAIL_FROM_ADDRESS</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input required type="email" name="mail_from_address" class="w-full" />
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </x-splade-form>


        <x-splade-form
            id="settings-form"
            action="{{ route('admin.settings.test_smtp') }}"
            class="bg-white mt-2 rounded-lg shadow overflow-hidden"
        >
            <div class="py-4">
                <div class="">
                    <table class="min-w-full divide-y divide-gray-200 border-collapse">
                        <tbody class="bg-white divide-y divide-gray-200">

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">TEST SMTP</label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <div class="flex gap-3 justify-between-">
                                    <x-splade-input required placeholder="admin@example.com" name="email" class="w-full-" />

                                    <x-splade-submit label="Test SMTP" />


                                </div>
                            </td>

                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-splade-form>

    </div>

</x-admin-layout>


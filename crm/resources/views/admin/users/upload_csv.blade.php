<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="6xl" close-explicitly>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-100 p-6 rounded-lg">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight mb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Bulk Upload {{ ucfirst($default['role'] ?? 'Users') }}
            </h2>
            <p class="text-gray-600">Upload multiple users at once using a CSV or Excel file</p>
        </div>

        <div class="py-8">
            <!-- Instructions Card -->
            <div class="mb-8 bg-white border-2 border-dashed border-gray-300 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    File Requirements (CSV/Excel)
                </h3>

                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Required Headers (Case Sensitive):</h4>
                        <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <code class="bg-gray-100 px-2 py-1 rounded font-mono">email</code>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <code class="bg-gray-100 px-2 py-1 rounded font-mono">phone</code>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <code class="bg-gray-100 px-2 py-1 rounded font-mono">first_name</code>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <code class="bg-gray-100 px-2 py-1 rounded font-mono">last_name</code>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-700 mb-1">Optional Headers:</h4>

                        <div class="mr-2 mb-2">
                                    label,brand,campaign,country
                        </div>
                        <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>

                                <code class="bg-gray-100 px-2 py-1 rounded font-mono">password</code>
                                <span class="ml-2 text-xs text-gray-500">(auto-generated if empty)</span>

                            </div>

                        </div>
                    </div>
                </div>




                <!-- Upload Form -->
                <x-splade-form
                    :default="$default"
                    class="space-y-6 mt-3"
                    action="{{ route('admin.users.upload_csv') }}"
                    method="POST"
                    enctype="multipart/form-data"
                >
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select CSV or Excel File
                        </label>

                        <x-splade-file
                            name="csv_file"
                            required
                            filepond
                            max-size="2MB"
                            class="w-full"
                        />
                    </div>

                    <x-splade-input type="hidden" name="role" />


                    <div class="flex items-center justify-between pt-4 border-t">
                        <div class="text-sm text-gray-500">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Make sure your file follows the exact format below
                        </div>

                        <x-splade-submit  class="mt-1" label="Upload & Process File" />

                    </div>
                </x-splade-form>

                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-medium text-blue-800 mb-2">Sample Format:</h4>
                    <div class="bg-white p-3 rounded border overflow-x-auto">
                        <pre class="text-xs font-mono text-gray-700">email,phone,first_name,last_name,password
john.doe@example.com,+1234567890,John,Doe,mypassword123
jane.smith@example.com,+0987654321,Jane,Smith,</pre>
                    </div>
                    <p class="text-xs text-blue-600 mt-2">💡 Same format applies to Excel files (.xlsx, .xls) - just use the first worksheet</p>
                </div>

                <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                    <h4 class="font-medium text-yellow-800 mb-2">Important Notes:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Email addresses must be unique and valid</li>
                        <li>• Phone numbers must be unique</li>
                        <li>• If password is empty, a default password will be assigned</li>
                        <li>• All users will be assigned the role: <strong>{{ ucfirst($default['role'] ?? 'user') }}</strong></li>
                        <li>• Supported formats: CSV (.csv), Excel (.xlsx, .xls)</li>
                        <li>• Maximum file size: 2MB</li>
                        <li>• For Excel files: Only the first worksheet will be processed</li>
                    </ul>
                </div>
            </div>

        </div>
    </x-splade-modal>
</x-admin-layout>

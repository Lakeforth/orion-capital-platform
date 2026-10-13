<x-guest-layout>
    <x-vendor-card>

        <x-splade-form class="space-y-2" method="POST" action="{{ route('vendor-signup') }}">

            <div class=" gap-4">
                <div class="p-4 text-center">
                    <p class="text-center text-2xl">Get started as a vendor</p>
                    <p class="mt-3 text-center">complete the form below to join Queek Vendors Platform</p>

                    <Link class="underline text-center mt-2 text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                        Login if already have account
                    </Link>
                </div>
                <div class="space-y-3  p-1">

                    <x-splade-input type="text" required label="Business Name" name="name" />
                    <x-splade-input type="text" label="Slogan" name="slogan" />


                    <x-splade-input  required label="Address" name="address" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">


                    <x-splade-select choices :options="$regions" label="Region" name="region_id" />


                    <x-splade-select required choices option-label="name"
                                     option-value="name" label="Area"
                                     remote-root="data"
                                     remote-url="`/api/areas/${form.region_id}`"
                                     name="area" />
                    </div>

                    <hr/>
                    <h3 class="font-bold">Store Login Detail</h3>
                    <hr/>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-splade-input required label="Email" name="email" />
                        <div class="relative">
                            <x-splade-input id="vendor_password" type="password" required label="Password" name="password" class="pr-12" />
                            <button type="button"
                                    data-toggle-password
                                    data-target="vendor_password"
                                    aria-label="Toggle password visibility"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800">
                                <svg class="h-5 w-5 toggle-eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg class="hidden h-5 w-5 toggle-eye-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.477 10.48a3 3 0 014.243 4.243M9.88 9.88a3 3 0 00-.707 3.247M6.228 6.228C4.33 7.538 2.933 9.534 2.458 12c1.274 4.057 5.064 7 9.542 7a9.96 9.96 0 004.772-1.183M15.53 15.53A9.957 9.957 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.18-3.568" />
                                </svg>
                            </button>
                        </div>

                        <x-splade-input required label="Phone" name="phone" />
                        <x-splade-input required label="First Name" name="first_name" />
                        <x-splade-input required label="Last Name" name="last_name" />

                        <x-splade-input required label="Username" name="username" />
                    </div>

                    <x-splade-submit class="mt-3 bg-primary text-white"  label="Submit" />


                </div>



            </div>

        </x-splade-form>

    </x-vendor-card>

    <x-splade-script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                const openIcon = button.querySelector('.toggle-eye-open');
                const closedIcon = button.querySelector('.toggle-eye-closed');
                if (openIcon && closedIcon) {
                    openIcon.classList.toggle('hidden', !showing);
                    closedIcon.classList.toggle('hidden', showing);
                }
            });
        });
    </x-splade-script>
</x-guest-layout>

<x-admin-layout>

    <x-card>

        <div class="p-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profile Information') }}
            </h2>




            <section>
                <header>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __("Update your account's profile information") }}
                    </p>
                </header>

                <x-splade-form method="patch" :action="route('admin.users.update', $user->id)" :default="$user" class="mt-6 space-y-6" preserve-scroll>
                    <x-splade-input class="" id="name" name="first_name" type="text" :label="__('First Name')" required autofocus autocomplete="name" />
                    <x-splade-input class="" id="last_name" name="last_name" type="text" :label="__('Last Name')" />
                    <x-splade-input class="" id="phone" name="phone" type="text" :label="__('Phone')" />
                    <x-splade-input disabled id="email" class="text-black-2" name="email" type="email" :label="__('Email')" required autocomplete="email" />


                    {{--        <img src="{{ auth()->user()->signature }}" />--}}

                        <ImageUpload
                            button-id="avatar"
                            input-id="fileInput"
                            preview-id="filePreview"
                            v-model="form.avatar"
                            model-value="{{ $user->avatar }}"
                            button-text="Select File"
                            type="image"
                        >
                            <p>Profile Pic</p>
                        </ImageUpload>



                    <div class="flex items-center gap-4">
                        <x-splade-submit :label="__('Save')" />

                        @if (session('status') === 'profile-updated')
                            <p class="text-sm text-gray-600">
                                {{ __('Saved.') }}
                            </p>
                        @endif
                    </div>
                </x-splade-form>
            </section>


        </div>
    </x-card>
</x-admin-layout>

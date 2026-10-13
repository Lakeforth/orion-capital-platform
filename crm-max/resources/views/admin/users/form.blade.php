@if($method == 'store' && isMini())
    <div class="mini-add-modal-grid grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-splade-input required label="First Name" name="first_name" placeholder="Enter First Name" />
        <x-splade-input required label="Last Name" name="last_name" placeholder="Enter Last Name" />

        <x-splade-input type="email" required label="Email" name="email" placeholder="Enter Email" />
        <x-splade-select choices label="Country" name="country" :options="$countryOptions" placeholder="Select Country" />

        <x-splade-input type="number" required label="Mobile Number" name="phone" placeholder="Enter Mobile Number" />
        <x-splade-input label="Address" name="address" placeholder="Enter Address" />

        <x-splade-input label="Permanent Address" name="city" placeholder="Enter Permanent Address" />
        <x-splade-select label="Source" name="source" :options="array_combine(get_sources(), get_sources())" placeholder="Select Source" />

        @if(hasPlan() && is_admin())
            <x-splade-select label="Account Plan" name="plan_id" remote-url="{{ route('api.account_plans.index') }}"
                              option-label="title" option-value="id" />
        @endif

        <x-splade-input label="Account ID" name="account_id" placeholder="Enter Account ID" />

        <div class="relative">
            <x-splade-input id="create_password" class="pr-12" type="password" required label="Password" name="password" placeholder="Enter Password" data-lm-pw-generate />
            <button
                type="button"
                data-toggle-password
                data-target="create_password"
                aria-label="Toggle password visibility"
                class="absolute right-3 top-[37px] text-gray-500 hover:text-gray-700"
            >
                {{ svg('heroicon-o-eye', 'h-5 w-5') }}
            </button>
        </div>
        <div class="relative">
            <x-splade-input id="create_password_confirmation" class="pr-12" type="password" label="Confirm Password" name="password_confirmation" placeholder="Confirm Password" />
            <button
                type="button"
                data-toggle-password
                data-target="create_password_confirmation"
                aria-label="Toggle password visibility"
                class="absolute right-3 top-[37px] text-gray-500 hover:text-gray-700"
            >
                {{ svg('heroicon-o-eye', 'h-5 w-5') }}
            </button>
        </div>
    </div>

    <x-splade-input type="hidden" required label="Role" name="role" />
@else
    @if($method == 'store')
        <div class="grid grid-cols-2 gap-2">
            <x-splade-input required label="First Name" name="first_name" />
            <x-splade-input required label="Last Name" name="last_name" />
        </div>
    @else
        @if(is_admin() || !module_enabled('limited_actions'))
            <div class="grid grid-cols-2 gap-2">
                <x-splade-input required label="First Name" name="first_name" />
                <x-splade-input required label="Last Name" name="last_name" />
            </div>
        @else
            <x-splade-input type="hidden" name="first_name" />
            <x-splade-input type="hidden" name="last_name" />
        @endif

    @endif


    {{--<div class="grid grid-cols-2 gap-2">--}}
    {{--    --}}
    {{--    <x-splade-input date required label="Date of Birth" name="date_of_birth" />--}}
    {{--    <x-splade-select required label="gender" name="gender">--}}
    {{--        <option value="male">Male</option>--}}
    {{--        <option value="female">Female</option>--}}
    {{--    </x-splade-select>--}}
    {{--</div>--}}
    <div class="grid grid-cols-2 gap-2">
        @if($method == 'store')
            <x-splade-input type="email" required  label="Email" name="email" />
            <x-splade-select choices label="Country" name="country" :options="$countryOptions ?? []" placeholder="Select Country" />

            <x-splade-input type="number" required label="Phone" name="phone" />


        @else
            <x-splade-input  disabled label="Email" name="email" />
            <x-splade-select choices label="Country" name="country" :options="$countryOptions ?? []" placeholder="Select Country" />

        @if(is_admin() || !module_enabled('limited_actions'))
                <x-splade-input type="number" required label="Phone" name="phone" />
            @else
                <x-splade-input type="hidden" name="phone" />
        @endif

        @endif
    {{--    <x-splade-input required label="Username" name="username" />--}}

    </div>

    <div class="grid grid-cols-2 gap-2">


        @if(hasPlan() && is_admin())

            <x-splade-select v-if="form.role == 'customer' || form.role == 'user'" label="Account Plan" name="plan_id" remote-url="{{ route('api.account_plans.index') }}"
                              option-label="title" option-value="id" />


        @endif
            <x-splade-input v-if="form.role == 'customer' || form.role == 'user'" label="Account ID" name="account_id" />

            <x-splade-input v-if="form.role == 'customer' || form.role == 'user'" type="number" step="0.01" min="0"
                              label="Spread Multiplier" name="spread_multiplier"
                              placeholder="1.00 = normal, 2.00 = double (blank = tenant default)" />



        @if($method == 'store')
        <div class="relative">
            <x-splade-input id="create_password_default" class="pr-12" type="password" required label="Password" name="password" data-lm-pw-generate />
            <button
                type="button"
                data-toggle-password
                data-target="create_password_default"
                aria-label="Toggle password visibility"
                class="absolute right-3 top-[37px] text-gray-500 hover:text-gray-700"
            >
                {{ svg('heroicon-o-eye', 'h-5 w-5') }}
            </button>
        </div>
        @else
            @if($user->role != 'lead')
                <x-splade-input type="password" label="Password (Leave empty to retain old)" name="password" data-lm-pw-generate />
            @endif

        @endif

        @if(is_admin_staff())
    {{--            <x-splade-select required label="Role" name="role" :options="$roles" option-label="display_name" option-value="name" />--}}

                <x-splade-select v-if="form.role == 'customer' || form.role == 'user'" label="Status" name="status" :options="get_customer_approval_statuses()" />
                <x-splade-select v-if="form.role != 'lead' && form.role != 'customer' && form.role != 'user'" label="Status" name="status" :options="get_user_status()" />


                <x-splade-input  date label="FTD" name="ftd" />


            @else
                <x-splade-input disabled required label="Role" name="role" />
                <x-splade-input disabled required label="Status" name="status" />



        @endif

            <x-splade-input type="hidden" required label="Role" name="role" />

            @if($method == 'update')
                <x-splade-select v-if="form.role != 'lead'" label="Campaign" name="campaign" :options="array_combine(get_campaigns(), get_campaigns())" />
                <x-splade-select v-if="form.role != 'lead'" label="Source" name="source" :options="array_combine(get_sources(), get_sources())" />

            @if($user->role == 'lead')
                    <x-splade-select label="Select Campaign" name="campaign" :options="array_combine(get_campaigns(), get_campaigns())" />
                    <x-splade-select label="Select Source" name="source" :options="array_combine(get_sources(), get_sources())" />
                    <x-splade-select label="Select Status" name="status" :options="get_lead_status()" />



                    <x-splade-select label="Select Label" name="label" :options="get_labels()" />
                    <x-splade-select label="Select Desk" name="desk" :options="get_desks()" />
                    <x-splade-select label="Select Team" name="team" :options="get_teams()" />

                @endif
            @endif

    </div>
@endif

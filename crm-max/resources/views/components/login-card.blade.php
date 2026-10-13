<div class="flex flex-col sm:justify-center w-full items-center ">
    {{--    <div>--}}
    {{--        @isset($logo)--}}

    {{--        @else--}}
    {{--            <Link href="/">--}}
    {{--                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />--}}
    {{--            </Link>--}}
    {{--        @endisset--}}
    {{--    </div>--}}

    @isset($heading)
        {{ $heading }}

    @endisset


    <div class="w-full sm:max-w-lg mt-12 px-12 py-10 bg-dark shadow-md overflow-hidden rounded-lg">
        <div class="flex items-center justify-center mb-10">
            <img class="h-10" src="{{ getFullImage(company_settings('admin_logo', asset('images/logo.png'))) }}" />
        </div>

        {{ $slot }}
    </div>
</div>

<div class="relative">
    <div class="absolute inset-0 bg-no-repeat bg-center bg-cover opacity-10" style="background-image: url('/images/logo_bg.jpg');"></div>

    <div class="relative z-10">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100-">

            @isset($header)
                {{ $header }}
            @endisset

            <div class="w-full sm:max-w-lg mt-6 px-6 py-4 bg-dark bg-white- shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

    </div>

</div>




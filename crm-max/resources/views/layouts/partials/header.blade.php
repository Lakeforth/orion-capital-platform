
<header class="sticky top-0 z-10 flex w-full bg-white drop-shadow-1 dark:bg-boxdark dark:drop-shadow-none">
  <div class="flex flex-grow items-center justify-between py-4 px-4 shadow-2 md:px-6 2xl:px-11">
    <div class="flex items-center gap-2 sm:gap-4 lg:hidden">
      <!-- Hamburger Toggle BTN -->
      <button
          @click="side_menu.toggleSideBar"
        class="z-99999 block rounded-sm border border-stroke bg-white p-1.5 shadow-sm dark:border-strokedark dark:bg-boxdark lg:hidden"
        >
        <span class="relative block h-5.5 w-5.5 cursor-pointer">
          <span class="du-block absolute right-0 h-full w-full">
            <span
              class="relative top-0 left-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-[0] duration-200 ease-in-out dark:bg-white"
              :class="{ '!w-full delay-300': !side_menu.show_side_bar }">

            </span>
            <span
              class="relative top-0 left-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-150 duration-200 ease-in-out dark:bg-white"
              :class="{ '!w-full delay-400': !side_menu.show_side_bar }"></span>
            <span
              class="relative top-0 left-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-200 duration-200 ease-in-out dark:bg-white"
              :class="{ '!w-full delay-500': !side_menu.show_side_bar }"></span>
          </span>
          <span class="du-block absolute right-0 h-full w-full rotate-45">
            <span
              class="absolute left-2.5 top-0 block h-full w-0.5 rounded-sm bg-black delay-300 duration-200 ease-in-out dark:bg-white"
              :class="{ '!h-0 delay-[0]': !side_menu.show_side_bar }"></span>
            <span
              class="delay-400 absolute left-0 top-2.5 block h-0.5 w-full rounded-sm bg-black duration-200 ease-in-out dark:bg-white"
              :class="{ '!h-0 dealy-200': !side_menu.show_side_bar }"></span>
          </span>
        </span>
      </button>
      <!-- Hamburger Toggle BTN -->
      <a class="block flex-shrink-0 lg:hidden" href="/">
        <img src="{{ getFullImage(company_settings('admin_logo', '/images/admin_logo.png')) }}" class="h-12" alt="Logo" />
      </a>
    </div>
    <div class="hidden sm:block">
    </div>

    <div class="flex items-center gap-3 2xsm:gap-7">
      <ul class="flex items-center gap-2 2xsm:gap-4">
        <!-- Notification Menu Area -->
        <li class="relative dropdown cursor-pointer">
            <div tabindex="0" id="notificationHoverButton"
                 data-dropdown-toggle="notificationHover"
                 data-dropdown-trigger="hover"
                 class="btn- m-1">
                <span class="relative flex h-8.5 w-8.5 items-center
                justify-center rounded-full border-[0.5px] border-stroke
                 bg-gray hover:text-primary dark:border-strokedark dark:bg-meta-4
                 dark:text-white">

            <span :class="!side_menu.notifying && 'hidden'" class="absolute -top-0.5
            right-0 z-1 h-2 w-2 rounded-full bg-meta-1">
              <span
                  class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-meta-1 opacity-75"></span>
            </span>

                    <svg class="fill-current duration-300 ease-in-out" width="18" height="18" viewBox="0 0 18 18" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16.1999 14.9343L15.6374 14.0624C15.5249 13.8937 15.4687 13.7249 15.4687 13.528V7.67803C15.4687 6.01865 14.7655 4.47178 13.4718 3.31865C12.4312 2.39053 11.0812 1.7999 9.64678 1.6874V1.1249C9.64678 0.787402 9.36553 0.478027 8.9999 0.478027C8.6624 0.478027 8.35303 0.759277 8.35303 1.1249V1.65928C8.29678 1.65928 8.24053 1.65928 8.18428 1.6874C4.92178 2.05303 2.4749 4.66865 2.4749 7.79053V13.528C2.44678 13.8093 2.39053 13.9499 2.33428 14.0343L1.7999 14.9343C1.63115 15.2155 1.63115 15.553 1.7999 15.8343C1.96865 16.0874 2.2499 16.2562 2.55928 16.2562H8.38115V16.8749C8.38115 17.2124 8.6624 17.5218 9.02803 17.5218C9.36553 17.5218 9.6749 17.2405 9.6749 16.8749V16.2562H15.4687C15.778 16.2562 16.0593 16.0874 16.228 15.8343C16.3968 15.553 16.3968 15.2155 16.1999 14.9343ZM3.23428 14.9905L3.43115 14.653C3.5999 14.3718 3.68428 14.0343 3.74053 13.6405V7.79053C3.74053 5.31553 5.70928 3.23428 8.3249 2.95303C9.92803 2.78428 11.503 3.2624 12.6562 4.2749C13.6687 5.1749 14.2312 6.38428 14.2312 7.67803V13.528C14.2312 13.9499 14.3437 14.3437 14.5968 14.7374L14.7655 14.9905H3.23428Z"
                            fill="" />
                    </svg>
                </span>
            </div>
{{--            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">--}}
{{--                    <li><a>Item 1</a></li>--}}
{{--                    <li><a>Item 2</a></li>--}}
{{--                </ul>--}}

            <div tabindex="0"
                 id="notificationHover"
                 class="dropdown-content absolute z-10
                 hidden
                  -right-27 mt-2.5 h-90 w-75
                  flex-col rounded-sm border border-stroke bg-white
                  shadow-default dark:border-strokedark dark:bg-boxdark
                   sm:right-0 sm:w-80">
                <div class="px-4.5 py-3">
                    <h5 class="text-sm font-medium text-bodydark2">Notification</h5>
                </div>

                @section('hide')
                <ul class="flex h-80 flex-col overflow-y-auto">
                    <li>
                        <a class="flex flex-col gap-2.5 border-t border-stroke px-4.5 py-3 hover:bg-gray-2 dark:border-strokedark dark:hover:bg-meta-4"
                           href="#">
                            <p class="text-sm">
                                <span class="text-black dark:text-white">Edit your information in a swipe</span>
                                Sint occaecat cupidatat non proident, sunt in culpa qui
                                officia deserunt mollit anim.
                            </p>

                            <p class="text-xs">12 May, 2025</p>
                        </a>
                    </li>
                    <li>
                        <a class="flex flex-col gap-2.5 border-t border-stroke px-4.5 py-3 hover:bg-gray-2 dark:border-strokedark dark:hover:bg-meta-4"
                           href="#">
                            <p class="text-sm">
                                <span class="text-black dark:text-white">It is a long established fact</span>
                                that a reader will be distracted by the readable.
                            </p>

                            <p class="text-xs">24 Feb, 2025</p>
                        </a>
                    </li>
                    <li>
                        <a class="flex flex-col gap-2.5 border-t border-stroke px-4.5 py-3 hover:bg-gray-2 dark:border-strokedark dark:hover:bg-meta-4"
                           href="#">
                            <p class="text-sm">
                                <span class="text-black dark:text-white">There are many variations</span>
                                of passages of Lorem Ipsum available, but the majority have
                                suffered
                            </p>

                            <p class="text-xs">04 Jan, 2025</p>
                        </a>
                    </li>
                    <li>
                        <a class="flex flex-col gap-2.5 border-t border-stroke px-4.5 py-3 hover:bg-gray-2 dark:border-strokedark dark:hover:bg-meta-4"
                           href="#">
                            <p class="text-sm">
                                <span class="text-black dark:text-white">There are many variations</span>
                                of passages of Lorem Ipsum available, but the majority have
                                suffered
                            </p>

                            <p class="text-xs">01 Dec, 2024</p>
                        </a>
                    </li>
                </ul>
                @endsection
            </div>

        </li>
          <!-- Notification Menu Area -->
      </ul>

      <!-- User Area -->
      <div class="relative dropdown"
           id="userHoverButton"
           data-dropdown-toggle="userHover"
           data-dropdown-trigger="hover"
      >
        <span tabindex="0" role="button" class="flex items-center gap-4">
          <span class="hidden text-right lg:block">
            <span class="block text-sm truncate font-medium text-black dark:text-white">{{ auth()->user()->name }}</span>
            <span class="block text-xs font-medium">{{ auth()->user()?->roles?->first()?->name }}</span>
          </span>

          <span class="h-12 w-12 rounded-full overflow-hidden">
            <img class="w-12 h-12  rounded-full" src="{{ auth()->user()->avatar }}" alt="User" />
          </span>

          <svg  class="hidden fill-current sm:block" width="12" height="8"
            viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
              d="M0.410765 0.910734C0.736202 0.585297 1.26384 0.585297 1.58928 0.910734L6.00002 5.32148L10.4108 0.910734C10.7362 0.585297 11.2638 0.585297 11.5893 0.910734C11.9147 1.23617 11.9147 1.76381 11.5893 2.08924L6.58928 7.08924C6.26384 7.41468 5.7362 7.41468 5.41077 7.08924L0.410765 2.08924C0.0853277 1.76381 0.0853277 1.23617 0.410765 0.910734Z"
              fill="" />
          </svg>
        </span>

        <!-- Dropdown Start -->
        <div tabindex="0"
             id="userHover"
          class="absolute hidden dropdown-content right-0 mt-4 w-62.5 flex-col rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">

          <Link href="{{ route('super.logout') }}" method="POST"
            class="flex items-center gap-3.5 py-4 px-6 text-sm font-medium duration-300 ease-in-out hover:text-primary lg:text-base">
            <svg class="fill-current" width="22" height="22" viewBox="0 0 22 22" fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <path
                d="M15.5375 0.618744H11.6531C10.7594 0.618744 10.0031 1.37499 10.0031 2.26874V4.64062C10.0031 5.05312 10.3469 5.39687 10.7594 5.39687C11.1719 5.39687 11.55 5.05312 11.55 4.64062V2.23437C11.55 2.16562 11.5844 2.13124 11.6531 2.13124H15.5375C16.3625 2.13124 17.0156 2.78437 17.0156 3.60937V18.3562C17.0156 19.1812 16.3625 19.8344 15.5375 19.8344H11.6531C11.5844 19.8344 11.55 19.8 11.55 19.7312V17.3594C11.55 16.9469 11.2062 16.6031 10.7594 16.6031C10.3125 16.6031 10.0031 16.9469 10.0031 17.3594V19.7312C10.0031 20.625 10.7594 21.3812 11.6531 21.3812H15.5375C17.2219 21.3812 18.5625 20.0062 18.5625 18.3562V3.64374C18.5625 1.95937 17.1875 0.618744 15.5375 0.618744Z"
                fill="" />
              <path
                d="M6.05001 11.7563H12.2031C12.6156 11.7563 12.9594 11.4125 12.9594 11C12.9594 10.5875 12.6156 10.2438 12.2031 10.2438H6.08439L8.21564 8.07813C8.52501 7.76875 8.52501 7.2875 8.21564 6.97812C7.90626 6.66875 7.42501 6.66875 7.11564 6.97812L3.67814 10.4844C3.36876 10.7938 3.36876 11.275 3.67814 11.5844L7.11564 15.0906C7.25314 15.2281 7.45939 15.3312 7.66564 15.3312C7.87189 15.3312 8.04376 15.2625 8.21564 15.125C8.52501 14.8156 8.52501 14.3344 8.21564 14.025L6.05001 11.7563Z"
                fill="" />
            </svg>
            Log Out
          </Link>
        </div>
        <!-- Dropdown End -->
      </div>
      <!-- User Area -->
    </div>
  </div>
</header>

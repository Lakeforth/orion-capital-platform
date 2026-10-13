<template>
    <div>
        <!-- Mobile Header -->
        <div class="flex items-center justify-between w-full bg-[#141527] py-2 px-2">
            <!-- Left side - Hamburger menu -->
<!--            <button-->
<!--                data-drawer-target="drawer-navigation"-->
<!--                data-drawer-toggle="drawer-navigation"-->
<!--                aria-controls="drawer-navigation"-->
<!--                class="flex items-center text-gray-400"-->
<!--            >-->
<!--                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">-->
<!--                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>-->
<!--                </svg>-->
<!--            </button>-->

            <Link href="/dashboard" class="bg-green-600 text-white rounded px-4 py-2 text-sm font-medium">
                Dashboard
            </Link>
            <!-- Center - Balance & Bonus -->
            <div v-if="has_account" class="flex items-center space-x-4">

                <div>
                    <div class="flex items-center text-sm">
                        <p class="text-gray-400 text-xs text-center">Wallet Balance:</p>
                    </div>
                    <div class="">
                        <p class="text-white text-center text-sm">{{ balance }}</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center text-sm">
                        <p class="text-gray-400 text-xs text-center">Credit Balance:</p>
                    </div>
                    <div class="">
                        <p class="text-white text-center  text-sm">{{ credit_wallet }}</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center text-sm">
                        <p class="text-gray-400 text-xs text-center">Bonus Balance:</p>
                    </div>
                    <div class="">
                        <p class="text-white text-center  text-sm">{{ bonus_wallet }}</p>
                    </div>
                </div>

            </div>
            <div v-else class="flex items-center space-x-4">
                <div class="flex items-center text-sm">
                    <span class="text-gray-400">Wallet Balance:</span>
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 rounded-full bg-gray-600 mr-1"></div>
                    <span class="text-white">{{ balance }}</span>
                </div>
            </div>

            <!-- Right side - Deposit button -->

        </div>

        <!-- Flowbite Sidebar Drawer -->
        <div id="drawer-navigation" class="fixed top-0 left-0 z-40 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-[#1A1B2E] w-64 dark:bg-gray-800" tabindex="-1" aria-labelledby="drawer-navigation-label">
            <h5 id="drawer-navigation-label" class="text-base font-semibold text-gray-200 uppercase dark:text-gray-200">Menu</h5>
            <button type="button" data-drawer-hide="drawer-navigation" aria-controls="drawer-navigation" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 absolute top-2.5 right-2.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                <span class="sr-only">Close menu</span>
            </button>
            <div class="py-4 overflow-y-auto">
                <ul class="space-y-2 font-medium">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-200 rounded-lg dark:text-white hover:bg-gray-700 dark:hover:bg-gray-700 group">
                            <svg class="w-5 h-5 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                                <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                                <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.027-.334.038-.7.038-1.065a10 10 0 0 0-10-10Z"/>
                            </svg>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('investment.dashboard') }}" class="flex items-center p-2 text-gray-200 rounded-lg dark:text-white hover:bg-gray-700 dark:hover:bg-gray-700 group">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 18">
                                <path d="M6.143 0H1.857A1.857 1.857 0 0 0 0 1.857v4.286C0 7.169.831 8 1.857 8h4.286A1.857 1.857 0 0 0 8 6.143V1.857A1.857 1.857 0 0 0 6.143 0Zm10 0h-4.286A1.857 1.857 0 0 0 10 1.857v4.286C10 7.169 10.831 8 11.857 8h4.286A1.857 1.857 0 0 0 18 6.143V1.857A1.857 1.857 0 0 0 16.143 0Zm-10 10H1.857A1.857 1.857 0 0 0 0 11.857v4.286C0 17.169.831 18 1.857 18h4.286A1.857 1.857 0 0 0 8 16.143v-4.286A1.857 1.857 0 0 0 6.143 10Zm10 0h-4.286A1.857 1.857 0 0 0 10 11.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 18 16.143v-4.286A1.857 1.857 0 0 0 16.143 10Z"/>
                            </svg>
                            <span class="flex-1 ml-3 whitespace-nowrap">Smart Contract</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 text-gray-200 rounded-lg dark:text-white hover:bg-gray-700 dark:hover:bg-gray-700 group">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m17.418 3.623-.018-.008a6.713 6.713 0 0 0-2.4-.569V2h1a1 1 0 1 0 0-2h-2a1 1 0 0 0-1 1v2H9.89A6.977 6.977 0 0 1 12 8v5h-2V8A5 5 0 1 0 0 8v6a1 1 0 0 0 1 1h8v4a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-4h6a1 1 0 0 0 1-1V8a5 5 0 0 0-2.582-4.377ZM6 12H4a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <span class="flex-1 ml-3 whitespace-nowrap">About</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-2 text-gray-200 rounded-lg dark:text-white hover:bg-gray-700 dark:hover:bg-gray-700 group">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                                <path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/>
                            </svg>
                            <span class="flex-1 ml-3 whitespace-nowrap">Contact Us</span>
                        </a>
                    </li>
                    <li>
                        <Link class="flex items-center p-2 text-gray-200 rounded-lg dark:text-white hover:bg-gray-700 dark:hover:bg-gray-700 group" href="{{ route('logout') }}" method="POST">
                            <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 16">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 8h11m0 0L8 4m4 4-4 4m4-11h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3"/>
                            </svg>
                            <span class="flex-1 ml-3 whitespace-nowrap">Sign Out</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "MobileNav",
    data() {
        return {
            margin: 0,
            freeMargin: 0,
            marginLevel: 0,
            has_account: false,
            credit_wallet: 0,
            bonus_wallet: 0,
            pnl: 0,
            balance: 0,
        };
    },
    methods: {
        async getData() {
            try {
                const { data } = await axios.get("/api/user/trade-data");
                this.margin = data?.data.margin;
                this.balance = data?.data.balance;
                this.marginLevel = data?.data.marginLevel;
                this.pnl = data?.data.pnl;
                this.freeMargin = data?.data.freeMargin;
                this.has_account = data?.data.has_account;
                this.credit_wallet = data?.data.credit_wallet;
                this.bonus_wallet = data?.data.bonus_wallet;
            } catch (error) {
                console.error("Error fetching trade data:", error);
            }
        },
    },
    mounted() {
        // Fetch data when the component is mounted
        this.getData();

        // Initialize Flowbite drawer
        if (typeof window !== 'undefined' && window.Flowbite) {
            window.Flowbite.initDrawers();
        }

        this.$splade.on('refresh-trades', () => this.getData());
    },
    beforeUnmount() {
        // Clean up event listener
        document.removeEventListener("refresh-trades", this.getData);
    },
};
</script>

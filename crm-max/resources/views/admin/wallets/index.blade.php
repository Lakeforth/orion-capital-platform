<x-admin-layout>
    <x-slot name="header">
        <div class="flex ">
            <h2 class="dark:text-white text-lg text-gray-800 leading-tight">
                Wallets
            </h2>
        </div>
    </x-slot>
    <x-slot name="header_right">
    </x-slot>
    <x-card>
        <div class="py-4">
            <div class="flex justify-between">
                <div>
                    <p class="font-semibold text-xl">Current Balance</p>
                    <p>Balance across all your accounts</p>
                </div>

                <div>
                    <div class="flex gap-2">
                        <Link class="bg-black-2 px-2 py-2 rounded text-white" href="">Receive</Link>
                        <Link class="bg-black-2 px-2 py-2 rounded text-white" href="">Send</Link>
                        <Link class="bg-black-2 px-2 py-2 rounded text-white" href="">Swap</Link>

                    </div>
                </div>

            </div>

            <div class="mt-5">
                <div>
                    <p class="font-semibold text-xl">Total Balance</p>
                    <p class="font-bold text-2xl text-black-2">$94,634.02</p>
                </div>


                <div class="mt-2">
                    <p>Available Currency</p>

                    <div class="mt-2">

                        <div class="flex py-2 justify-between border-top border-t-2">
                            <p>Tron</p>
                            <p>$16,213.20</p>
                        </div>

                        <div class="flex py-2 justify-between border-top border-t-2">
                            <p>Tether(trc20)</p>
                            <p>$6,213.20</p>
                        </div>

                        <div class="flex py-2 justify-between border-top border-t-2">
                            <p>Ethereum</p>
                            <p>$2,900.00</p>
                        </div>

                    </div>
                </div>

            </div>


        </div>
    </x-card>
</x-admin-layout>

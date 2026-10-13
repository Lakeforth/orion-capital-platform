<template>

    <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1"
         id="navbar-sticky">
        <ul class="flex text-center text-white flex-col p-4 md:p-0 mt-4
                    font-medium border border-gray-100
                     rounded-lg md:space-x-8
                     rtl:space-x-reverse md:flex-row
                      md:mt-0 md:border-0 ">


            <li v-if="has_account">Total Balance<span class="price">{{ total_balance }}</span></li>
            <li>Balance<span class="price">{{ balance }}</span></li>
            <li>PnL<span :class="pnl > 0 ? 'price' : 'price price_native'">{{ pnl }}</span></li>
            <li>Margin<span class="price">{{ margin }}</span></li>
            <li>Free Margin<span class="price">{{ freeMargin}}</span></li>
            <li>Margin Level<span class="price">{{ marginLevel}}</span></li>
            <li v-if="has_account">Credit Balance<span class="price">{{ credit_wallet}}</span></li>
            <li v-if="has_account">Bonus Balance<span class="price">{{ bonus_wallet}}</span></li>



        </ul>
    </div>


</template>


<script>
import axios from "axios";

export default {
    name: "TradeNav",
    data() {
        return {
            margin: 0,
            freeMargin: 0,
            marginLevel: 0,
            pnl: 0,
            balance: 0,
            total_balance: 0,
            credit_wallet: 0,
            bonus_wallet: 0,
            has_account: false,
            pollInterval: null,
        };
    },
    methods: {
        async getData() {
            try {
                const { data } = await axios.get("/api/user/trade-data");
                this.margin = data?.data.margin ?? 0;
                this.balance = data?.data.balance;
                this.total_balance = data?.data.total_balance ?? 0;
                this.marginLevel = data?.data.marginLevel;
                this.pnl = data?.data.pnl;
                this.freeMargin = data?.data.freeMargin;
                this.has_account = data?.data.has_account;
                this.credit_wallet = data?.data.credit_wallet;
                this.bonus_wallet = data?.data.bonus_wallet;


                if(data?.data?.marginValue > 0){
                    setTimeout(() => {
                        this.getData()
                    }, 5000);
                }


            } catch (error) {
                console.error("Error fetching trade data:", error);
            }
        },

    },
    mounted() {
        // Fetch data when the component is mounted

        this.getData();

    },
    beforeUnmount() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval)
        }
    }
};
</script>

<style scoped>
.price_native {
    color: red;
}
</style>

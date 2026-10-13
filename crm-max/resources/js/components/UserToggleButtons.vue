<template>
    <div class="gap-2">
        <div class="gap-1 grid">
            <Link
                method="post"
                :href="copyTraderRoute"
                class="py-1 px-2 text-center text-xs rounded-md text-white"
                :class="copyTraderActive ? 'bg-primary' : 'bg-gray-600'"
            >
                <p>{{ copyTraderActive ? 'Autotrader Active' : 'Auto Trader Disabled' }}</p>
            </Link>

            <Link
                method="post"
                :href="requireOtpRoute"
                class="py-1 px-2 text-center text-xs rounded-md text-white"
                :class="requireOtpActive ? 'bg-primary' : 'bg-gray-600'"
            >
                <p>{{ requireOtpActive ? 'Require Login OTP' : 'Login OTP Disabled' }}</p>
            </Link>

            <Link
                method="post"
                :href="canEmailRoute"
                class="py-1 px-2 text-center text-xs rounded-md text-white"
                :class="canEmailActive ? 'bg-primary' : 'bg-gray-600'"
            >
                <p>{{ canEmailActive ? 'Enable Receiving Email' : 'Disable Receiving Email' }}</p>
            </Link>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserToggleButtons',
    props: {
        copyTraderRoute: {
            type: String,
            required: true
        },
        requireOtpRoute: {
            type: String,
            required: true
        },
        canEmailRoute: {
            type: String,
            required: true
        },
        copyTraderActive: {
            type: Boolean,
            required: true
        },
        requireOtpActive: {
            type: Boolean,
            required: true
        },
        canEmailActive: {
            type: Boolean,
            required: true
        }
    }
}
</script>

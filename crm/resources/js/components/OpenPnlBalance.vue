<template>
    <span>{{ display }}</span>
</template>
<script>
/**
 * The "PNL Balance" figure shown above the open-trades table used to be
 * server-rendered once (User::profit(), a straight sum of open trades'
 * `profit` column) and never updated again -- while the table below it
 * polls every 10s and re-prices live off the same feed the trade room
 * uses. QA caught the two visibly disagreeing (open positions summing to
 * more than the header showed) because the header was simply stale, not
 * because of some deeper timing bug.
 *
 * Matching the header's own poll interval to the table's wouldn't actually
 * fix this -- two independently-timed round trips can never be guaranteed
 * to land in the same instant, so they'd still drift apart from render to
 * render, just by a smaller margin. This instead removes the second
 * computation entirely: AdminOpenTrades emits its own already-live
 * displayTrades total on every recompute, and this only ever displays
 * that number. Same data, same instant, structurally incapable of
 * disagreeing with the table underneath it.
 */
export default {
    name: 'OpenPnlBalance',
    props: {
        initial: {
            type: String,
            default: '',
        },
        currencySymbol: {
            type: String,
            default: '$',
        },
    },
    data() {
        return {
            total: null,
        }
    },
    computed: {
        display() {
            if (this.total === null) {
                return this.initial
            }

            const rounded = Math.round(this.total)
            const sign = rounded < 0 ? '-' : ''

            return `${sign}${this.currencySymbol}${Math.abs(rounded).toLocaleString('en-US')}`
        },
    },
    mounted() {
        this.$splade.on('open-pnl-updated', this.handleUpdate)
    },
    beforeUnmount() {
        this.$splade.off('open-pnl-updated', this.handleUpdate)
    },
    methods: {
        handleUpdate(total) {
            this.total = total
        },
    },
}
</script>

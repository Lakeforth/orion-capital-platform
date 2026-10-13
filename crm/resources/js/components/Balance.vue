<template>
    <div class="balance-widget" :class="{ compact }">
        <div class="balance-lines">
            <p class="balance-line"><span class="balance-label">Balance:</span> <span class="balance-value">{{ balance }}</span></p>
            <p v-if="credit" class="balance-line"><span class="balance-label">{{ custom_wallet }}:</span> <span class="balance-value">{{ credit }}</span></p>
            <p v-if="bonus" class="balance-line"><span class="balance-label">Bonus:</span> <span class="balance-value">{{ bonus }}</span></p>

        </div>


        <div
            class="flex gap-2 balance-actions"
            :class="{ 'show-on-hover': hoverActions, 'overlay-actions': hoverActions }"
            v-if="is_admin"
        >

            <Link
                :href="`${wallet_url}?type=deposit`"
                :data-popover-target="`popover-wallet-top-${id}`"
                data-popover-placement="top"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </Link>

            <Link
                :href="`${wallet_url}?type=withdraw`"
                :data-popover-target="`popover-wallet-remove-${id}`"
                data-popover-placement="top"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </Link>

            <div
                :id="`popover-wallet-top-${id}`"
                class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800"
                data-popover
                role="tooltip"
            >
                <div class="px-3 py-2">
                    <p>Deposit</p>
                </div>
                <div data-popper-arrow></div>
            </div>

            <div
                :id="`popover-wallet-remove-${id}`"
                class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800"
                data-popover
                role="tooltip"
            >
                <div class="px-3 py-2">
                    <p>Withdrawal</p>
                </div>
                <div data-popper-arrow></div>
            </div>
        </div>

        <p class="balance-line">
            <span class="balance-label">Total Balance:</span>
            <span class="balance-value">{{ total_balance }}</span>
        </p>

    </div>
</template>

<script>
export default {
    name : "Balance",
    props: {
        wallet_url: {
            type: String,
            required: true
        },
        balance: {
            type: String,
            required: true
        },
        credit: {
            type: String,
        },
        total_balance: {
            type: String,
        },
        bonus: {
            type: String,
        },
        custom_wallet: {
            type: String,
        },
        edit_url: {
            type: String,
        },
        id: {
            type: [String, Number],
            required: true
        },
        is_admin: {
            type: Boolean
        },
        compact: {
            type: Boolean,
            default: false
        },
        hoverActions: {
            type: Boolean,
            default: false
        }
    },
}
</script>

<style scoped>
.balance-widget {
    display: grid;
    gap: 8px;
    align-items: center;
    position: relative;
}

.balance-lines {
    display: grid;
    gap: 2px;
}

.balance-line {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.3;
}

.balance-label {
    font-size: 12px;
    color: #6b7280;
}

.balance-value {
    color: #1f2937;
    font-weight: 600;
}

.total-deposit-link {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    width: fit-content;
}

.balance-actions.show-on-hover {
    opacity: 0;
    pointer-events: none;
    transition: all .16s ease;
}

.balance-widget:hover .balance-actions.show-on-hover {
    opacity: 1;
    pointer-events: auto;
}

.balance-actions.overlay-actions {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid #dbe2ec;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.balance-widget.compact {
    gap: 4px;
}

.balance-widget.compact .balance-line {
    font-size: 12px;
    line-height: 1.2;
}

.balance-widget.compact .balance-label {
    font-size: 11px;
}

.balance-widget.compact .total-deposit-link {
    font-size: 11px;
}

.balance-widget.compact :deep(svg) {
    width: 16px;
    height: 16px;
}
</style>

<template>
    <div class="flex gap-2">
        <button :data-popover-target="`popover-top-${item.id}`" data-popover-placement="top">
            bb
        </button>

<!--        <router-link :to="{name: 'admin.chat.index'}" :data-popover-target="`popover-chat-open-${item.id}`" data-popover-placement="top">-->
<!--            <i class="heroicon-o-chat-bubble-oval-left-ellipsis w-7"/>-->
<!--        </router-link>-->

<!--        <router-link to="#" :data-popover-target="`popover-calender-${item.id}`" data-popover-placement="top">-->
<!--            <i class="heroicon-o-calendar-days w-7"/>-->
<!--        </router-link>-->

<!--        <router-link to="#" :data-popover-target="`popover-label-${item.id}`" data-popover-placement="top">-->
<!--            <i class="heroicon-o-tag w-7"/>-->
<!--        </router-link>-->

        <a :href="`tel:${item.phone}`" :data-popover-target="`popover-call-${item.id}`" data-popover-placement="top">
            <i class="heroicon-o-phone-arrow-up-right w-7"/>
        </a>

<!--        <router-link to="#" :data-popover-target="`popover-chat-${item.id}`" data-popover-placement="top">-->
<!--            <i class="heroicon-o-chat-bubble-bottom-center-text w-7"/>-->
<!--        </router-link>-->

        <!-- Popovers -->
        <div v-for="type in popoverTypes"
             :key="type.id"
             :id="`popover-${type.id}-${item.id}`"
             class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800"
             data-popover
             role="tooltip">
            <div class="px-3 py-2">
                <template v-if="type.id === 'top'">
                    <p>Email: {{ item.email }}</p>
                    <p>Phone: {{ item.phone }}</p>
                </template>
                <p v-else>{{ type.text }}</p>
            </div>
            <div data-popper-arrow></div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            item : {
               id : 1, name : 'Ben', phone : '093903'
            },

            popoverTypes: [
                { id: 'top', text: '' },
                { id: 'chat-open', text: 'Open chat' },
                { id: 'call', text: 'Call' },
                { id: 'label', text: 'Assign Label' },
                { id: 'calender', text: 'Calender' },
                { id: 'chat', text: 'Add comment' }
            ]
        }
    }
}
</script>

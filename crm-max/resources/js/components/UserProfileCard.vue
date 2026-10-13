<template>
    <div class="flex items-center gap-3">
        <img class="h-10 w-10 rounded-full" :src="avatar" />

        <div>
            <div class="gap-1 mb-1 flex flex-col">
                <Link :href="userShowRoute" class="font-semibold text-[#0d63cc] hover:underline">{{ name }}</Link>
                <p class="text-xs text-gray-500">Last seen: {{ lastLogin || 'Never' }}</p>
                <p v-if="!canTrade" class="text-xs font-medium text-red-600">Trading disabled</p>

                <div v-if="phone">{{ phone }}</div>

                <div v-if="email">
                    <p class="mb-1">{{ email }}</p>
                    <!--                <span-->
                    <!--                    :class="emailVerified ? 'bg-green-500' : 'bg-red-500'"-->
                    <!--                    class="text-white text-sm px-2 py-1 rounded-lg"-->
                    <!--                >-->
                    <!--          {{ emailVerified ? 'Verified' : 'Unverified' }}-->
                    <!--        </span>-->
                </div>


            </div>

            <a
                target="_blank"
                class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"
                :href="frontend_login">
                Login
            </a>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserProfileCard',
    props: {
        avatar: {
            type: String,
            required: true
        },
        name: {
            type: String,
            required: true
        },
        userShowRoute: {
            type: String,
            required: true
        },
        phone: {
            type: String,
            required: true
        },
        email: {
            type: String,
            required: true
        },
        frontend_login : String,
        emailVerified: {
            type: Boolean,
            required: true
        },
        lastLogin: {
            type: String,
            default: ''
        },
        canTrade: {
            type: Boolean,
            default: true
        }
    }
}
</script>

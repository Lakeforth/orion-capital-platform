<template>
    <div class="mini-customer-messages">
        <div class="mini-customer-messages__body" ref="messagesBody">
            <div v-if="loading" class="mini-customer-messages__loading">Loading messages...</div>

            <template v-else>
                <div v-if="messages.length === 0" class="mini-customer-messages__loading">No messages yet.</div>

                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="mini-chat-row"
                    :class="isMine(message) ? 'is-mine' : 'is-theirs'"
                >
                    <div class="mini-chat-bubble">
                        <p>{{ message.message || '[Attachment]' }}</p>
                    </div>
                </div>
            </template>
        </div>

        <div class="mini-customer-messages__composer">
            <input
                v-model="message"
                type="text"
                class="mini-customer-messages__input"
                placeholder="Type Message"
                @keydown.enter.prevent="sendMessage"
            />
            <button class="mini-customer-messages__send" :disabled="sending || !message.trim()" @click="sendMessage">
                Send
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MiniCustomerMessages',
    props: {
        userId: {
            type: String,
            required: true,
        },
        currentUserId: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            loading: false,
            sending: false,
            message: '',
            messages: [],
        };
    },
    mounted() {
        this.fetchMessages();
    },
    methods: {
        isMine(message) {
            return String(message.sender_id) === String(this.currentUserId) || message.is_admin;
        },
        async fetchMessages() {
            this.loading = true;
            try {
                const { data } = await window.axios.get(`/api/v1/admin/chat/${this.userId}`);
                this.messages = data?.data || [];
                this.markUnreadAsRead();
                this.$nextTick(() => this.scrollToBottom());
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        async markUnreadAsRead() {
            const unreadIncoming = this.messages.filter(
                (item) => String(item.sender_id) !== String(this.currentUserId) && !item.read_at
            );

            for (const message of unreadIncoming) {
                try {
                    await window.axios.post(`/api/v1/admin/chat/messages/${message.id}/read`);
                } catch (error) {
                    console.error(error);
                }
            }
        },
        async sendMessage() {
            if (!this.message.trim() || this.sending) {
                return;
            }

            this.sending = true;
            try {
                const payload = {
                    message: this.message,
                    attachments: [],
                };

                const { data } = await window.axios.post(`/api/v1/admin/chat/${this.userId}`, payload);
                if (data?.data) {
                    this.messages.push(data.data);
                }

                this.message = '';
                this.$nextTick(() => this.scrollToBottom());
            } catch (error) {
                console.error(error);
            } finally {
                this.sending = false;
            }
        },
        scrollToBottom() {
            const body = this.$refs.messagesBody;
            if (body) {
                body.scrollTop = body.scrollHeight;
            }
        },
    },
};
</script>

<template>
    <div class="messages-container" ref="messagesContainer" @scroll="handleScroll">
        <!-- Loading indicator for infinite scroll -->
        <div v-if="loading && messages.length > 0" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i>
            Loading earlier messages...
        </div>

        <!-- Date separators and message groups -->
        <template v-for="(messageGroup, date) in groupedMessages" :key="date" >
            <div class="date-separator">
                <span>{{ formatDate(date) }}</span>
            </div>

            <!-- Messages for this date -->
            <div v-for="message in messageGroup" :key="message.id" class="message-wrapper">
                <message-item
                    :message="message"
                    @mark-as-read="$emit('mark-as-read', message.id)"
                />
            </div>
        </template>

        <!-- Empty state -->
        <div v-if="messages.length === 0 && !loading" class="no-messages">
            <i class="fas fa-comments no-messages-icon"></i>
            <p>No messages yet. Start the conversation!</p>
        </div>

        <!-- Initial loading -->
        <div v-if="loading && messages.length === 0" class="loading-messages">
            <i class="fas fa-spinner fa-spin loading-spinner"></i>
            <p>Loading messages...</p>
        </div>

        <!-- Anchor for auto-scrolling -->
        <div ref="bottomAnchor"></div>
    </div>
</template>

<script>
function isToday(date) {
    const t = new Date();
    return date.getDate() === t.getDate() && date.getMonth() === t.getMonth() && date.getFullYear() === t.getFullYear();
}
function isYesterday(date) {
    const y = new Date(); y.setDate(y.getDate() - 1);
    return date.getDate() === y.getDate() && date.getMonth() === y.getMonth() && date.getFullYear() === y.getFullYear();
}
function format(date) {
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}
import MessageItem from './MessageItem.vue';

export default {
    name: 'ChatMessages',
    components: {
        MessageItem
    },
    props: {
        messages: {
            type: Array,
            required: true
        },
        loading: {
            type: Boolean,
            default: false
        },
        hasMoreMessages: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            userScrolled: false,
            savedScrollHeight: 0,
            savedScrollTop: 0
        };
    },
    computed: {
        groupedMessages() {
            const groups = {};

            this.messages.forEach(message => {
                const date = new Date(message.created_at).toDateString();
                if (!groups[date]) {
                    groups[date] = [];
                }
                groups[date].push(message);
            });

            return groups;
        }
    },
    watch: {
        messages() {
            // Scroll to bottom when new messages arrive, unless user has scrolled up
            this.$nextTick(() => {
                if (!this.userScrolled) {
                    this.scrollToBottom();
                }
            });
        }
    },
    mounted() {
        this.scrollToBottom();
    },
    methods: {
        scrollToBottom() {
            if (this.$refs.bottomAnchor) {
                this.$refs.bottomAnchor.scrollIntoView({ behavior: 'smooth' });
            }
        },

        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            // Detect if user has scrolled up
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
            this.userScrolled = !isAtBottom;

            // Load more messages when scrolling to top
            if (container.scrollTop < 50 && this.hasMoreMessages && !this.loading) {
                this.$emit('load-more');
            }
        },

        saveScrollPosition() {
            const container = this.$refs.messagesContainer;
            if (container) {
                this.savedScrollHeight = container.scrollHeight;
                this.savedScrollTop = container.scrollTop;
            }
        },

        restoreScrollPosition() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    const newScrollHeight = container.scrollHeight;
                    const heightDifference = newScrollHeight - this.savedScrollHeight;
                    container.scrollTop = this.savedScrollTop + heightDifference;
                }
            });
        },

        formatDate(dateStr) {
            const date = new Date(dateStr);

            if (isToday(date)) {
                return 'Today';
            } else if (isYesterday(date)) {
                return 'Yesterday';
            } else {
                return format(date);
            }
        }
    }
};
</script>

<style scoped>
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    scroll-behavior: smooth;
}

.date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px 0;
    position: relative;
}

.date-separator::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 1px;
    background-color: #e5e7eb;
}

.date-separator span {
    background-color: #f3f4f6;
    padding: 0 10px;
    font-size: 12px;
    color: #6b7280;
    position: relative;
    z-index: 1;
}

.message-wrapper {
    margin-bottom: 8px;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    padding: 8px;
    color: #6b7280;
    font-size: 14px;
}

.loading-indicator i {
    margin-right: 8px;
}

.loading-messages {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
}

.loading-spinner {
    font-size: 24px;
    margin-bottom: 12px;
}

.no-messages {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
}

.no-messages-icon {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}
</style>

<template>
    <div class="user-list">
        <div class="search-container">
            <input
                type="text"
                placeholder="Search users..."
                :value="searchQuery"
                @input="$emit('update:search-query', $event.target.value)"
                class="search-input"
            >
        </div>

        <div class="users-container">
            <div
                v-for="user in filteredUsers"
                :key="user.id"
                class="user-item"
                :class="{ 'active': selectedUserId === user.id }"
                @click="$emit('select-user', user)"
            >
                <div class="user-item-avatar">
                    <img v-if="user.avatar" :src="user.avatar" :alt="user.first_name" class="avatar-small">
                    <div v-else class="avatar-fallback-small">
                        {{ user.first_name[0] }}{{ user.last_name[0] }}
                    </div>
                    <span class="status-dot-small" :class="{ 'online': user.is_online }"></span>
                </div>
                <div class="user-item-details">
                    <div class="user-item-name">
                        {{ user.first_name }} {{ user.last_name }}
                        <span v-if="user.unread_count" class="unread-badge">{{ user.unread_count }}</span>
                    </div>
                    <div class="user-item-last-message">
                        {{ formatLastMessage(user.last_message) }}
                    </div>
                </div>
                <div class="user-item-time" v-if="user.last_message_time">
                    {{ formatTime(user.last_message_time) }}
                </div>
            </div>

            <div v-if="loading" class="loading-users">
                <i class="fas fa-spinner fa-spin"></i>
                Loading users...
            </div>

            <div v-if="filteredUsers.length === 0 && !loading" class="no-users">
                No users found
            </div>
        </div>
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
function format(date, fmt) {
    if (fmt === 'h:mm a') return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    if (fmt === 'MMM d') return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    if (fmt === 'MMM d, yyyy') return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

export default {
    name: 'UserList',
    props: {
        users: {
            type: Array,
            required: true,
            default: () => []
        },
        selectedUserId: {
            type: String,
            default: null
        },
        loading: {
            type: Boolean,
            default: false
        },
        searchQuery: {
            type: String,
            default: ''
        }
    },
    computed: {
        filteredUsers() {
            if (!this.searchQuery) return this.users;

            const query = this.searchQuery.toLowerCase();
            return this.users.filter(user =>
                user.first_name.toLowerCase().includes(query) ||
                user.last_name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            );
        }
    },
    methods: {
        formatLastMessage(message) {
            if (!message) return 'No messages yet';

            // Truncate long messages
            return message.length > 25 ? message.substring(0, 25) + '...' : message;
        },
        formatTime(dateStr) {
            if (!dateStr) return '';

            const date = new Date(dateStr);

            if (isToday(date)) {
                return format(date, 'h:mm a');
            } else if (isYesterday(date)) {
                return 'Yesterday';
            } else {
                return format(date, 'MMM d');
            }
        }
    }
};
</script>

<style scoped>
.user-list {
    width: 300px;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    background-color: #ffffff;
}

.search-container {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.search-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    background-color: #f9fafb;
    font-size: 14px;
    outline: none;
}

.search-input:focus {
    border-color: #3b82f6;
    background-color: #ffffff;
}

.users-container {
    flex: 1;
    overflow-y: auto;
}

.user-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-item:hover {
    background-color: #f9fafb;
}

.user-item.active {
    background-color: #f0f9ff;
    border-left: 3px solid #3b82f6;
}

.user-item-avatar {
    position: relative;
    margin-right: 12px;
}

.avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-fallback-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.status-dot-small {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #9ca3af;
    border: 2px solid white;
}

.status-dot-small.online {
    background-color: #10b981;
}

.user-item-details {
    flex: 1;
    min-width: 0;
    margin-right: 8px;
}

.user-item-name {
    font-size: 14px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.unread-badge {
    background-color: #3b82f6;
    color: white;
    border-radius: 10px;
    padding: 1px 6px;
    font-size: 11px;
    font-weight: bold;
}

.user-item-last-message {
    font-size: 12px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-item-time {
    font-size: 11px;
    color: #9ca3af;
    white-space: nowrap;
}

.loading-users {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
}

.loading-users i {
    margin-right: 8px;
}

.no-users {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
}
</style>

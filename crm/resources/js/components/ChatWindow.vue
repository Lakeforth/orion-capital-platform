<template>
    <div class="chat-container">
        <!-- Chat header -->
        <div class="chat-header">
            <div class="user-info">
                <div class="avatar-container" v-if="selectedUser">
                    <img v-if="selectedUser.avatar" :src="selectedUser.avatar" :alt="selectedUser.first_name" class="avatar">
                    <div v-else class="avatar-fallback">{{ userInitials }}</div>
                    <span class="status-dot" :class="{ 'online': selectedUser.is_online }"></span>
                </div>
                <div v-if="selectedUser" class="user-details">
                    <h3>{{ selectedUser.first_name }} {{ selectedUser.last_name }}</h3>
                    <p>{{ selectedUser.email }}</p>
                </div>
                <div v-else class="select-user-prompt">
                    <h3>Admin Chat</h3>
                    <p>Select a user to start chatting</p>
                </div>
            </div>

            <div class="header-actions">
                <button class="action-button refresh" @click="fetchUsers">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <div class="chat-layout">
            <!-- User list -->
            <div class="user-list">
                <div class="search-container">
                    <input
                        type="text"
                        placeholder="Search users..."
                        v-model="searchQuery"
                        class="search-input"
                    >
                </div>

                <div class="users-container">
                    <div
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="user-item"
                        :class="{ 'active': selectedUserId === user.id }"
                        @click="selectUser(user)"
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
                                {{ user.last_message ? (user.last_message.length > 25 ? user.last_message.substring(0, 25) + '...' : user.last_message) : 'No messages yet' }}
                            </div>
                        </div>
                        <div class="user-item-time" v-if="user.last_message_time">
                            {{ formatTime(user.last_message_time) }}
                        </div>
                    </div>

                    <div v-if="loadingUsers" class="loading-users">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading users...
                    </div>

                    <div v-if="filteredUsers.length === 0 && !loadingUsers" class="no-users">
                        No users found
                    </div>
                </div>
            </div>

            <!-- Message area -->
            <div class="message-area">
                <template v-if="selectedUserId">
                    <div class="messages-container" ref="messagesContainer">
                        <!-- Date separator -->
                        <template v-for="(messageGroup, date) in groupedMessages" :key="date">
                            <div class="date-separator">
                                <span>{{ formatDate(date) }}</span>
                            </div>

                            <!-- Messages for this date -->
                            <div v-for="message in messageGroup" :key="message.id" class="message-wrapper">
                                <div
                                    class="message"
                                    :class="{
                    'admin-message': message.is_admin,
                    'user-message': !message.is_admin
                  }"
                                >
                                    <div class="message-content">
                                        <p v-if="message.message">{{ message.message }}</p>

                                        <!-- Attachments -->
                                        <div v-if="message.attachments && message.attachments.length > 0" class="attachments">
                                            <div v-for="attachment in message.attachments" :key="attachment.id" class="attachment">
                                                <!-- Image attachment -->
                                                <div v-if="isImage(attachment.file_type)" class="image-attachment">
                                                    <img :src="attachment.download_url" :alt="attachment.file_name" class="attachment-image">
                                                    <div class="attachment-info">
                                                        <span class="attachment-name">{{ attachment.file_name }}</span>
                                                        <a :href="attachment.download_url" download class="download-button">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <!-- File attachment -->
                                                <div v-else class="file-attachment">
                                                    <div class="file-icon">
                                                        <i :class="getFileIcon(attachment.file_type)"></i>
                                                    </div>
                                                    <div class="file-details">
                                                        <span class="file-name">{{ attachment.file_name }}</span>
                                                        <span class="file-size">{{ formatFileSize(attachment.file_size) }}</span>
                                                    </div>
                                                    <a :href="attachment.download_url" download class="download-button">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="message-meta">
                                        <span class="message-time">{{ formatMessageTime(message.created_at) }}</span>
                                        <span v-if="message.is_admin" class="read-status">
                      <i
                          :class="[
                          'fas',
                          message.read_at ? 'fa-check-double text-blue-500' : 'fa-check'
                        ]"
                      ></i>
                    </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-if="loadingMessages" class="loading-messages">
                            <i class="fas fa-spinner fa-spin"></i>
                            Loading messages...
                        </div>

                        <div v-if="messages.length === 0 && !loadingMessages" class="no-messages">
                            No messages yet. Start the conversation!
                        </div>

                        <div ref="bottomAnchor"></div>
                    </div>

                    <!-- Message input -->
                    <div class="message-input-area">
                        <!-- File preview area -->
                        <div v-if="selectedFiles.length > 0" class="file-preview-area">
                            <div v-for="(file, index) in selectedFiles" :key="index" class="file-preview">
                                <div class="file-preview-inner">
                                    <img
                                        v-if="file.preview"
                                        :src="file.preview"
                                        :alt="file.file.name"
                                        class="preview-image"
                                    >
                                    <div v-else class="file-icon-preview">
                                        <i :class="getFileIconFromFile(file.file)"></i>
                                    </div>
                                    <div class="file-name">{{ file.file.name }}</div>
                                    <div
                                        v-if="file.uploading"
                                        class="upload-progress"
                                        :style="{ width: file.progress + '%' }"
                                    ></div>
                                </div>
                                <button @click="removeFile(index)" class="remove-file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-container">
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileChange"
                                style="display: none;"
                                multiple
                            >

                            <button @click="openFileInput" class="attach-button">
                                <i class="fas fa-paperclip"></i>
                            </button>

                            <textarea
                                v-model="messageText"
                                placeholder="Type a message..."
                                class="message-input"
                                @keydown.enter.prevent="sendMessage"
                                ref="messageTextArea"
                            ></textarea>

                            <button
                                @click="sendMessage"
                                :disabled="!messageText.trim() && selectedFiles.length === 0"
                                class="send-button"
                                :class="{ 'disabled': !messageText.trim() && selectedFiles.length === 0 }"
                            >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <div v-else class="select-user-message">
                    <div class="select-user-content">
                        <i class="fas fa-comments select-user-icon"></i>
                        <h3>Select a user to start chatting</h3>
                        <p>Choose from the list on the left to begin a conversation</p>
                    </div>
                </div>
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
import axios from 'axios';

export default {
    name: 'AdminChat',
    data() {
        return {
            users: [],
            messages: [],
            selectedUserId: null,
            selectedUser: null,
            messageText: '',
            searchQuery: '',
            loadingUsers: false,
            loadingMessages: false,
            selectedFiles: [],
            page: 1,
            hasMoreMessages: true,
            userScrolled: false
        };
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
        },
        userInitials() {
            if (!this.selectedUser) return '';
            return `${this.selectedUser.first_name[0]}${this.selectedUser.last_name[0]}`.toUpperCase();
        },
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
    created() {
        this.fetchUsers();
        this.setupEchoListeners();
    },
    mounted() {
        // Add scroll listener to detect when user scrolls up
        this.$nextTick(() => {
            if (this.$refs.messagesContainer) {
                this.$refs.messagesContainer.addEventListener('scroll', this.handleScroll);
            }
        });
    },
    beforeDestroy() {
        // Remove scroll listener
        if (this.$refs.messagesContainer) {
            this.$refs.messagesContainer.removeEventListener('scroll', this.handleScroll);
        }

        // Clean up file previews
        this.selectedFiles.forEach(file => {
            if (file.preview) URL.revokeObjectURL(file.preview);
        });

        // Leave Echo channels
        if (window.Echo && this.selectedUserId) {
            window.Echo.leave(`chat.user.${this.selectedUserId}`);
        }
    },
    watch: {
        selectedUserId(newId) {
            if (newId) {
                this.fetchMessages();
                this.setupEchoListeners();
            }
        },
        messages() {
            // Scroll to bottom when messages change, unless user has scrolled up
            this.$nextTick(() => {
                if (!this.userScrolled && this.$refs.bottomAnchor) {
                    this.$refs.bottomAnchor.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
    },
    methods: {
        async fetchUsers() {
            this.loadingUsers = true;
            try {
                const response = await axios.get('/api/v1/admin/users/with-chat');
                this.users = response.data.data;

                // If we had a selected user, update that user's data
                if (this.selectedUserId) {
                    const updatedUser = this.users.find(u => u.id === this.selectedUserId);
                    if (updatedUser) {
                        this.selectedUser = updatedUser;
                    }
                }
            } catch (error) {
                console.error('Error fetching users:', error);
            } finally {
                this.loadingUsers = false;
            }
        },

        async fetchMessages(page = 1) {
            if (!this.selectedUserId) return;

            this.loadingMessages = true;
            try {
                const response = await axios.get(`/api/v1/admin/chat/${this.selectedUserId}?page=${page}`);

                if (page === 1) {
                    this.messages = response.data.data;
                } else {
                    // For pagination, add messages at the beginning as they're older
                    this.messages = [...response.data.data, ...this.messages];
                }

                this.hasMoreMessages = !!response.data.next_page_url;
                this.page = page;

                // Mark all unread messages as read
                this.markAllAsRead();
            } catch (error) {
                console.error('Error fetching messages:', error);
            } finally {
                this.loadingMessages = false;
            }
        },

        selectUser(user) {
            this.selectedUserId = user.id;
            this.selectedUser = user;
            this.page = 1;
            this.messages = [];
            this.userScrolled = false;

            // Reset any previous Echo listeners
            if (window.Echo) {
                Object.keys(this.users).forEach(userId => {
                    window.Echo.leave(`chat.user.${userId}`);
                });
            }
        },

        async sendMessage() {
            if ((!this.messageText.trim() && this.selectedFiles.length === 0) || !this.selectedUserId) return;

            try {
                let attachments = [];

                // Upload files first if any
                if (this.selectedFiles.length > 0) {
                    for (let i = 0; i < this.selectedFiles.length; i++) {
                        const fileData = this.selectedFiles[i];

                        // Mark as uploading and reset progress
                        this.$set(this.selectedFiles[i], 'uploading', true);
                        this.$set(this.selectedFiles[i], 'progress', 0);

                        // Upload the file
                        const formData = new FormData();
                        formData.append('file', fileData.file);

                        const response = await axios.post('/api/v1/chat/upload', formData, {
                            onUploadProgress: (event) => {
                                if (event.total) {
                                    const percentCompleted = Math.round((event.loaded * 100) / event.total);
                                    this.$set(this.selectedFiles[i], 'progress', percentCompleted);
                                }
                            }
                        });

                        attachments.push(response.data.url);
                    }
                }

                // Send the message with attachments
                await axios.post(`/api/v1/admin/chat/${this.selectedUserId}`, {
                    message: this.messageText,
                    attachments
                });

                // Clear input and files
                this.messageText = '';
                this.selectedFiles = [];

                // Make sure textarea is focused after sending
                this.$nextTick(() => {
                    if (this.$refs.messageTextArea) {
                        this.$refs.messageTextArea.focus();
                    }
                });
            } catch (error) {
                console.error('Error sending message:', error);
            }
        },

        setupEchoListeners() {
            if (!window.Echo || !this.selectedUserId) return;

            // Listen for new messages
            window.Echo.private(`chat.user.${this.selectedUserId}`)
                .listen('.message.sent', (e) => {
                    // Check if message already exists to avoid duplicates
                    if (!this.messages.some(m => m.id === e.message.id)) {
                        this.messages.push(e.message);

                        // If message is from user (not admin), mark it as read
                        if (!e.message.is_admin) {
                            this.markAsRead(e.message.id);
                        }

                        // Refresh user list to update last message
                        this.fetchUsers();
                    }
                })
                .listen('.message.read', (e) => {
                    // Update read status
                    const msgIndex = this.messages.findIndex(m => m.id === e.message_id);
                    if (msgIndex !== -1) {
                        this.$set(this.messages[msgIndex], 'read_at', new Date().toISOString());
                    }
                });
        },

        async markAsRead(messageId) {
            try {
                await axios.post(`/api/v1/chat/messages/${messageId}/read`);
            } catch (error) {
                console.error('Error marking message as read:', error);
            }
        },

        async markAllAsRead() {
            const unreadMessages = this.messages.filter(
                m => !m.is_admin && !m.read_at
            );

            for (const message of unreadMessages) {
                await this.markAsRead(message.id);
            }

            // Update local message objects
            unreadMessages.forEach(message => {
                const index = this.messages.findIndex(m => m.id === message.id);
                if (index !== -1) {
                    this.$set(this.messages[index], 'read_at', new Date().toISOString());
                }
            });

            // Refresh user list to update unread counts
            this.fetchUsers();
        },

        formatDate(dateStr) {
            const date = new Date(dateStr);

            if (isToday(date)) {
                return 'Today';
            } else if (isYesterday(date)) {
                return 'Yesterday';
            } else {
                return format(date, 'MMM d, yyyy');
            }
        },

        formatMessageTime(dateStr) {
            return format(new Date(dateStr), 'h:mm a');
        },

        formatTime(dateStr) {
            const date = new Date(dateStr);

            if (isToday(date)) {
                return format(date, 'h:mm a');
            } else if (isYesterday(date)) {
                return 'Yesterday';
            } else {
                return format(date, 'MMM d');
            }
        },

        isImage(fileType) {
            return fileType.startsWith('image/');
        },

        getFileIcon(fileType) {
            if (fileType.startsWith('image/')) {
                return 'far fa-image';
            } else if (fileType === 'application/pdf') {
                return 'far fa-file-pdf';
            } else if (fileType.includes('word') || fileType.includes('document')) {
                return 'far fa-file-word';
            } else if (fileType.includes('excel') || fileType.includes('spreadsheet')) {
                return 'far fa-file-excel';
            } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                return 'far fa-file-powerpoint';
            } else if (fileType.includes('zip') || fileType.includes('compressed')) {
                return 'far fa-file-archive';
            } else if (fileType.includes('text') || fileType.includes('txt')) {
                return 'far fa-file-alt';
            } else {
                return 'far fa-file';
            }
        },

        getFileIconFromFile(file) {
            return this.getFileIcon(file.type);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        openFileInput() {
            this.$refs.fileInput.click();
        },

        handleFileChange(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            // Add each file to selectedFiles
            Array.from(files).forEach(file => {
                let preview;
                if (file.type.startsWith('image/')) {
                    preview = URL.createObjectURL(file);
                }

                this.selectedFiles.push({
                    file,
                    preview,
                    uploading: false,
                    progress: 0
                });
            });

            // Reset file input
            event.target.value = '';
        },

        removeFile(index) {
            // Revoke URL if it's an image preview
            if (this.selectedFiles[index].preview) {
                URL.revokeObjectURL(this.selectedFiles[index].preview);
            }

            this.selectedFiles.splice(index, 1);
        },

        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            // Detect if user has scrolled up
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
            this.userScrolled = !isAtBottom;

            // Load more messages when scrolling to top
            if (container.scrollTop < 50 && this.hasMoreMessages && !this.loadingMessages) {
                this.loadMoreMessages();
            }
        },

        async loadMoreMessages() {
            if (!this.hasMoreMessages || this.loadingMessages) return;

            // Store scroll position
            const container = this.$refs.messagesContainer;
            const scrollHeight = container.scrollHeight;

            await this.fetchMessages(this.page + 1);

            // Restore scroll position after new messages are loaded
            this.$nextTick(() => {
                const newScrollHeight = container.scrollHeight;
                const diff = newScrollHeight - scrollHeight;
                container.scrollTop = diff; // Adjust scroll position
            });
        }
    }
};
</script>

<style scoped>
.chat-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background-color: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}

.user-info {
    display: flex;
    align-items: center;
}

.avatar-container {
    position: relative;
    margin-right: 12px;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-fallback {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.status-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #9ca3af;
    border: 2px solid white;
}

.status-dot.online {
    background-color: #10b981;
}

.user-details h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.user-details p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

.select-user-prompt h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.select-user-prompt p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

.header-actions {
    display: flex;
}

.action-button {
    background: none;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4b5563;
    cursor: pointer;
    transition: background-color 0.2s;
}

.action-button:hover {
    background-color: #f3f4f6;
}

.chat-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
}

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

.message-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #f3f4f6;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px 0;
    position: relative;
}

.date-separator span {
    background-color: #f3f4f6;
    padding: 0 10px;
    font-size: 12px;
    color: #6b7280;
    position: relative;
    z-index: 1;
}

.date-separator::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 1px;
    background-color: #e5e7eb;
}

.message-wrapper {
    display: flex;
    margin-bottom: 8px;
}

.message {
    max-width: 70%;
    display: flex;
    flex-direction: column;
    margin-bottom: 4px;
}

.admin-message {
    align-self: flex-start;
    background-color: #e0f2fe;
    border-radius: 16px 16px 16px 0;
    padding: 10px 12px;
    margin-right: auto;
}

.user-message {
    align-self: flex-end;
    background-color: #ffffff;
    border-radius: 16px 16px 0 16px;
    padding: 10px 12px;
    margin-left: auto;
}

.message-content p {
    margin: 0;
    word-break: break-word;
    font-size: 14px;
    color: #1f2937;
}

.message-meta {
    display: flex;
    align-items: center;
    margin-top: 4px;
}

.message-time {
    font-size: 11px;
    color: #9ca3af;
    margin-right: 4px;
}

.read-status {
    font-size: 11px;
    color: #9ca3af;
}

.attachments {
    margin-top: 8px;
}

.attachment {
    margin-top: 4px;
}

.image-attachment {
    position: relative;
    border-radius: 4px;
    overflow: hidden;
}

.attachment-image {
    max-width: 100%;
    max-height: 200px;
    display: block;
    border-radius: 4px;
}

.attachment-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 6px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}

.attachment-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 8px;
}

.download-button {
    color: white;
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 4px;
}

.download-button:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.file-attachment {
    display: flex;
    align-items: center;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 4px;
    padding: 8px;
}

.file-icon {
    margin-right: 10px;
    color: #3b82f6;
}

.file-details {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1f2937;
}

.file-size {
    font-size: 11px;
    color: #6b7280;
}

.loading-messages {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
}

.loading-messages i {
    margin-right: 8px;
}

.no-messages {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
}

.message-input-area {
    padding: 16px;
    background-color: white;
    border-top: 1px solid #e5e7eb;
}

.file-preview-area {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
    max-height: 100px;
    overflow-y: auto;
}

.file-preview {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
}

.file-preview-inner {
    width: 100%;
    height: 100%;
    background-color: #f3f4f6;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4px;
}

.preview-image {
    max-width: 100%;
    max-height: 50px;
    object-fit: contain;
}

.file-icon-preview {
    font-size: 24px;
    color: #3b82f6;
}

.file-name {
    font-size: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    text-align: center;
    margin-top: 4px;
    color: #4b5563;
}

.upload-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background-color: #3b82f6;
}

.remove-file {
    position: absolute;
    top: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 10px;
}

.input-container {
    display: flex;
    align-items: center;
    background-color: #f3f4f6;
    border-radius: 24px;
    padding: 8px 12px;
}

.attach-button {
    background: none;
    border: none;
    color: #6b7280;
    margin-right: 8px;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.attach-button:hover {
    background-color: #e5e7eb;
}

.message-input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    resize: none;
    max-height: 100px;
    font-size: 14px;
    font-family: inherit;
    padding: 8px 0;
    color: #1f2937;
}

.message-input::placeholder {
    color: #9ca3af;
}

.send-button {
    background: none;
    border: none;
    color: #3b82f6;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    transition: background-color 0.2s, color 0.2s;
    margin-left: 8px;
}

.send-button:hover {
    background-color: #e5e7eb;
}

.send-button.disabled {
    color: #9ca3af;
    cursor: not-allowed;
}

.select-user-message {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f9fafb;
}

.select-user-content {
    text-align: center;
    padding: 30px;
}

.select-user-icon {
    font-size: 48px;
    color: #9ca3af;
    margin-bottom: 16px;
}

.select-user-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.select-user-content p {
    font-size: 14px;
    color: #6b7280;
}
</style>

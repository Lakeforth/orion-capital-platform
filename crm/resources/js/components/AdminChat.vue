<template>
    <div class="chat-container">
        <!-- Chat header -->
        <div class="chat-header">
            <div class="user-info">
                <div class="avatar-container" v-if="selectedUser">
                    <img
                        v-if="selectedUser.avatar"
                        :src="selectedUser.avatar"
                        :alt="selectedUser.first_name"
                        class="avatar"
                    />
                    <div v-else class="avatar-fallback">{{ userInitials }}</div>
                    <span
                        class="status-dot"
                        :class="{ online: selectedUser.is_online }"
                    ></span>
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
            <UserList
                :users="users"
                :selected-user-id="selectedUserId"
                :loading="loadingUsers"
                :search-query="searchQuery"
                @update:search-query="val => (searchQuery = val)"
                @select-user="selectUser"
            />

            <!-- Message area -->
            <div class="message-area">
                <template v-if="selectedUserId">
                    <ChatMessages
                        :messages="messages"
                        :loading="loadingMessages"
                        :has-more-messages="hasMoreMessages"
                        @load-more="loadMoreMessages"
                        @mark-as-read="markAsRead"
                        ref="messagesComponent"
                    />
                    <ChatInput
                        v-model="messageText"
                        :selected-files="selectedFiles"
                        @send="sendMessage"
                        @file-added="addFile"
                        @file-removed="removeFile"
                    />

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
import { ref, computed, onBeforeUnmount } from 'vue'
import { setupEchoAuth } from '../utils/echoAuth'
import UserList from './chat/UserList.vue'
import ChatMessages from './chat/ChatMessages.vue'
import ChatInput from './chat/ChatInput.vue'
import axios from 'axios'

export default {
    name: 'AdminChat',
    components: { UserList, ChatMessages, ChatInput },

    setup() {
        // state
        const users = ref([])
        const messages = ref([])
        const selectedUserId = ref(null)
        const selectedUser = ref(null)
        const messageText = ref('')
        const searchQuery = ref('')
        const loadingUsers = ref(false)
        const loadingMessages = ref(false)
        const selectedFiles = ref([])
        const page = ref(1)
        const hasMoreMessages = ref(true)
        const echoInitialized = ref(false)
        const echoChannels = ref([])

        // computed
        const userInitials = computed(() => {
            if (!selectedUser.value) return ''
            const first = selectedUser.value.first_name?.[0] ?? ''
            const last = selectedUser.value.last_name?.[0] ?? ''
            return (first + last).toUpperCase()
        })

        // Echo + initial data
        const initialize = async () => {
            echoInitialized.value = await setupEchoAuth()
            fetchUsers()
        }

        // fetch users
        const fetchUsers = async () => {
            loadingUsers.value = true
            try {
                const { data } = await axios.get('/api/v1/admin/users/with-chat')
                users.value = data.data

                if (selectedUserId.value) {
                    const upd = users.value.find(u => u.id === selectedUserId.value)
                    if (upd) selectedUser.value = upd
                }
            } catch (e) {
                console.error(e)
            } finally {
                loadingUsers.value = false
            }
        }

        // fetch messages
        const fetchMessages = async (p = 1) => {
            if (!selectedUserId.value) return
            loadingMessages.value = true

            try {
                const { data } = await axios.get(
                    `/api/v1/admin/chat/${selectedUserId.value}?page=${p}`
                )

                if (p === 1) {
                    messages.value = data.data
                } else {
                    messages.value = [...data.data, ...messages.value]
                }

                hasMoreMessages.value = !!data.next_page_url
                page.value = p
            } catch (e) {
                console.error(e)
            } finally {
                loadingMessages.value = false
            }
        }

        // select a user
        const selectUser = user => {
            selectedUserId.value = user.id
            selectedUser.value = user
            page.value = 1
            messages.value = []
            setupEchoListeners()
            fetchMessages()
        }

        // Echo listeners
        const setupEchoListeners = () => {
            if (!window.Echo || !selectedUserId.value || !echoInitialized.value)
                return

            // leave old channels
            echoChannels.value.forEach(name => window.Echo.leave(name))
            echoChannels.value = []

            const chanName = `chat.user.${selectedUserId.value}`
            const channel = window.Echo.private(chanName)

            channel.listen('.message.sent', e => {
                if (!messages.value.some(m => m.id === e.message.id)) {
                    messages.value.push(e.message)
                    if (!e.message.is_admin) markAsRead(e.message.id)
                    fetchUsers()
                }
            })

            channel.listen('.message.read', e => {
                const idx = messages.value.findIndex(m => m.id === e.message_id)
                if (idx !== -1) {
                    messages.value[idx].read_at = new Date().toISOString()
                }
            })

            echoChannels.value.push(chanName)
        }

        // send
        const sendMessage = async () => {
            if (
                (!messageText.value.trim() && selectedFiles.value.length === 0) ||
                !selectedUserId.value
            )
                return

            try {
                const attachments = []

                for (const fileObj of selectedFiles.value) {
                    fileObj.uploading = true
                    fileObj.progress = 0

                    const form = new FormData()
                    form.append('file', fileObj.file)
                    const res = await axios.post('/api/v1/chat/upload', form, {
                        onUploadProgress: ev => {
                            if (ev.total) {
                                fileObj.progress = Math.round((ev.loaded * 100) / ev.total)
                            }
                        },
                    })
                    attachments.push(res.data.url)
                }

                // await axios.post(`/api/v1/admin/chat/${selectedUserId.value}`, {
                //     message: messageText.value,
                //     attachments,
                // })

                const res = await axios.post(
                    `/api/v1/admin/chat/${selectedUserId.value}`,
                    { message: messageText.value, attachments }
                );

                messages.value.push(res.data.data);

                messageText.value = ''
                selectedFiles.value = []
            } catch (e) {
                console.error(e)
            }
        }

        // mark read
        const markAsRead = async id => {
            // await axios.post(`/api/v1/admin/chat/messages/${id}/read`)
            const idx = messages.value.findIndex(m => m.id === id)
            if (idx !== -1) messages.value[idx].read_at = new Date().toISOString()
        }

        // load more
        const loadMoreMessages = async () => {
            if (!hasMoreMessages.value || loadingMessages.value) return

            const comp = refs.messagesComponent
            comp?.saveScrollPosition()
            await fetchMessages(page.value + 1)
            comp?.restoreScrollPosition()
        }

        // file handlers
        const addFile = file => {
            const obj = { file, preview: null, uploading: false, progress: 0 }
            if (file.type.startsWith('image/')) {
                obj.preview = URL.createObjectURL(file)
            }
            selectedFiles.value.push(obj)
        }
        const removeFile = idx => {
            const f = selectedFiles.value[idx]
            if (f.preview) URL.revokeObjectURL(f.preview)
            selectedFiles.value.splice(idx, 1)
        }

        // cleanup
        onBeforeUnmount(() => {
            selectedFiles.value.forEach(f => f.preview && URL.revokeObjectURL(f.preview))
            echoChannels.value.forEach(name => window.Echo.leave(name))
        })

        // kick things off
        initialize()

        return {
            users,
            messages,
            selectedUserId,
            selectedUser,
            messageText,
            searchQuery,
            loadingUsers,
            loadingMessages,
            selectedFiles,
            hasMoreMessages,
            userInitials,
            fetchUsers,
            selectUser,
            loadMoreMessages,
            sendMessage,
            addFile,
            removeFile,
            markAsRead,
        }
    },
}
</script>



<style scoped>
/* CSS styles remain the same as the previous component */
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

.message-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #f3f4f6;
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

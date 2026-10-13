<template>
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
            <div v-if="hasAttachments" class="attachments">
                <div
                    v-for="attachment in message.attachments"
                    :key="attachment.id"
                    class="attachment"
                >
                    <!-- Image attachment -->
                    <div v-if="isImage(attachment.file_type)" class="image-attachment">
                        <img
                            :src="attachment.download_url"
                            :alt="attachment.file_name"
                            class="attachment-image"
                            @load="$emit('attachment-loaded')"
                        >
                        <div class="attachment-info">
                            <span class="attachment-name">{{ attachment.file_name }}</span>
                            <a
                                :href="attachment.download_url"
                                download
                                class="download-button"
                                @click.stop
                            >
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
                        <a
                            :href="attachment.download_url"
                            download
                            class="download-button-file"
                            @click.stop
                        >
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="message-meta">
            <span class="message-time">{{ formattedTime }}</span>
            <span v-if="message.is_admin" class="read-status">
        <i
            :class="[
            'fas',
            message.read_at ? 'fa-check-double read' : 'fa-check'
          ]"
        ></i>
      </span>
        </div>
    </div>
</template>

<script>
function format(date, fmt) {
    if (fmt === 'h:mm a') return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    if (fmt === 'MMM d') return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    if (fmt === 'MMM d, yyyy') return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

export default {
    name: 'MessageItem',
    props: {
        message: {
            type: Object,
            required: true
        }
    },
    computed: {
        hasAttachments() {
            return this.message.attachments && this.message.attachments.length > 0;
        },
        formattedTime() {
            return this.formatMessageTime(this.message.created_at);
        }
    },
    mounted() {
        // If this is a user message and it's not read yet, mark it as read
        if (!this.message.is_admin && !this.message.read_at) {
            this.$emit('mark-as-read');
        }
    },
    methods: {
        formatMessageTime(dateStr) {
            return format(new Date(dateStr), 'h:mm a');
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

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
};
</script>

<style scoped>
.message {
    max-width: 70%;
    padding: 10px 12px;
    border-radius: 16px;
    margin-bottom: 4px;
}

.admin-message {
    align-self: flex-start;
    background-color: #e0f2fe;
    border-radius: 16px 16px 16px 0;
    margin-right: auto;
    float: left;
    clear: both;
}

.user-message {
    align-self: flex-end;
    background-color: #ffffff;
    border-radius: 16px 16px 0 16px;
    margin-left: auto;
    float: right;
    clear: both;
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
    font-size: 11px;
    color: #9ca3af;
}

.message-time {
    margin-right: 4px;
}

.read-status i.read {
    color: #3b82f6;
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
    display: block;
    font-size: 11px;
    color: #6b7280;
}

.download-button-file {
    color: #3b82f6;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
}

.download-button-file:hover {
    background-color: rgba(59, 130, 246, 0.1);
}
</style>

<template>
    <div class="message-input-area">
        <!-- File preview area (unchanged) -->
        <div v-if="selectedFiles.length" class="file-preview-area">
            <div
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="file-preview"
            >
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
                    <div class="file-name">
                        {{ truncateFileName(file.file.name) }}
                    </div>
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
            />

            <button @click="openFileInput" class="attach-button">
                <i class="fas fa-paperclip"></i>
            </button>

            <!-- bind to modelValue and emit update:modelValue -->
            <textarea
                :value="modelValue"
                @input="updateValue($event.target.value)"
                placeholder="Type a message..."
                class="message-input"
                @keydown.enter.prevent="sendIfNotShift"
                ref="messageTextArea"
                rows="1"
            ></textarea>

            <button
                @click="send"
                :disabled="isDisabled"
                class="send-button"
                :class="{ disabled: isDisabled }"
            >
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ChatInput',

    // Vue3 v-model defaults to prop="modelValue" + event="update:modelValue"
    props: {
        modelValue: {
            type: String,
            default: ''
        },
        selectedFiles: {
            type: Array,
            default: () => []
        }
    },

    emits: [
        'update:modelValue', // for v-model
        'send',
        'file-added',
        'file-removed'
    ],

    computed: {
        isDisabled() {
            return !this.modelValue.trim() && this.selectedFiles.length === 0;
        }
    },

    mounted() {
        this.adjustTextareaHeight();
    },

    methods: {
        updateValue(value) {
            this.$emit('update:modelValue', value);
            this.$nextTick(this.adjustTextareaHeight);
        },

        adjustTextareaHeight() {
            const ta = this.$refs.messageTextArea;
            if (!ta) return;
            ta.style.height = 'auto';
            ta.style.height = `${Math.min(ta.scrollHeight, 120)}px`;
        },

        sendIfNotShift(e) {
            if (!e.shiftKey) this.send();
        },

        send() {
            if (this.isDisabled) return;
            this.$emit('send');
            this.$nextTick(() => {
                this.adjustTextareaHeight();
                this.$refs.messageTextArea.focus();
            });
        },

        openFileInput() {
            this.$refs.fileInput.click();
        },

        handleFileChange(e) {
            const files = e.target.files;
            if (!files.length) return;
            Array.from(files).forEach(file => this.$emit('file-added', file));
            e.target.value = '';
        },

        removeFile(idx) {
            this.$emit('file-removed', idx);
        },

        getFileIconFromFile(file) {
            if (file.type.startsWith('image/')) return 'far fa-image';
            if (file.type === 'application/pdf') return 'far fa-file-pdf';
            if (file.type.includes('word')) return 'far fa-file-word';
            if (file.type.includes('excel')) return 'far fa-file-excel';
            if (file.type.includes('powerpoint')) return 'far fa-file-powerpoint';
            if (file.type.includes('zip')) return 'far fa-file-archive';
            if (file.type.includes('text')) return 'far fa-file-alt';
            return 'far fa-file';
        },

        truncateFileName(name) {
            if (name.length <= 15) return name;
            const ext = name.split('.').pop();
            const base = name.slice(0, name.length - ext.length - 1);
            return `${base.slice(0,10)}...${ext}`;
        }
    }
};
</script>

<style scoped>
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
    padding: 0;
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
    max-height: 120px;
    font-size: 14px;
    font-family: inherit;
    padding: 6px 0;
    color: #1f2937;
    overflow-y: auto;
    line-height: 1.5;
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
</style>

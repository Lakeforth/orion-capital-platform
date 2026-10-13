<template>
    <div>
        <slot />

        <div class="flex flex-col items-center justify-center space-y-4">

            <div
                @click="openFileManager"
                class="w-full max-w-lg p-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 transition duration-150 ease-in-out relative"
                :data-input="inputId"
                :data-preview="previewId"
            >
                <template v-if="!filePath">
                    <p class="text-center text-gray-500">Click to upload {{ type }} here</p>
                </template>
                <template v-else>
                    <img :src="imageThumbUrl" class="h-20 object-center object-cover mx-auto rounded-lg shadow-md">
                    <button @click.stop="removeImage" class="absolute top-2 h-6 w-6 right-2 p-1- bg-red-600 text-white rounded-full">&times;</button>
                </template>
                <input type="hidden" :id="inputId" v-model="filePath">
            </div>
        </div>
    </div>

</template>

<script>
export default {
    props: {
        inputId: {
            type: String,
            required: true
        },
        previewId: {
            type: String,
            required: true
        },
        routePrefix: {
            type: String,
            default: '/filemanager'
        },
        type: {
            type: String,
            default: 'file'
        },
        file: {
            type: String,
        },
        modelValue: String,
    },
    data() {
        return {
            filePath: '',
            imageThumbUrl: ''
        };
    },
    emits: ['update:modelValue'],
    methods: {
        openFileManager() {
            const route_prefix = this.routePrefix;
            const target_input = document.getElementById(this.inputId);
            const target_preview = document.getElementById(this.previewId);

            window.open(route_prefix + '?type=' + this.type, 'FileManager', 'width=900,height=600');
            window.SetUrl = (items) => {
                const file_path = items.map(item => item.url).join(',');

                if (target_input) {
                    target_input.value = file_path;
                    target_input.dispatchEvent(new Event('change'));
                    this.filePath = file_path;
                    this.imageThumbUrl = items[0].thumb_url;
                } else {
                    console.error('target_input is null');
                }
            };
        },
        removeImage() {
            this.filePath = '';
            this.imageThumbUrl = '';
            const target_input = document.getElementById(this.inputId);
            if (target_input) {
                target_input.value = '';
                target_input.dispatchEvent(new Event('change'));
            }
        }
    },
    watch : {
        filePath(newVal){
            this.$emit('update:modelValue', newVal);
        }
    },
    mounted() {
        this.imageThumbUrl = this.modelValue
        this.filePath = this.modelValue
    }
};
</script>

<style scoped>
.dropzone {
    width: 100%;
    max-width: 600px;
    padding: 30px;
    border: 2px dashed #cccccc;
    border-radius: 10px;
    cursor: pointer;
    text-align: center;
    transition: border-color 0.3s ease;
}
.dropzone:hover {
    border-color: #007bff;
}
</style>

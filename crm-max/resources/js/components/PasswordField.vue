<template>
    <div>
        <label :for="inputId" class="mb-2 block text-sm font-medium text-gray-700">
            <span>{{ label }}</span>
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input
                :id="inputId"
                :name="name"
                :type="showPassword ? 'text' : 'password'"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :required="required"
                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 pr-12 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm transition focus:border-red-500 focus:ring-red-500"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-2 text-gray-500 transition hover:text-gray-800"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
            >
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.477 10.48a3 3 0 014.243 4.243M9.88 9.88a3 3 0 00-.707 3.247M6.228 6.228C4.33 7.538 2.933 9.534 2.458 12c1.274 4.057 5.064 7 9.542 7a9.96 9.96 0 004.772-1.183M15.53 15.53A9.957 9.957 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.18-3.568" />
                </svg>
            </button>
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    name: {
        type: String,
        required: true,
    },
    id: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "Password",
    },
    placeholder: {
        type: String,
        default: "••••••••",
    },
    autocomplete: {
        type: String,
        default: "current-password",
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: "",
    },
});

defineEmits(["update:modelValue"]);

const showPassword = ref(false);
const inputId = computed(() => props.id || `${props.name}-field`);
</script>

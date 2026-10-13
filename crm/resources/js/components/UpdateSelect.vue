<template>
  <div class="inline-flex items-center gap-2">
    <select
      class="rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-indigo-500 focus:ring-indigo-500 px-2 py-1 disabled:opacity-50 disabled:cursor-not-allowed"
      :disabled="loading || disabled"
      v-model="selected"
      @change="onSelect"
    >
      <option v-if="placeholder" :value="null" disabled>{{ placeholder }}</option>
      <option
        v-for="(opt, idx) in normalizedOptions"
        :key="idx + '-' + opt.key"
        :value="opt.key"
      >
        {{ opt.value }}
      </option>
    </select>

    <svg
      v-if="loading"
      class="animate-spin h-4 w-4 text-gray-500"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
      />
    </svg>

    <span v-if="saved" class="text-xs text-green-600">Saved</span>
    <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
  </div>
</template>

<script>
export default {
  name: 'UpdateSelect',
  props: {
    value: {
      // Accept any value, including null
      default: null,
    },
    field: {
      type: String,
      required: true,
    },
    endpoint: {
      type: String,
      required: true,
    },
    // Array of { key, value }
    data: {
      type: Array,
      default: () => [],
      required: false,
    },
    // Optional endpoint to fetch options when data is not available in the cell context
    dataEndpoint: {
      type: String,
      default: '',
    },
    // Keys to map when fetching options (e.g., id -> key, title -> value)
    optionKey: {
      type: String,
      default: 'id',
    },
    optionValue: {
      type: String,
      default: 'title',
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    placeholder: {
      type: String,
      default: 'Select...',
    },
  },
  data() {
    return {
      selected: this.value,
      loading: false,
      saved: false,
      error: '',
      fetched: [],
    };
  },
  computed: {
    normalizedOptions() {
      const base = (this.data && this.data.length ? this.data : this.fetched) || [];
      // Ensure each item has { key, value }
      return base.map((it) => {
        if (typeof it === 'object' && it !== null && 'key' in it && 'value' in it) {
          return it;
        }
        // Attempt mapping
        if (typeof it === 'object' && it !== null) {
          const k = it[this.optionKey] ?? it.key ?? it.value ?? it.id;
          const v = it[this.optionValue] ?? it.value ?? it.title ?? it.name ?? String(k);
          return { key: k, value: v };
        }
        return { key: it, value: String(it) };
      });
    },
  },
  watch: {
    value(newVal) {
      this.selected = newVal;
    },
  },
  mounted() {
    // If no inline data provided and we have a dataEndpoint, fetch options
    if ((!this.data || this.data.length === 0) && this.dataEndpoint) {
      this.fetchOptions();
    }
  },
  methods: {
    async fetchOptions() {
      this.loading = true;
      try {
        const { data } = await window.axios.get(this.dataEndpoint, { withCredentials: true });
        // Expect an array of objects; if not array, try to find first array value
        if (Array.isArray(data)) {
          this.fetched = data;
        } else if (data && typeof data === 'object') {
          // find first array property
          const arr = Object.values(data).find((v) => Array.isArray(v));
          this.fetched = arr || [];
        } else {
          this.fetched = [];
        }
      } catch (e) {
        this.error = 'Load options failed';
      } finally {
        this.loading = false;
      }
    },
    async onSelect() {
      this.loading = true;
      this.error = '';
      this.saved = false;
      try {
        const payload = { [this.field]: this.selected };
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await window.axios.post(this.endpoint, payload, {
          headers: token ? { 'X-CSRF-TOKEN': token } : {},
          withCredentials: true,
        });
        this.saved = true;
        setTimeout(() => (this.saved = false), 1500);
        this.$emit('updated', { field: this.field, value: this.selected });
      } catch (e) {
        this.error = 'Update failed';
        // Rollback selection to prop value if request fails
        this.selected = this.value;
      } finally {
        this.loading = false;
      }
    },
  },
};

</script>

<style scoped>
</style>

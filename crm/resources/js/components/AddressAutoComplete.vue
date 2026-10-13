<template>
    <div>
        <label class="typo__label" for="ajax">Address *</label>
        <multiselect
            v-model="selectedAddress"
            id="ajax"
            label="description"
            track-by="place_id"
            placeholder="Type to search"
            open-direction="bottom"
            :options="addresses"
            :searchable="true"
            :loading="isLoading"
            :internal-search="false"
            :clear-on-select="false"
            :close-on-select="true"
            :options-limit="300"
            :limit="3"
            :limit-text="limitText"
            :max-height="600"
            :show-no-results="false"
            :hide-selected="true"
            @search-change="asyncFind"
            @select="onSelect"
        />
        <pre class="language-json"><code>{{ selectedAddress }}</code></pre>
    </div>
</template>

<script>
import Multiselect from 'vue-multiselect'

export default {
    components: {
        Multiselect
    },
    props: {
        modelValue: String
    },
    emits: ['update:modelValue', 'update:map_lat', 'update:map_lng'],
    data () {
        return {
            selectedAddress: null,
            addresses: [],
            isLoading: false
        }
    },
    methods: {
        limitText (count) {
            return `and ${count} other results`
        },
        asyncFind (query) {
            if (!query) return;
            this.isLoading = true;
            fetch(`/api/v1/autocomplete?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    this.addresses = data || []; // Ensure results are properly updated
                    this.isLoading = false;
                })
                .catch(() => {
                    this.addresses = [];
                    this.isLoading = false;
                });
        },
        onSelect (option) {
            this.selectedAddress = option.description;
            this.$emit('update:modelValue', option.description);

            fetch(`/api/v1/details?placeId=${option.place_id}`)
                .then(response => response.json())
                .then(data => {
                    const location = data.result.geometry.location;
                    console.log(location.latitude)
                    this.$emit('update:map_lat', location.lat);
                    this.$emit('update:map_lng', location.lng);
                });
        },
        clearAll () {
            this.selectedAddress = null;
        }
    },
    watch: {
        modelValue(newVal) {
            this.selectedAddress = newVal;
        }
    }
}
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>

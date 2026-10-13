<template>
    <div class="">
        <label class="block"><span class="block mb-1 text-gray-700 font-sans"> Address <span
        aria-hidden="true" class="text-red-600" title="This field is required">*</span></span>
        <div class="flex rounded-md border border-gray-300 shadow-sm">
            <input
            class="block w-full border-0 focus:border-indigo-300 focus:ring
            focus:ring-indigo-200 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-gray-50 disabled:cursor-not-allowed rounded-md"
            type="text"
            ref="autocompleteInput"
            @input="handleInput"
            required></div>
    </label>
    </div>
<!--    <div>-->
<!--        <input-->
<!--            type="text"-->
<!--            ref="autocompleteInput"-->
<!--            @input="handleInput"-->
<!--            class="block w-full border-0 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-gray-50 disabled:cursor-not-allowed rounded-md"-->
<!--            placeholder="Enter address"-->
<!--        />-->
<!--    </div>-->
</template>

<script>
import {onMounted, ref, watch} from 'vue';
export default {
    props: {
        apiKey: {type: String, required: true},
        modelValue: String
    },
    emits: ['update:modelValue', 'update:latitude', 'update:longitude'],
    setup(props, {emit}) {
        const autocompleteInput = ref(null);

        const handleInput = () => {
            const autocomplete = new google.maps.places.Autocomplete(autocompleteInput.value);
            google.maps.event.addListener(autocomplete, 'place_changed', async () => {
                const place = await autocomplete?.getPlace();
                console.log(place.name)
                console.log(place)
                if (place.geometry) {
                    await onSearch(place.name)
                    emit('update:modelValue', place.formatted_address);
                    // emit('update:modelValue', place.formatted_address);
                    emit('update:latitude', place.geometry.location.lat());
                    emit('update:longitude', place.geometry.location.lng());
                }
            });
        };

        const onSearch = async (query) => {
            if (!query) return;
            const response = await fetch(`/api/v1/autocomplete?query=${query}`);
            const data = await response.json();
            console.log(data)
        };

        onMounted(() => {
            if (!window.google) {
                window.initGoogleMaps = () => {
                    initializeAutocomplete();
                };
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${props.apiKey}&libraries=places&callback=initGoogleMaps`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            } else {
                initializeAutocomplete();
            }
        });

        return {autocompleteInput, handleInput};

    }
};
</script>


<x-splade-input required label="Name" name="name" />

<x-splade-input type="hidden" name="type" />

@if(($type ?? null) === 'plan')
    <x-splade-checkbox
        name="use_image"
        label="Use Icon Image"
        value="1"
        false-value="0"
        toggle
    />

    <div v-if="!(form.use_image === true || form.use_image === 1 || form.use_image === '1')">
        <label class="mb-3 block text-sm font-medium text-gray-700">Icon</label>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
            @foreach(customer_plan_feature_icon_catalog() as $key => $icon)
                <label
                    for="icon_key_{{ $key }}"
                    class="cursor-pointer rounded-xl border bg-white p-3 transition"
                    :class="form.icon_key === '{{ $key }}'
                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                        : 'border-gray-200 hover:border-primary/40 hover:bg-gray-50'"
                >
                    <input
                        id="icon_key_{{ $key }}"
                        v-model="form.icon_key"
                        type="radio"
                        name="icon_key"
                        value="{{ $key }}"
                        class="sr-only"
                    />

                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full"
                            :class="form.icon_key === '{{ $key }}' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'"
                        >
                            {{ svg($icon['svg'], 'h-5 w-5') }}
                        </span>

                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $icon['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ $key }}</p>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <div v-if="form.use_image === true || form.use_image === 1 || form.use_image === '1'">
        <ImageUpload
            button-id="feature-image"
            input-id="feature-image"
            preview-id="feature-image"
            v-model="form.image"
            button-text="Select File"
            type="image"
        >
            <p>Icon Image</p>
        </ImageUpload>
    </div>

    <x-splade-checkbox
        name="is_active"
        label="Active"
        value="1"
        false-value="0"
        toggle
    />
@endif



<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-splade-input name="title" label="Title" required/>
    <x-splade-input type="color" name="color" label="Color" required/>
    <x-splade-input type="number" name="leverage" label="Leverage" required/>


    <x-splade-input type="number" name="min_deposit" label="Min Deposit" required/>
    @if(module_enabled('enable_plan_price'))
        <x-splade-input type="number" name="price" label="Price" step="0.01" min="0" required/>
    @endif


    <ImageUpload
        button-id="icon"
        input-id="icon"
        preview-id="icon"
        v-model="form.icon"
        button-text="Select File"
        type="image"
    >
        <p>Icon</p>
    </ImageUpload>

    <ImageUpload
        button-id="image"
        input-id="image"
        preview-id="image"
        v-model="form.image"
        button-text="Select File"
        type="image"
    >
        <p>Image</p>
    </ImageUpload>
</div>

@if(module_enabled('expert_advisor'))

    <x-splade-select multiple choices required
                     label="Expert Advisors" name="expert_advisors"
                     option-value="id"
                     option-label="name"
                     remote-url="{{ route('api.eas') }}"
    />

@endif

<br/>
<p class="text-xl mt-2">PLAN FEATURES</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-2">
    @foreach(plan_features() as $index => $feature)
            <x-splade-input
                name="features[{{ $index }}]"
                :label="$feature"
                placeholder="Enter value for {{ $feature }}"
                class="w-full"
            />
    @endforeach
</div>


<x-splade-input name="description" label="Description" />

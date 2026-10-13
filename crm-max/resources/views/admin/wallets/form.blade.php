<x-splade-data :default="[
    'walletCryptoNetworks' => $walletCryptoNetworkOptions ?? [],
]">
    <x-splade-select
        name="type"
        label="Type"
        :options="['wallet' => 'Wallet', 'link' => 'Link']"
        required
    />

    <ImageUpload
        button-id="barcode"
        input-id="fileInput"
        preview-id="fileBarcode"
        v-model="form.barcode"
        button-text="Select File"
        type="image"
    >
        <p v-text="form.type === 'link' ? 'Image' : 'QR Code'"></p>
    </ImageUpload>

    <x-splade-input v-if="form.type === 'link'" required label="Name" name="crypto" />

    <div v-if="form.type !== 'link'" class="space-y-2">
        <x-splade-select
            name="crypto"
            label="Crypto name"
            :options="$walletCryptoOptions ?? []"
            placeholder="Select crypto"
            required
        />

        <p v-if="Object.keys(data.walletCryptoNetworks).length === 0" class="text-sm text-amber-700">
            No company deposit cryptos have been configured yet. Add supported cryptos in `/panel/settings/deposit` first.
        </p>
    </div>

    <x-splade-textarea v-if="form.type === 'link'" required label="Deposit Instruction" name="crypto_network" />

    <x-splade-select
        v-if="form.type !== 'link'"
        required
        label="Crypto Network"
        name="crypto_network"
        placeholder="Select network"
    >
        <option value="">Select network</option>
        <option
            v-for="network in (data.walletCryptoNetworks[form.crypto] || [])"
            :key="network.value"
            :value="network.value"
            v-text="network.label"
        ></option>
    </x-splade-select>

    <x-splade-input v-if="form.type === 'link'" required label="Link url" name="address" />
    <x-splade-input v-if="form.type !== 'link'" required label="Address" name="address" />

    @if(isset($general))
        <x-splade-select choices option-label="name"
                         option-value="id" label="User (Leave empty for all users)"
                         remote-root="data"
                         remote-url="/api/users"
                         name="user_id"/>
    @else
        <x-splade-input type="hidden" required  name="user_id" />
    @endif
</x-splade-data>

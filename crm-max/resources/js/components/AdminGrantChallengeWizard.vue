<template>
    <div>
        <div v-if="step < 4" class="flex items-center gap-3 mb-6">
            <div v-for="(label, i) in steps" :key="label" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold"
                        :class="(i + 1) < step || (i + 1) === step ? 'bg-[#3B82F6] text-white' : 'bg-white border border-[#CBD5E1] text-[#94A3B8]'"
                    >
                        <span v-if="(i + 1) < step">&#10003;</span>
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="text-sm font-semibold" :class="(i + 1) <= step ? 'text-[#1E3A5F]' : 'text-[#94A3B8]'">{{ label }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="w-10 h-px bg-[#CBD5E1]"></div>
            </div>
        </div>

        <!-- STEP 1: CLIENT + SELECT -->
        <div v-if="step === 1" class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-5 items-start">
            <div>
                <h2 class="text-[#1E3A5F] text-lg font-bold mb-3">Client</h2>
                <div v-if="lockedClient" class="mb-6 p-3 rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] flex items-center justify-between">
                    <div>
                        <p class="text-[#1E3A5F] font-bold text-sm">{{ lockedClient.name }}</p>
                        <p class="text-[#64748B] text-xs">{{ lockedClient.email }}</p>
                    </div>
                    <a :href="cancelUrl" class="text-[#3B82F6] text-xs font-semibold">Change</a>
                </div>
                <div v-else class="mb-6">
                    <input
                        v-model="clientSearch"
                        type="text"
                        placeholder="Search clients by name or email…"
                        class="w-full border border-[#E2E8F0] rounded-lg px-3 py-2 text-sm text-[#334155] focus:outline-none focus:ring-2 focus:ring-[#3B82F6] mb-2"
                    />
                    <div class="max-h-52 overflow-y-auto border border-[#E2E8F0] rounded-lg divide-y divide-[#F1F5F9]">
                        <button
                            v-for="c in filteredClients"
                            :key="c.id"
                            type="button"
                            @click="selectedUserId = c.id"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC]"
                            :class="selectedUserId === c.id ? 'bg-[#EFF6FF]' : ''"
                        >
                            <span class="text-[#1E3A5F] font-semibold">{{ c.name }}</span>
                            <span class="text-[#94A3B8]"> — {{ c.email }}</span>
                        </button>
                        <p v-if="filteredClients.length === 0" class="px-3 py-3 text-sm text-[#94A3B8]">No clients match.</p>
                    </div>
                </div>

                <h2 class="text-[#1E3A5F] text-lg font-bold mb-3">Challenge Type</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                    <button
                        v-for="key in [1, 2, 3, 0]"
                        :key="key"
                        type="button"
                        :disabled="!availableTypes.includes(key)"
                        @click="selectType(key)"
                        class="text-left p-4 rounded-xl border transition-colors"
                        :class="[
                            typeKey === key ? 'border-[#3B82F6] ring-1 ring-[#3B82F6]' : 'border-[#E2E8F0]',
                            !availableTypes.includes(key) ? 'opacity-50 cursor-not-allowed' : 'hover:border-[#94A3B8]',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <p class="text-[#1E3A5F] font-bold">{{ typeLabels[key].label }}</p>
                            <span v-if="key === 2" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#DBEAFE] text-[#3B82F6]">MOST POPULAR</span>
                            <span v-if="!availableTypes.includes(key)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F1F5F9] text-[#94A3B8]">SOON</span>
                        </div>
                        <p class="text-[#64748B] text-sm mt-1">{{ typeLabels[key].blurb }}</p>
                    </button>
                </div>

                <h2 class="text-[#1E3A5F] text-lg font-bold mb-3">Account Size</h2>
                <div class="flex flex-wrap gap-2 mb-6">
                    <p v-if="typeKey === null" class="text-[#94A3B8] text-sm">Pick a challenge type first.</p>
                    <p v-else-if="sizesForType.length === 0" class="text-[#94A3B8] text-sm">No plans configured for this type yet.</p>
                    <button
                        v-for="size in sizesForType"
                        :key="size"
                        type="button"
                        @click="accountSize = size"
                        class="px-4 py-2 rounded-full border text-sm font-bold transition-colors flex items-center gap-1.5"
                        :class="accountSize === size ? 'bg-[#3B82F6] border-[#3B82F6] text-white' : 'border-[#CBD5E1] text-[#334155] hover:border-[#94A3B8]'"
                    >
                        {{ money(size) }}
                        <span v-if="planHasFreeTrial(size)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#DCFCE7] text-[#15803D]">FREE TRIAL</span>
                    </button>
                </div>

                <h2 class="text-[#1E3A5F] text-lg font-bold mb-3">Drawdown Type</h2>
                <div class="flex gap-2">
                    <button
                        v-for="dt in ['static', 'trailing']"
                        :key="dt"
                        type="button"
                        :disabled="accountSize !== null && !drawdownTypesAvailable.has(dt)"
                        @click="drawdownType = dt"
                        class="px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-1.5"
                        :class="drawdownType === dt ? 'bg-[#3B82F6] text-white' : (accountSize !== null && !drawdownTypesAvailable.has(dt)) ? 'border border-[#E2E8F0] text-[#94A3B8] opacity-60 cursor-not-allowed' : 'border border-[#E2E8F0] text-[#1E3A5F]'"
                    >
                        {{ dt === 'static' ? 'Static' : 'Trailing' }}
                    </button>
                </div>
                <p class="text-[#94A3B8] text-xs mt-2">
                    {{ drawdownType === 'static' ? 'Static: drawdown floor is fixed at the initial balance.' : 'Trailing: floor moves up as equity grows.' }}
                </p>
            </div>

            <order-summary
                :rows="[
                    { label: 'Client', value: lockedClient ? lockedClient.name : (selectedClient ? selectedClient.name : '—') },
                    { label: 'Challenge Type', value: typeKey !== null ? typeLabels[typeKey].label : '—' },
                    { label: 'Account Size', value: accountSize ? money(accountSize) : '—' },
                    { label: 'Drawdown', value: drawdownType === 'static' ? 'Static' : 'Trailing' },
                ]"
                :total="0"
                free-badge="No Charge (Admin Grant)"
                :original-price="selectedPlan ? selectedPlan.price : null"
                :rules="firstPhase ? [
                    { label: 'Profit Target', value: firstPhase.profit_target_pct + '%' },
                    { label: 'Max Drawdown', value: firstPhase.max_total_drawdown_pct + '%' },
                    { label: 'Daily Loss Limit', value: firstPhase.max_daily_loss_pct + '%' },
                    { label: 'Profit Split', value: (selectedPlan ? selectedPlan.profit_split_pct : 0) + '%' },
                ] : []"
                :cta="{ label: 'Continue to Add-ons', disabled: !canContinueStep1 }"
                @cta="step = 2"
            />
        </div>

        <!-- STEP 2: ADD-ONS -->
        <div v-if="step === 2" class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-5 items-start">
            <div>
                <h2 class="text-[#1E3A5F] text-2xl font-bold mb-1">Add-ons</h2>
                <p class="text-[#64748B] text-sm mb-5">Optional upgrades to include in this grant (no charge either way)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button
                        v-for="addon in addonCatalog"
                        :key="addon.key"
                        type="button"
                        @click="toggleAddon(addon.key)"
                        class="text-left p-4 rounded-xl border transition-colors"
                        :class="selectedAddons.includes(addon.key) ? 'border-[#3B82F6] ring-1 ring-[#3B82F6]' : 'border-[#E2E8F0] hover:border-[#94A3B8]'"
                    >
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-[#1E3A5F] font-bold text-sm">{{ addon.label }}</p>
                            <div class="w-9 h-5 rounded-full relative shrink-0 transition-colors" :class="selectedAddons.includes(addon.key) ? 'bg-[#3B82F6]' : 'bg-[#E2E8F0]'">
                                <div class="absolute top-0.5 w-4 h-4 rounded-full bg-white transition-all" :class="selectedAddons.includes(addon.key) ? 'left-4' : 'left-0.5'"></div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-[#F1F5F9] text-[#64748B] text-xs font-semibold">
                            usually +{{ money(addon.price) }}
                        </span>
                    </button>
                </div>
            </div>

            <order-summary
                :rows="[{ label: 'Base Challenge (' + (accountSize ? money(accountSize) : '—') + ')', value: 'FREE' }].concat(
                    selectedAddons.map((key) => ({ label: addonLabel(key), value: 'FREE' }))
                )"
                :total="0"
                free-badge="No Charge (Admin Grant)"
                :rules="[]"
                :cta="{ label: 'Continue to Grant', disabled: false }"
                :secondary-cta="{ label: 'Back' }"
                @cta="step = 3"
                @secondary="step = 1"
            />
        </div>

        <!-- STEP 3: GRANT -->
        <div v-if="step === 3" class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-5 items-start">
            <div class="flex flex-col gap-6">
                <div>
                    <h2 class="text-[#1E3A5F] text-lg font-bold mb-3">Reason (optional)</h2>
                    <textarea
                        v-model="note"
                        rows="3"
                        placeholder="e.g. compensation, promo agreement, support goodwill"
                        class="w-full border border-[#E2E8F0] rounded-lg px-3 py-2 text-sm text-[#334155] focus:outline-none focus:ring-2 focus:ring-[#3B82F6]"
                    ></textarea>
                    <p class="text-[#94A3B8] text-xs mt-2">Logged on the client's account for audit purposes -- not shown to the client.</p>
                </div>

                <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
            </div>

            <order-summary
                :rows="[
                    { label: 'Client', value: lockedClient ? lockedClient.name : (selectedClient ? selectedClient.name : '—') },
                    { label: (typeKey !== null ? typeLabels[typeKey].label : '') + ' ' + (accountSize ? money(accountSize) : ''), value: 'FREE' },
                ].concat(selectedAddons.map((key) => ({ label: addonLabel(key), value: 'FREE' })))"
                :total="0"
                free-badge="No Charge (Admin Grant)"
                :rules="[]"
                :what-you-get="[
                    'Prop firm trading account, issued immediately',
                    (selectedAddons.includes('profit_split_90') ? 90 : (selectedPlan ? selectedPlan.profit_split_pct : 0)) + '% profit split',
                    selectedAddons.includes('no_time_limit') ? 'No fixed time limit' : (firstPhase && firstPhase.max_duration_days ? firstPhase.max_duration_days + '-day phase limit' : 'No fixed time limit'),
                ]"
                :cta="{ label: submitting ? 'Granting…' : 'Grant Challenge', disabled: submitting || !canSubmit }"
                :secondary-cta="{ label: 'Back' }"
                footnote="No wallet debit, no charge -- the client is emailed their trading credentials immediately."
                @cta="submit"
                @secondary="step = 2"
            />
        </div>

        <!-- STEP 4: GRANTED -->
        <div v-if="step === 4 && granted" class="max-w-[700px] mx-auto">
            <div class="bg-white rounded-2xl border border-[#E2E8F0] p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-[#22C55E] flex items-center justify-center mx-auto mb-5">
                    <span class="text-white text-3xl font-bold">&#10003;</span>
                </div>
                <h2 class="text-[#1E3A5F] text-2xl font-bold mb-2">Challenge Granted!</h2>
                <p class="text-[#64748B] text-sm mb-6">
                    {{ lockedClient ? lockedClient.name : (selectedClient ? selectedClient.name : 'The client') }}'s
                    {{ typeKey !== null ? typeLabels[typeKey].label : '' }} {{ accountSize ? money(accountSize) : '' }} challenge account is ready
                    and they've been emailed their trading credentials.
                </p>

                <div class="border border-[#E2E8F0] rounded-xl p-5 text-left">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[#1E3A5F] font-bold">Trading Credentials</p>
                        <span class="px-2 py-1 rounded-md text-[11px] font-bold bg-[#FEF3C7] text-[#B45309]">ALSO EMAILED TO CLIENT</span>
                    </div>
                    <credential-row label="Account ID" :value="granted.account_id || 'Not yet issued'" />
                    <credential-row label="Password" :value="granted.password || 'Not yet issued'" last />
                </div>

                <div class="flex gap-3 mt-6">
                    <a :href="granted.show_url" class="flex-1 px-4 py-2.5 rounded-lg bg-[#3B82F6] text-white text-sm font-bold hover:bg-[#2563EB] text-center">
                        View Challenge
                    </a>
                    <a :href="cancelUrl" class="flex-1 px-4 py-2.5 rounded-lg border border-[#E2E8F0] text-[#1E3A5F] text-sm font-bold hover:bg-[#F8FAFC] text-center">
                        {{ lockedClient || selectedClient ? "Back to Client" : "Back to Challenges" }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const TYPE_LABELS = {
    1: { label: 'One-Step', blurb: "Hit one profit target and they're funded" },
    2: { label: 'Two-Step', blurb: 'Two phases, industry-standard structure' },
    3: { label: 'Three-Step', blurb: 'Three progressive targets for cautious traders' },
    0: { label: 'Instant Funding', blurb: 'Skip the challenge, fund immediately' },
};

const OrderSummary = {
    props: ['rows', 'total', 'freeBadge', 'originalPrice', 'rules', 'whatYouGet', 'cta', 'secondaryCta', 'footnote'],
    emits: ['cta', 'secondary'],
    template: `
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 sticky top-6">
            <h3 class="text-[#1E3A5F] text-lg font-bold mb-4">Grant Summary</h3>
            <div class="flex flex-col gap-2.5 mb-4">
                <div v-for="r in rows" :key="r.label" class="flex items-center justify-between text-sm">
                    <span class="text-[#64748B]">{{ r.label }}</span>
                    <span class="text-[#1E3A5F] font-bold">{{ r.value }}</span>
                </div>
            </div>
            <div class="border-t border-[#F1F5F9] pt-3 mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-[#64748B] text-sm">Total</span>
                    <span class="text-2xl font-bold text-[#15803D]">FREE</span>
                </div>
                <p v-if="originalPrice" class="text-[#94A3B8] text-xs text-right mt-0.5">normally {{ money(originalPrice) }}</p>
            </div>

            <div v-if="rules && rules.length > 0" class="rounded-lg bg-[#F8FAFC] p-3 mb-4">
                <p class="text-[10px] font-bold text-[#94A3B8] uppercase mb-2">Challenge Rules</p>
                <div v-for="r in rules" :key="r.label" class="flex items-center justify-between text-xs py-1">
                    <span class="text-[#64748B]">{{ r.label }}</span>
                    <span class="text-[#1E3A5F] font-semibold">{{ r.value }}</span>
                </div>
            </div>

            <div v-if="whatYouGet" class="rounded-lg bg-[#F8FAFC] p-3 mb-4">
                <p class="text-[10px] font-bold text-[#94A3B8] uppercase mb-2">What The Client Gets</p>
                <div v-for="line in whatYouGet" :key="line" class="flex items-start gap-1.5 text-xs py-0.5">
                    <span class="text-[#22C55E] shrink-0">&#10003;</span>
                    <span class="text-[#334155]">{{ line }}</span>
                </div>
            </div>

            <button
                type="button"
                @click="$emit('cta')"
                :disabled="cta.disabled"
                class="w-full px-4 py-2.5 rounded-lg bg-[#3B82F6] text-white text-sm font-bold hover:bg-[#2563EB] disabled:opacity-40 disabled:cursor-not-allowed"
            >
                {{ cta.label }}
            </button>
            <button v-if="secondaryCta" type="button" @click="$emit('secondary')" class="w-full mt-2 text-center text-[#64748B] text-sm font-semibold">
                {{ secondaryCta.label }}
            </button>
            <p v-if="footnote" class="text-[11px] text-[#94A3B8] text-center mt-3">{{ footnote }}</p>
        </div>
    `,
    methods: {
        money(n) {
            return '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
        },
    },
};

const CredentialRow = {
    props: ['label', 'value', 'last'],
    data() {
        return { copied: false };
    },
    template: `
        <div class="flex items-center justify-between py-3" :class="last ? '' : 'border-b border-[#F1F5F9]'">
            <span class="text-[#64748B] text-sm">{{ label }}</span>
            <span class="text-[#1E3A5F] text-sm font-mono font-bold flex items-center gap-2">
                {{ value }}
                <button type="button" @click="copy" class="text-[#94A3B8] hover:text-[#3B82F6]">
                    <svg v-if="copied" class="w-3.5 h-3.5 text-[#22C55E]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                </button>
            </span>
        </div>
    `,
    methods: {
        copy() {
            navigator.clipboard.writeText(this.value).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },
    },
};

export default {
    name: 'AdminGrantChallengeWizard',
    components: { OrderSummary, CredentialRow },
    props: {
        plans: { type: Array, default: () => [] },
        addonCatalog: { type: Array, default: () => [] },
        customers: { type: Array, default: () => [] },
        preselectedUserId: { type: String, default: null },
        storeUrl: { type: String, required: true },
        usersShowUrlTemplate: { type: String, required: true },
        challengesIndexUrl: { type: String, required: true },
    },
    data() {
        return {
            step: 1,
            typeLabels: TYPE_LABELS,
            typeKey: null,
            accountSize: null,
            drawdownType: 'static',
            selectedAddons: [],
            note: '',
            selectedUserId: this.preselectedUserId,
            clientSearch: '',
            submitting: false,
            error: '',
            granted: null,
        };
    },
    computed: {
        steps() {
            return ['Select', 'Add-ons', 'Grant'];
        },
        lockedClient() {
            if (!this.preselectedUserId) return null;
            return this.customers.find((c) => c.id === this.preselectedUserId) || null;
        },
        selectedClient() {
            return this.customers.find((c) => c.id === this.selectedUserId) || null;
        },
        cancelUrl() {
            const clientId = this.preselectedUserId || this.selectedUserId;
            return clientId ? this.usersShowUrlTemplate.replace(':id', clientId) : this.challengesIndexUrl;
        },
        filteredClients() {
            const q = this.clientSearch.trim().toLowerCase();
            const list = q
                ? this.customers.filter((c) => c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q))
                : this.customers;
            return list.slice(0, 50);
        },
        plansByPhaseCount() {
            const groups = {};
            for (const plan of this.plans) {
                const key = plan.phases.length;
                if (!groups[key]) groups[key] = [];
                groups[key].push(plan);
            }
            return groups;
        },
        availableTypes() {
            return [1, 2, 3, 0].filter((k) => (this.plansByPhaseCount[k] || []).length > 0);
        },
        plansForType() {
            return this.typeKey !== null ? (this.plansByPhaseCount[this.typeKey] || []) : [];
        },
        sizesForType() {
            const sizes = this.plansForType.map((p) => p.account_size);
            return [...new Set(sizes)].sort((a, b) => a - b);
        },
        plansForSize() {
            return this.plansForType.filter((p) => p.account_size === this.accountSize);
        },
        drawdownTypesAvailable() {
            return new Set(this.plansForSize.map((p) => (p.phases[0] ? p.phases[0].drawdown_type : 'static')));
        },
        selectedPlan() {
            if (this.typeKey === null || this.accountSize === null) return null;
            const match = this.plansForSize.find((p) => (p.phases[0] ? p.phases[0].drawdown_type : 'static') === this.drawdownType);
            return match || this.plansForSize[0] || null;
        },
        firstPhase() {
            return this.selectedPlan && this.selectedPlan.phases[0] ? this.selectedPlan.phases[0] : null;
        },
        canContinueStep1() {
            const hasClient = this.preselectedUserId || this.selectedUserId;
            return Boolean(hasClient && this.selectedPlan);
        },
        canSubmit() {
            return Boolean((this.preselectedUserId || this.selectedUserId) && this.selectedPlan);
        },
    },
    methods: {
        money(n) {
            return '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
        },
        selectType(key) {
            this.typeKey = key;
            this.accountSize = null;
        },
        planHasFreeTrial(size) {
            return this.plansForType.some((p) => p.account_size === size && p.free_trial_enabled);
        },
        toggleAddon(key) {
            const i = this.selectedAddons.indexOf(key);
            if (i === -1) this.selectedAddons.push(key);
            else this.selectedAddons.splice(i, 1);
        },
        addonLabel(key) {
            const addon = this.addonCatalog.find((a) => a.key === key);
            return addon ? addon.label : key;
        },
        async submit() {
            if (!this.selectedPlan || this.submitting) return;
            this.submitting = true;
            this.error = '';
            try {
                const { data } = await window.axios.post(this.storeUrl, {
                    user_id: this.preselectedUserId || this.selectedUserId,
                    challenge_plan_id: this.selectedPlan.id,
                    addons: this.selectedAddons,
                    note: this.note,
                }, {
                    headers: { Accept: 'application/json' },
                });
                this.granted = data.data;
                this.step = 4;
            } catch (e) {
                this.error = (e.response && e.response.data && e.response.data.message) || 'Failed to grant this challenge.';
            } finally {
                this.submitting = false;
            }
        },
    },
    watch: {
        accountSize() {
            if (this.accountSize !== null && !this.drawdownTypesAvailable.has(this.drawdownType)) {
                const first = this.drawdownTypesAvailable.values().next().value;
                if (first) this.drawdownType = first;
            }
        },
        sizesForType(sizes) {
            if (this.typeKey !== null && this.accountSize === null && sizes.length === 1) {
                this.accountSize = sizes[0];
            }
        },
    },
};
</script>

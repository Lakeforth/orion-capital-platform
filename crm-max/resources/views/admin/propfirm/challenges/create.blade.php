<x-admin-layout>
    <x-slot name="header">Grant a Challenge</x-slot>

    <p class="text-sm text-gray-500 mb-4">
        Same steps as the client-area "Buy a Challenge" flow -- no charge, no wallet debit. The plan must be active
        and the client's open-challenge limit still applies.
    </p>

    <AdminGrantChallengeWizard
        :plans='@json($plans)'
        :addon-catalog='@json($addonCatalog)'
        :customers='@json($customers)'
        :preselected-user-id='@json($selectedUserId)'
        store-url="{{ route('admin.propfirm.challenges.store') }}"
        users-show-url-template="{{ route('admin.users.show', ':id') }}?tab=challenges"
        challenges-index-url="{{ route('admin.propfirm.challenges.index') }}"
    />
</x-admin-layout>

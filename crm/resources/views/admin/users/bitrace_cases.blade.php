<div class="mt-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recovery</h2>
            <p class="text-sm text-gray-500 mt-0.5">Recovery Module</p>
        </div>
        <Link modal href="{{ route('admin.user.bitrace.create', $user->id) }}"
              class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Open New Case
        </Link>
    </div>

    @if($cases->isEmpty())
        <div class="text-center py-12 text-gray-500">
            No cases found for this client.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 bg-white text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Case ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Current Step</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned Staff</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Date Created</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($cases as $case)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <Link href="{{ route('admin.user.bitrace.case.show', [$user->id, $case->id]) }}"
                                      class="font-mono text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $case->case_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $case->current_stage ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($case->assignedManager)
                                    {{ $case->assignedManager->first_name }} {{ $case->assignedManager->last_name }}
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $case->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    href="{{ route('admin.user.bitrace.case.destroy', [$user->id, $case->id]) }}"
                                    method="delete"
                                    confirm="Delete case {{ $case->case_number }}? This cannot be undone."
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                    title="Delete case"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

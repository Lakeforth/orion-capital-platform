@if($records->count())
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Lead</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Phone</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Source</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Campaign</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach($records as $lead)
                    <tr>
                        <td class="px-4 py-3">
                            <Link href="{{ route('admin.users.show', $lead->id) }}" class="font-medium text-[#0d63cc] hover:underline">
                                {{ $lead->name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $lead->email }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $lead->phone ?: 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ ucfirst($lead->status ?? 'pending') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $lead->source ?: 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $lead->campaign ?: 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $lead->created_at?->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
@else
    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
        No leads found for this {{ $profileType }}.
    </div>
@endif

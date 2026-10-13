<div class="flex items-center gap-3 text-gray-500">
    @if($recommendation->status !== \App\Models\AiRecommendation::STATUS_SENT || $recommendation->schedule_type === \App\Models\AiRecommendation::SCHEDULE_RECURRING)
        <Link
            href="{{ route('admin.ai-recommendations.trigger', ['user' => $recommendation->user_id, 'aiRecommendation' => $recommendation->id, 'redirect_to' => $redirectTo ?? 'customers_ai_recommendations']) }}"
            method="POST"
            title="Deliver now"
            class="text-teal-700 hover:text-teal-900"
        >
            {{ svg('heroicon-o-chart-bar', 'w-4 h-4') }}
        </Link>
    @endif

    <Link
        modal
        href="{{ route('admin.ai-recommendations.edit', ['user' => $recommendation->user_id, 'aiRecommendation' => $recommendation->id, 'redirect_to' => $redirectTo ?? 'customers_ai_recommendations']) }}"
        title="Edit"
        class="hover:text-gray-800"
    >
        {{ svg('heroicon-o-pencil', 'w-4 h-4') }}
    </Link>

    <Link
        href="{{ route('admin.ai-recommendations.destroy', ['user' => $recommendation->user_id, 'aiRecommendation' => $recommendation->id, 'redirect_to' => $redirectTo ?? 'customers_ai_recommendations']) }}"
        method="DELETE"
        confirm="Delete recommendation"
        confirm-text="Are you sure you want to delete this AI recommendation?"
        confirm-button="Delete"
        cancel-button="Cancel"
        title="Delete"
        class="hover:text-red-600"
    >
        {{ svg('heroicon-o-trash', 'w-4 h-4') }}
    </Link>
</div>

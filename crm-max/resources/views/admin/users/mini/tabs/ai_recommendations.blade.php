<div class="mini-panel p-4">
    @include('admin.users.partials.ai_recommendations', [
        'aiRecommendationTable' => $aiRecommendationTable,
        'redirectTo' => 'user_ai_recommendations',
    ])
</div>

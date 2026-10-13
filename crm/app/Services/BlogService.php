<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class BlogService
{
    public function getFeaturedPosts($limit = 3)
    {
        $cacheKey = 'featured_posts_' . $limit;

        $cachedCategories = Cache::get($cacheKey);

        if ($cachedCategories) {
            return $cachedCategories;
        }

        $categories = Post::active()->inRandomOrder()
            ->where('featured', true)
            ->limit($limit)->get();
        Cache::put($cacheKey, $categories, now()->addHours(2));

        return $categories;
    }


    public function getPostQuery($request, $categoryId = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = Post::query();

        if($categoryId){
            // Filter by category
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }


        // Keyword search
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%$keyword%")
                    ->orWhere('excerpt', 'like', "%$keyword%")
                    ->orWhereJsonContains('tags', $keyword);
            });
        }

        // Order by
        $sort_by = $request->input('sort_by', 'popular');

        switch ($sort_by) {

            case 'latest':
                $query->orderBy('created_at','desc');
                break;

            case 'oldest':
                $query->orderBy('created_at','asc');
                break;

            default:
                $query->orderBy('title', 'asc');
                break;
        }

        return $query;

    }

    public function getPosts($request, $categoryId = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getPostQuery($request, $categoryId);
        // Pagination
        $perPage = $request->has('per_page') ? $request->input('per_page') : 12;

        return $query->paginate($perPage);
    }

    public function getPostsByCategory($request, $category, $limit = 5){

        $cacheKey = 'products_category_' . $category . '_' . serialize($request->all());

        // Attempt to retrieve data from cache
        $cachedProducts = Cache::get($cacheKey);

        if ($cachedProducts) {
            return $cachedProducts;
        }

        $query = $this->getProductQuery($request, $category);

        // Execute the query and cache the result
        $products = $query->limit($limit)->get();

        Cache::put($cacheKey, $products, now()->addMinutes(60)); // Cache for 60 minutes

        return $products;

    }


}

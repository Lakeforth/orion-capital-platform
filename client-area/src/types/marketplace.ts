export interface MarketplaceItem {
    id: string;
    title: string;
    description: string;
    price: number;
    currency: string;
    image: string;
    category: string;
    paymentType: "monthly" | "one-time";
    status: "available" | "purchased";
    rating: number;
    badge?: string;
    features?: string[];
    author?: string;
    downloads?: number;
    lastUpdated?: string;
    version?: string;
}

export interface MarketplaceFilter {
    value: string;
    label: string;
    count?: number;
}

export interface MarketplaceFilters {
    categories: MarketplaceFilter[];
    secondary: MarketplaceFilter[];
}

export interface MarketplaceSearchParams {
    search?: string;
    category?: string;
    filter?: string;
    page?: number;
    limit?: number;
    sortBy?: "price" | "rating" | "title" | "date";
    sortOrder?: "asc" | "desc";
}

export interface MarketplaceApiResponse {
    items: MarketplaceItem[];
    total: number;
    page: number;
    limit: number;
    totalPages: number;
    filters: MarketplaceFilters;
}

export type CategoryFilter = "all" | "expert_advisor" | "others";
export type SecondaryFilter =
    | "popular"
    | "free"
    | "purchased";

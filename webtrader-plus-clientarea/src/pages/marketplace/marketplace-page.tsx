import { useMemo, useCallback } from "react";
import { useSearchParams } from "react-router-dom";
import { Search } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import { toast } from "@/components/ui/sonner";
import useUserStore from "@/store/userStore";

// Components
import MarketplaceCard from "@/components/marketplace/marketplace-card";
import MarketplaceFilters from "@/components/marketplace/marketplace-filters";
// import LoadingScreen from "@/components/loading-screen";

// Types and constants
import type { MarketplaceItem, CategoryFilter, SecondaryFilter } from "@/types/marketplace";
import {
    CATEGORY_FILTERS,
    SECONDARY_FILTERS,
    DEFAULT_CATEGORY,
    DEFAULT_SECONDARY,
    filterByCategory,
    filterBySecondary,
    filterBySearch,
} from "@/constants/marketplace";

// Type for API response items
type ExpertAdvisorApiItem = {
    id: string;
    name: string;
    rating: string;
    amount: string;
    amount_val: string;
    type: string;
    popular: number;
    downloads: number;
    description: string;
    slug: string;
    image: string;
};

export default function MarketplacePage() {
    const [searchParams, setSearchParams] = useSearchParams();

    // State management
    const searchTerm = searchParams.get("search") || "";
    const selectedCategory = (searchParams.get("category") as CategoryFilter) || DEFAULT_CATEGORY;
    const selectedSecondary = (searchParams.get("filter") as SecondaryFilter) || DEFAULT_SECONDARY;

    // Fetch expert advisors using React Query
    const { data: apiItems, isLoading, error } = useQuery({
        queryKey: ["expertAdvisors"],
        queryFn: async () => {
            const response = await axiosInstance.get<{ data: ExpertAdvisorApiItem[] }>("/expert_advisors");
            return response.data.data;
        },
    });

    // Map API items to MarketplaceItem format
    const items = useMemo(() => {
        if (!apiItems) return [];
        return apiItems.map((item): MarketplaceItem => ({
            id: item.id,
            title: item.name,
            description: item.description,
            price: parseFloat(item.amount_val) || parseFloat(item.amount) || 0,
            currency: "USD",
            image: item.image,
            category: item.type,
            paymentType: "one-time",
            status: "available",
            rating: Number(item.rating) || 0,
            features: [],
            author: "",
            downloads: item.downloads,
        }));
    }, [apiItems]);

    // Update URL parameters when filters change
    const updateSearchParams = useCallback((key: string, value: string) => {
        const params = new URLSearchParams(searchParams);
        if (value && value !== (key === "category" ? DEFAULT_CATEGORY : DEFAULT_SECONDARY)) {
            params.set(key, value);
        } else {
            params.delete(key);
        }
        setSearchParams(params, { replace: true });
    }, [searchParams, setSearchParams]);

    // Handlers
    const handleSearchChange = useCallback((value: string) => {
        updateSearchParams("search", value);
    }, [updateSearchParams]);

    const handleCategoryChange = useCallback((category: CategoryFilter) => {
        updateSearchParams("category", category);
    }, [updateSearchParams]);

    const handleSecondaryChange = useCallback((filter: SecondaryFilter) => {
        updateSearchParams("filter", filter);
    }, [updateSearchParams]);

    const getCurrentUser = useUserStore((state) => state.getCurrentUser);

    const handleItemAction = useCallback((item: MarketplaceItem) => {
        if (item.status === "purchased") {
            console.log("Already purchased:", item.title);
        } else {
            console.log("Purchasing:", item.title);

            // Make a POST request to purchase the expert advisor
            axiosInstance.post("/purchase/ea", { expert_advisor_id: item.id })
                .then((response) => {
                    if (response.data.status === "success") {
                        // Show success toast
                        toast.success("Expert Advisor purchased successfully");

                        // Refresh user data
                        getCurrentUser();
                    } else {
                        // Show error toast if status is not success
                        toast.error(response.data.message || "Failed to purchase Expert Advisor");
                    }
                })
                .catch((error) => {
                    // Show error toast with the error message from the response
                    const errorMessage = error.response?.data?.message ||
                                        error.response?.data?.error ||
                                        "Failed to purchase Expert Advisor";
                    toast.error(errorMessage);
                });
        }
    }, [getCurrentUser]);

    // Filter items
    const filteredItems = useMemo(() => {
        let filtered = items;
        filtered = filterByCategory(filtered, selectedCategory);
        filtered = filterBySecondary(filtered, selectedSecondary);
        filtered = filterBySearch(filtered, searchTerm);
        return filtered;
    }, [items, searchTerm, selectedCategory, selectedSecondary]);

    // Loading state
    if (isLoading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                Loading...
            </div>
        );
    }

    // Error state
    if (error) {
        return (
            <div className="min-h-screen bg-background flex flex-col items-center justify-center">
                <p className="text-red-500 mb-4">Failed to load marketplace items</p>
                <Button variant="outline" onClick={() => window.location.reload()}>
                    Retry
                </Button>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background">
            <div className="container- mx-auto px-6 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-foreground mb-2">MARKET PLACE</h1>
                </div>

                {/* Search Bar */}
                <div className="mb-6">
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search market place"
                            className="pl-10 bg-background border-border"
                            value={searchTerm}
                            onChange={(e) => handleSearchChange(e.target.value)}
                        />
                    </div>
                </div>

                {/* Filters */}
                <MarketplaceFilters
                    categoryFilters={CATEGORY_FILTERS}
                    secondaryFilters={SECONDARY_FILTERS}
                    selectedCategory={selectedCategory}
                    selectedSecondary={selectedSecondary}
                    onCategoryChange={handleCategoryChange}
                    onSecondaryChange={handleSecondaryChange}
                />

                {/* Items Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                    {filteredItems.map((item) => (
                        <MarketplaceCard
                            key={item.id}
                            item={item}
                            onAction={handleItemAction}
                        />
                    ))}
                </div>

                {/* No Results */}
                {filteredItems.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-muted-foreground">No items found matching your criteria.</p>
                        <Button
                            variant="outline"
                            className="mt-4"
                            onClick={() => {
                                handleSearchChange("");
                                handleCategoryChange(DEFAULT_CATEGORY);
                                handleSecondaryChange(DEFAULT_SECONDARY);
                            }}
                        >
                            Clear Filters
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}

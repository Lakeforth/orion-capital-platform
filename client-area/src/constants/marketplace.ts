
import type { MarketplaceItem,  CategoryFilter, SecondaryFilter } from "@/types/marketplace";

export const CATEGORY_FILTERS: readonly { value: CategoryFilter; label: string }[] = [
    { value: "all", label: "All" },
    { value: "expert_advisor", label: "Expert Advisor" },
    { value: "others", label: "Others" },
] as const;

export const SECONDARY_FILTERS: readonly { value: SecondaryFilter; label: string }[] = [
    { value: "popular", label: "Popular" },
    { value: "popular", label: "Popular" },
    { value: "free", label: "Free" },
    { value: "purchased", label: "My Purchased" },
] as const;

// Default filter values
export const DEFAULT_CATEGORY: CategoryFilter = "all";
export const DEFAULT_SECONDARY: SecondaryFilter = "popular";

// Filter functions for easier maintenance
export const filterByCategory = (items: MarketplaceItem[], category: CategoryFilter): MarketplaceItem[] => {
    if (category === "all") return items;
    return items.filter(item => item.category === category);
};

export const filterBySecondary = (items: MarketplaceItem[], filter: SecondaryFilter): MarketplaceItem[] => {
    switch (filter) {
        case "free":
            return items.filter(item => item.price === 0);
        case "purchased":
            return items.filter(item => item.status === "purchased");
        // case "new":
        //     // Sort by lastUpdated descending
        //     return [...items].sort((a, b) =>
        //         new Date(b.lastUpdated || 0).getTime() - new Date(a.lastUpdated || 0).getTime()
        //     );
        case "popular":
        default:
            // Sort by downloads descending
            return [...items].sort((a, b) => (b.downloads || 0) - (a.downloads || 0));
    }
};

export const filterBySearch = (items: MarketplaceItem[], searchTerm: string): MarketplaceItem[] => {
    if (!searchTerm.trim()) return items;

    const searchLower = searchTerm.toLowerCase();
    return items.filter(item =>
        item.title.toLowerCase().includes(searchLower) ||
        item.description.toLowerCase().includes(searchLower) ||
        item.author?.toLowerCase().includes(searchLower) ||
        item.features?.some(feature => feature.toLowerCase().includes(searchLower))
    );
};

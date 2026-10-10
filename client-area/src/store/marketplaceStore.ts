
import { create } from "zustand";
import { persist } from "zustand/middleware";
import axiosInstance from "@/lib/axios";
import type {
    MarketplaceItem,
    MarketplaceApiResponse,
    MarketplaceSearchParams
} from "@/types/marketplace";

interface MarketplaceStore {
    // State
    items: MarketplaceItem[];
    isLoading: boolean;
    error: string | null;
    total: number;
    currentPage: number;
    totalPages: number;

    // User's purchased items
    purchasedItems: string[];

    // Actions
    fetchItems: (params?: MarketplaceSearchParams) => Promise<void>;
    purchaseItem: (itemId: string) => Promise<void>;
    setPurchasedItems: (itemIds: string[]) => void;
    clearError: () => void;
    setItems: (items: MarketplaceItem[]) => void;
}

const useMarketplaceStore = create<MarketplaceStore>()(
    persist(
        (set, get) => ({
            // Initial state
            items: [],
            isLoading: false,
            error: null,
            total: 0,
            currentPage: 1,
            totalPages: 1,
            purchasedItems: [],

            // Fetch marketplace items from API
            fetchItems: async (params = {}) => {
                set({ isLoading: true, error: null });

                try {
                    const response = await axiosInstance.get<MarketplaceApiResponse>("/marketplace", {
                        params,
                    });

                    const { items, total, page, totalPages } = response.data;

                    set({
                        items,
                        total,
                        currentPage: page,
                        totalPages,
                        isLoading: false,
                    });
                } catch (error) {
                    console.error("Failed to fetch marketplace items:", error);
                    set({
                        error: "Failed to load marketplace items. Please try again later.",
                        isLoading: false,
                    });
                }
            },

            // Purchase an item
            purchaseItem: async (itemId: string) => {
                set({ isLoading: true, error: null });

                try {
                    await axiosInstance.post(`/marketplace/${itemId}/purchase`);

                    // Update local state
                    const { purchasedItems, items } = get();
                    const updatedPurchasedItems = [...purchasedItems, itemId];
                    const updatedItems = items.map(item =>
                        item.id === itemId ? { ...item, status: "purchased" as const } : item
                    );

                    set({
                        purchasedItems: updatedPurchasedItems,
                        items: updatedItems,
                        isLoading: false,
                    });
                } catch (error) {
                    console.error("Failed to purchase item:", error);
                    set({
                        error: "Failed to purchase item. Please try again.",
                        isLoading: false,
                    });
                }
            },

            // Set purchased items (usually called on app init)
            setPurchasedItems: (itemIds: string[]) => {
                const { items } = get();
                const updatedItems = items.map(item => ({
                    ...item,
                    status: itemIds.includes(item.id) ? "purchased" as const : item.status,
                }));

                set({
                    purchasedItems: itemIds,
                    items: updatedItems,
                });
            },

            // Clear error state
            clearError: () => set({ error: null }),

            // Set items (for testing or manual updates)
            setItems: (items: MarketplaceItem[]) => set({ items }),
        }),
        {
            name: "marketplace-storage",
            // Only persist purchased items to avoid stale data
            partialize: (state) => ({
                purchasedItems: state.purchasedItems
            }),
        }
    )
);

export default useMarketplaceStore;

import { create } from "zustand";
import { persist } from "zustand/middleware";

interface CurrencyStore {
    selectedCurrencyCode: string;
    setSelectedCurrencyCode: (code: string) => void;
}

const useCurrencyStore = create<CurrencyStore>()(
    persist(
        (set) => ({
            selectedCurrencyCode: 'USD', // Default to USD
            setSelectedCurrencyCode: (code) => set({ selectedCurrencyCode: code }),
        }),
        {
            name: "currency-storage",
        }
    )
);

export default useCurrencyStore;

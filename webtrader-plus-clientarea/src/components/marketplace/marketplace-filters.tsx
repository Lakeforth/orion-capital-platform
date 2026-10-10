import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { CategoryFilter, SecondaryFilter } from "@/types/marketplace";

interface FilterOption<T> {
    value: T;
    label: string;
}

interface MarketplaceFiltersProps {
    categoryFilters: readonly FilterOption<CategoryFilter>[];
    secondaryFilters: readonly FilterOption<SecondaryFilter>[];
    selectedCategory: CategoryFilter;
    selectedSecondary: SecondaryFilter;
    onCategoryChange: (category: CategoryFilter) => void;
    onSecondaryChange: (filter: SecondaryFilter) => void;
}

export default function MarketplaceFilters({
                                               categoryFilters,
                                               secondaryFilters,
                                               selectedCategory,
                                               selectedSecondary,
                                               onCategoryChange,
                                               onSecondaryChange,
                                           }: MarketplaceFiltersProps) {
    return (
        <div className="space-y-4 mb-8">
            {/* Category Filters */}
            <div>
                <div className="flex gap-2 flex-wrap">
                    {categoryFilters.map((filter) => (
                        <Button
                            key={filter.value}
                            variant={selectedCategory === filter.value ? "default" : "outline"}
                            size="sm"
                            className={cn(
                                "rounded-full px-4 py-2 text-sm font-medium transition-all",
                                selectedCategory === filter.value
                                    ? "bg-muted text-muted-foreground"
                                    : "bg-transparent text-muted-foreground hover:bg-muted/50"
                            )}
                            onClick={() => onCategoryChange(filter.value)}
                        >
                            {filter.label}
                        </Button>
                    ))}
                </div>
            </div>

            {/* Secondary Filters */}
            <div>
                <div className="flex gap-2 flex-wrap">
                    {secondaryFilters.map((filter) => (
                        <Button
                            key={filter.value}
                            variant={selectedSecondary === filter.value ? "default" : "outline"}
                            size="sm"
                            className={cn(
                                "rounded-full px-4 py-2 text-sm font-medium transition-all",
                                selectedSecondary === filter.value
                                    ? "bg-muted text-muted-foreground"
                                    : "bg-transparent text-muted-foreground hover:bg-muted/50"
                            )}
                            onClick={() => onSecondaryChange(filter.value)}
                        >
                            {filter.label}
                        </Button>
                    ))}
                </div>
            </div>
        </div>
    );
}

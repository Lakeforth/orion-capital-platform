import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { useCurrency } from "@/hooks/useCurrency";
import { Loader2 } from "lucide-react";

export function CurrencySelector() {
    const { selectedCurrency, currencies, setSelectedCurrency, isLoading } = useCurrency();

    if (isLoading) {
        return (
            <div className="flex justify-center p-4">
                <div className="flex items-center gap-2 text-muted-foreground">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Loading currencies...
                </div>
            </div>
        );
    }

    if (!selectedCurrency || currencies.length === 0) {
        return (
            <div className="text-center text-muted-foreground p-4">
                No currencies available
            </div>
        );
    }

    return (
        <RadioGroup
            value={selectedCurrency.code}
            onValueChange={setSelectedCurrency}
            className="grid grid-cols-[repeat(auto-fill,minmax(5.5rem,1fr))] gap-x-3 gap-y-2"
        >
            {currencies.map((curr) => (
                <div key={curr.id} className="flex min-w-0 items-center gap-2">
                    <RadioGroupItem
                        value={curr.code}
                        id={`currency-${curr.code}`}
                        className="shrink-0 border-primary/20"
                    />
                    <Label
                        htmlFor={`currency-${curr.code}`}
                        className="flex min-w-0 items-center gap-1 cursor-pointer"
                    >
                        <span className="w-5 shrink-0 text-center">{curr.icon}</span>
                        <span className="truncate">{curr.code}</span>
                    </Label>
                </div>
            ))}
        </RadioGroup>
    );
}

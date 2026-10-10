import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import type { MarketplaceItem } from "@/types/marketplace";
import { useState } from "react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

interface MarketplaceCardProps {
    item: MarketplaceItem;
    onAction: (item: MarketplaceItem) => void;
}

export default function MarketplaceCard({ item, onAction }: MarketplaceCardProps) {
    const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

    // Render star rating
    const renderStars = (rating: number) => {
        return (
            <div className="flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((star) => (
                    <div
                        key={star}
                        className={cn(
                            "w-3 h-3 text-xs",
                            star <= rating ? "text-yellow-400" : "text-gray-300"
                        )}
                    >
                        ★
                    </div>
                ))}
            </div>
        );
    };


    const handlePurchaseClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        setIsPurchaseModalOpen(true);
    };

    const handlePurchaseConfirm = () => {
        setIsPurchaseModalOpen(false);
        onAction(item);
    };

    return (
        <>
            <Card
                className="bg-card border-border hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden group"
                onClick={() => onAction(item)}
            >
                <div className="relative">
                    {/* Item Image */}
                    <div className="aspect-[4/3] relative overflow-hidden">
                        <img
                            src={item.image}
                            alt={item.title}
                            className="w-full h-full object-cover"
                        />

                        {item.badge && (
                            <Badge className="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 z-10">
                                {item.badge}
                            </Badge>
                        )}

                        {/* Status indicator for purchased items */}
                        {item.status === "purchased" && (
                            <Badge className="absolute top-2 right-2 bg-green-600 text-white text-xs px-2 py-1 z-10">
                                Owned
                            </Badge>
                        )}

                        {/* Content overlay */}
                        <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <div className="text-white text-center px-4">
                                <div className="text-lg font-bold mb-1 leading-tight">{item.title}</div>
                                <div className="text-xs opacity-80">Expert Advisors</div>
                            </div>
                        </div>
                    </div>
                </div>

                <CardContent className="p-4">
                    {/* Title and Rating */}
                    <div className="mb-3">
                        <h3 className="font-semibold text-foreground text-sm mb-1 truncate" title={item.title}>
                            {item.title}
                        </h3>
                        <div className="flex items-center justify-between">
                            {renderStars(item.rating)}
                            {item.downloads && (
                                <span className="text-xs text-muted-foreground">
                                    {item.downloads.toLocaleString()} downloads
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Price and Action */}
                    <div className="flex items-center justify-between">
                        <div className="flex flex-col">
                            <div className="text-lg font-bold text-foreground">
                                {item.price === 0 ? "Free" : `${item.price.toLocaleString()} ${item.currency}`}
                            </div>
                            {item.paymentType === "monthly" && (
                                <span className="text-xs text-muted-foreground">/month</span>
                            )}
                        </div>

                        <Button
                            size="sm"
                            variant={item.status === "purchased" ? "outline" : "default"}
                            className={cn(
                                "px-4 py-1 text-xs font-medium cursor-pointer",
                                item.status === "purchased"
                                    ? "bg-transparent text-muted-foreground border-border cursor-default"
                                    : "bg-green-600 text-white hover:bg-green-700"
                            )}
                            onClick={handlePurchaseClick}
                            disabled={item.status === "purchased"}
                        >
                            {item.status === "purchased" ? "Purchased" : "Purchase"}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Purchase Confirmation Modal */}
            <Dialog open={isPurchaseModalOpen} onOpenChange={setIsPurchaseModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirm Purchase</DialogTitle>
                        <DialogDescription className="pt-4">
                            You are about to purchase <span className="font-semibold">{item.title}</span> for{" "}
                            <span className="font-semibold">{item.price.toLocaleString()} {item.currency}</span>.
                            <br /><br />
                            This amount will be deducted from your account balance.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="mt-4">
                        <Button
                            variant="outline"
                            onClick={() => setIsPurchaseModalOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handlePurchaseConfirm}
                            className="bg-green-600 cursor-pointer text-white hover:bg-green-700"
                        >
                            Proceed
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

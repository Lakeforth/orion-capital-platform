import { Button } from "@/components/ui/button";
import { Dialog, DialogContent } from "@/components/ui/dialog";

interface MobileFilterDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function MobileFilterDialog({
                                       open,
                                       onOpenChange,
                                   }: MobileFilterDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <div className="space-y-4">
                    <h3 className="text-lg font-medium">Filter Orders</h3>

                    <div className="space-y-2">
                        <div className="text-sm font-medium">Order Type</div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" className="flex-1">
                                All
                            </Button>
                            <Button variant="outline" size="sm" className="flex-1">
                                Buy
                            </Button>
                            <Button variant="outline" size="sm" className="flex-1">
                                Sell
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <div className="text-sm font-medium">Symbol</div>
                        <div className="grid grid-cols-2 gap-2">
                            <Button variant="outline" size="sm">
                                All
                            </Button>
                            <Button variant="outline" size="sm">
                                Crypto
                            </Button>
                            <Button variant="outline" size="sm">
                                Forex
                            </Button>
                            <Button variant="outline" size="sm">
                                Stocks
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <div className="text-sm font-medium">Date Range</div>
                        <div className="grid grid-cols-2 gap-2">
                            <Button variant="outline" size="sm">
                                Today
                            </Button>
                            <Button variant="outline" size="sm">
                                This Week
                            </Button>
                            <Button variant="outline" size="sm">
                                This Month
                            </Button>
                            <Button variant="outline" size="sm">
                                All Time
                            </Button>
                        </div>
                    </div>

                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button onClick={() => onOpenChange(false)}>Apply Filters</Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

export default MobileFilterDialog;

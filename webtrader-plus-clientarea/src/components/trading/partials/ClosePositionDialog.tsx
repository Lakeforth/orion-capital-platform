import { Button } from "@/components/ui/button";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Loader2, X, Ban } from "lucide-react";
import type { Trade } from "@/store/tradeStore";

interface ClosePositionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    selectedOrder: Trade | null;
    onConfirm: () => void;
    isClosing?: boolean;
}

export function ClosePositionDialog({
                                        open,
                                        onOpenChange,
                                        selectedOrder,
                                        onConfirm,
                                        isClosing = false,
                                    }: ClosePositionDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md bg-muted/90 border-none p-0">
                <div className="bg-muted/90 text-foreground rounded-md">
                    <div className="p-4 space-y-4">
                        <div className="text-center font-medium">
                            CLOSE POSITION #{selectedOrder?.id.substring(0, 8)}
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Are you sure to close the position{" "}
                            {selectedOrder?.trade_type.toUpperCase()}{" "}
                            {(selectedOrder?.volume_lots ?? selectedOrder?.volume ?? 0).toFixed(2)} {selectedOrder?.asset_symbol}?
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                className="w-full bg-red-500 hover:bg-red-600 text-white"
                                onClick={onConfirm}
                                disabled={isClosing}
                            >
                                {isClosing ? (
                                    <div className="flex items-center justify-center">
                                        <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                        <span>Closing...</span>
                                    </div>
                                ) : (
                                    <span className="inline-flex items-center"><X className="h-4 w-4 mr-2" />Yes</span>
                                )}
                            </Button>
                            <Button
                                className="w-full bg-slate-600 hover:bg-slate-700 text-white"
                                onClick={() => onOpenChange(false)}
                                disabled={isClosing}
                            >
                                <Ban className="h-4 w-4 mr-2" />
                                No
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

export default ClosePositionDialog;

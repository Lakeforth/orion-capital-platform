import { Plus, Minus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from "@/components/ui/dialog";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Form, FormControl, FormField, FormItem } from "@/components/ui/form";
import { useState, useEffect } from "react";

const formSchema = z.object({
  assetPrice: z.string(),
});

interface PendingOrderModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  currentPrice?: number;
}

export function PendingOrderModal({
  open,
  onOpenChange,
  currentPrice = 0.51954,
}: PendingOrderModalProps) {
  const [isCustomPrice, setIsCustomPrice] = useState(false);

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      assetPrice: "Current",
    },
  });

  useEffect(() => {
    // Reset to "Current" when modal opens
    if (open) {
      form.setValue("assetPrice", "Current");
      setIsCustomPrice(false);
    }
  }, [open, form]);

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    console.log("Applied pending order:", values);
    onOpenChange(false);
  };

  const handleAdjustValue = (increment: boolean) => {
    const currentValue = form.getValues("assetPrice");

    // If current value is "Current", set it to the actual price first
    if (currentValue === "Current") {
      form.setValue("assetPrice", currentPrice.toString());
      setIsCustomPrice(true);
      return;
    }

    const numValue = Number.parseFloat(currentValue);
    if (!isNaN(numValue)) {
      const step = 0.00001;
      const newValue = increment ? numValue + step : numValue - step;
      form.setValue("assetPrice", newValue.toFixed(5));
    }
  };

  const handleRevertToMarket = () => {
    form.setValue("assetPrice", "Current");
    setIsCustomPrice(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[300px] bg-[#1E2130] text-white border-gray-700 p-0 overflow-hidden">
        <DialogHeader className="p-2">
          <div className="flex justify-between items-center">
            <DialogTitle className="text-white text-base">PENDING</DialogTitle>
            <DialogClose className="h-5 w-5 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" />
          </div>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="p-2 space-y-2"
          >
            <div className="flex items-center">
              <FormField
                control={form.control}
                name="assetPrice"
                render={({ field }) => (
                  <FormItem className="flex-1">
                    <FormControl>
                      <div className="flex items-center">
                        <div className="bg-[#131722] p-2 rounded-l flex-1">
                          <div className="text-gray-400 text-xs">
                            Asset Price
                          </div>
                          <Input
                            {...field}
                            className="bg-transparent border-none p-0 text-sm text-white focus-visible:ring-0 focus-visible:ring-offset-0 h-5"
                          />
                        </div>
                        <div className="flex flex-col bg-[#131722] rounded-r h-full justify-center">
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-5 rounded-none rounded-tr text-gray-400 hover:text-white hover:bg-[#232838]"
                            onClick={() => handleAdjustValue(true)}
                          >
                            <Plus className="h-2.5 w-2.5" />
                          </Button>
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-5 rounded-none rounded-br text-gray-400 hover:text-white hover:bg-[#232838]"
                            onClick={() => handleAdjustValue(false)}
                          >
                            <Minus className="h-2.5 w-2.5" />
                          </Button>
                        </div>
                      </div>
                    </FormControl>
                  </FormItem>
                )}
              />
            </div>

            {isCustomPrice && (
              <Button
                type="button"
                variant="secondary"
                className="w-full h-7 text-sm bg-[#131722] hover:bg-[#232838] text-white border-0"
                onClick={handleRevertToMarket}
              >
                Revert to Market Price
              </Button>
            )}

            <Button type="submit" className="w-full h-7 text-sm bg-primary">
              Apply
            </Button>

            <div className="text-center text-[10px] text-gray-400">
              Position will be opened automatically when the price reaches this
              level
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

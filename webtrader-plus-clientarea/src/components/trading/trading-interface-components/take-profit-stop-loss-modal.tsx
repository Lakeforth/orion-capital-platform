import { Plus, Minus, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Form, FormControl, FormField, FormItem, FormMessage } from "@/components/ui/form";
import { useEffect, useMemo } from "react";
import { pricePrecision } from "@/lib/price-format";
import type { Asset } from "@/store/assetStore";

const formSchema = z.object({
  preset: z.string(),
  takeProfit: z.string(),
  stopLoss: z.string(),
});

interface TakeProfitStopLossModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onApplyValues?: (takeProfit: number | undefined, stopLoss: number | undefined) => void;
  currentTakeProfit?: number;
  currentStopLoss?: number;
  // Drives input precision (5dp forex, 3dp JPY pairs, 2dp everything else --
  // same convention as chart/order-box price display, see pricePrecision)
  // and the market-price fallback used to pre-populate empty fields.
  asset?: Asset | null;
  currentPrice?: number;
  tradeType?: string;
  title?: string;
  submitLabel?: string;
  isSubmitting?: boolean;
}

export function TakeProfitStopLossModal({
  open,
  onOpenChange,
  onApplyValues,
  currentTakeProfit,
  currentStopLoss,
  asset,
  currentPrice,
  tradeType,
  title = "TAKE PROFIT & STOP LOSS",
  submitLabel = "Apply",
  isSubmitting = false,
}: TakeProfitStopLossModalProps) {
  const precision = useMemo(
    () => pricePrecision(asset?.category, asset?.symbol_display || "", currentPrice || 0),
    [asset?.category, asset?.symbol_display, currentPrice]
  );
  const decimalRegex = useMemo(() => new RegExp(`^\\d*\\.?\\d{0,${precision}}$`), [precision]);
  // 100 units of the smallest displayed decimal -- e.g. $1.00 for a 2dp
  // instrument, 10 pips for a 5dp forex pair. Matches the step this modal
  // already used for 2dp instruments before precision became dynamic.
  const step = useMemo(() => 100 * Math.pow(10, -precision), [precision]);

  // An unset level stays empty. Pre-filling it with the market price meant
  // that setting only a take profit also sent a stop loss at the current
  // price, so one level could never be set without the other. The +/-
  // buttons still start from the market price (handleAdjustValue).
  const fieldDefault = (current: number | undefined) =>
    current != null && current > 0 ? current.toFixed(precision) : "";

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      preset: "default",
      takeProfit: fieldDefault(currentTakeProfit),
      stopLoss: fieldDefault(currentStopLoss),
    },
  });

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    form.clearErrors(["takeProfit", "stopLoss"]);

    const takeProfit = values.takeProfit && Number(values.takeProfit) > 0
      ? parseFloat(parseFloat(values.takeProfit).toFixed(precision))
      : undefined;
    const stopLoss = values.stopLoss && Number(values.stopLoss) > 0
      ? parseFloat(parseFloat(values.stopLoss).toFixed(precision))
      : undefined;
    const marketPrice = Number(currentPrice || 0);
    const side = tradeType?.toLowerCase();
    const isBuy = side === "buy";
    const isSell = side === "sell";

    // Give immediate feedback in WebTrader. The API repeats these checks
    // against its latest executable price, since the market may move between
    // the modal opening and the user pressing Save.
    if (marketPrice > 0 && (isBuy || isSell)) {
      if (
        takeProfit !== undefined &&
        (isBuy ? takeProfit <= marketPrice : takeProfit >= marketPrice)
      ) {
        form.setError("takeProfit", {
          type: "validate",
          message: `For a ${side?.toUpperCase()}, take profit must be ${isBuy ? "above" : "below"} the current price (${marketPrice.toFixed(precision)}).`,
        });
        return;
      }

      if (
        stopLoss !== undefined &&
        (isBuy ? stopLoss >= marketPrice : stopLoss <= marketPrice)
      ) {
        form.setError("stopLoss", {
          type: "validate",
          message: `For a ${side?.toUpperCase()}, stop loss must be ${isBuy ? "below" : "above"} the current price (${marketPrice.toFixed(precision)}).`,
        });
        return;
      }
    }

    if (onApplyValues) {
      onApplyValues(takeProfit, stopLoss);
    }

    if (!isSubmitting) {
      onOpenChange(false);
    }
  };

  // Re-seed the form when the modal opens, and only then. This used to
  // also depend on currentPrice, which is a live tick (several times a
  // second) -- form.reset() fired on every one of them, silently
  // discarding whatever the user was mid-typing into TP/SL. No value
  // could ever actually be set as a result. currentTakeProfit/
  // currentStopLoss/precision are still read (for the values that go
  // into the reset), just no longer re-trigger it while the modal is
  // already open.
  useEffect(() => {
    if (open) {
      form.reset({
        preset: "default",
        takeProfit: fieldDefault(currentTakeProfit),
        stopLoss: fieldDefault(currentStopLoss),
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  const handleAdjustValue = (
    field: "takeProfit" | "stopLoss",
    increment: boolean
  ) => {
    const currentValue = Number.parseFloat(form.getValues(field) || String(currentPrice || 0));
    const newValue = increment ? currentValue + step : Math.max(0, currentValue - step);
    form.setValue(field, newValue.toFixed(precision));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="sm:max-w-[350px] bg-[#1E2130] text-white border-gray-700 p-0 overflow-hidden"
        overlayClassName="backdrop-blur-none bg-black/10"
      >
        <DialogHeader className="p-2">
          <div className="flex justify-between items-center">
            <DialogTitle className="text-white text-xs">
              {title}
            </DialogTitle>
            <DialogClose></DialogClose>
          </div>
        </DialogHeader>
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="p-2 space-y-2"
          >
            <FormField
              control={form.control}
              name="preset"
              render={({ field }) => (
                <FormItem>
                  <Select
                    onValueChange={field.onChange}
                    defaultValue={field.value}
                  >
                    <FormControl>
                      <SelectTrigger className="w-full h-8 text-xs bg-[#131722] border-0 text-white">
                        <SelectValue placeholder="Select preset" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent className="bg-[#1E2130] text-white text-xs border-gray-700">
                      <SelectItem value="default">default</SelectItem>
                      <SelectItem value="conservative">conservative</SelectItem>
                      <SelectItem value="aggressive">aggressive</SelectItem>
                    </SelectContent>
                  </Select>
                </FormItem>
              )}
            />

            <div className="space-y-1">
              <div className="flex items-center">
                <FormField
                  control={form.control}
                  name="takeProfit"
                  render={({ field }) => (
                    <FormItem className="flex-1">
                      <FormControl>
                        <div className="flex items-center">
                          <div className="bg-[#131722] p-2 rounded-l flex-1">
                            <div className="text-gray-400 text-xs">
                              Take Profit
                              {field.value && (
                                <button
                                  type="button"
                                  aria-label="Clear take profit"
                                  className="float-right text-gray-500 hover:text-white"
                                  onClick={() => form.setValue("takeProfit", "")}
                                >
                                  <X className="h-3 w-3" />
                                </button>
                              )}
                            </div>
                            <Input
                              {...field}
                              className="bg-transparent border-none p-0 text-white text-xs focus-visible:ring-0 focus-visible:ring-offset-0 h-5"
                              placeholder="Not set"
                              onChange={(e) => {
                                const value = e.target.value;
                                // Allow empty value or a valid decimal at the instrument's own precision
                                if (value === '' || decimalRegex.test(value)) {
                                  form.clearErrors("takeProfit");
                                  field.onChange(value);
                                }
                              }}
                            />
                          </div>
                          <div className="flex flex-col bg-[#131722] rounded-r h-full justify-center">
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="h-5 w-5 rounded-none rounded-tr text-gray-400 hover:text-white hover:bg-[#232838]"
                              onClick={() =>
                                handleAdjustValue("takeProfit", true)
                              }
                            >
                              <Plus className="h-3 w-3" />
                            </Button>
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="h-5 w-5 rounded-none rounded-br text-gray-400 hover:text-white hover:bg-[#232838]"
                              onClick={() =>
                                handleAdjustValue("takeProfit", false)
                              }
                            >
                              <Minus className="h-3 w-3" />
                            </Button>
                          </div>
                        </div>
                      </FormControl>
                      <FormMessage className="mt-1 text-[10px] leading-tight text-red-400" />
                    </FormItem>
                  )}
                />
              </div>

              <div className="flex items-center">
                <FormField
                  control={form.control}
                  name="stopLoss"
                  render={({ field }) => (
                    <FormItem className="flex-1">
                      <FormControl>
                        <div className="flex items-center">
                          <div className="bg-[#131722] p-2 rounded-l flex-1">
                            <div className="text-gray-400 text-xs">
                              Stop Loss
                              {field.value && (
                                <button
                                  type="button"
                                  aria-label="Clear stop loss"
                                  className="float-right text-gray-500 hover:text-white"
                                  onClick={() => form.setValue("stopLoss", "")}
                                >
                                  <X className="h-3 w-3" />
                                </button>
                              )}
                            </div>
                            <Input
                              {...field}
                              className="bg-transparent border-none p-0 text-white text-xs focus-visible:ring-0 focus-visible:ring-offset-0 h-5"
                              placeholder="Not set"
                              onChange={(e) => {
                                const value = e.target.value;
                                // Allow empty value or a valid decimal at the instrument's own precision
                                if (value === '' || decimalRegex.test(value)) {
                                  form.clearErrors("stopLoss");
                                  field.onChange(value);
                                }
                              }}
                            />
                          </div>
                          <div className="flex flex-col bg-[#131722] rounded-r h-full justify-center">
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="h-5 w-5 rounded-none rounded-tr text-gray-400 hover:text-white hover:bg-[#232838]"
                              onClick={() =>
                                handleAdjustValue("stopLoss", true)
                              }
                            >
                              <Plus className="h-3 w-3" />
                            </Button>
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="h-5 w-5 rounded-none rounded-br text-gray-400 hover:text-white hover:bg-[#232838]"
                              onClick={() =>
                                handleAdjustValue("stopLoss", false)
                              }
                            >
                              <Minus className="h-3 w-3" />
                            </Button>
                          </div>
                        </div>
                      </FormControl>
                      <FormMessage className="mt-1 text-[10px] leading-tight text-red-400" />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            <p className="text-[10px] text-gray-400">
              {tradeType && currentPrice
                ? `${tradeType.toUpperCase()}: TP must be ${tradeType.toLowerCase() === "buy" ? "above" : "below"} and SL ${tradeType.toLowerCase() === "buy" ? "below" : "above"} the current price. Clear a field to remove it.`
                : "Leave a field empty to remove that take profit or stop loss."}
            </p>

            <Button
              type="submit"
              disabled={isSubmitting}
              className="w-full h-8 text-xs bg-green-500 hover:bg-green-600 text-white"
            >
              {submitLabel}
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

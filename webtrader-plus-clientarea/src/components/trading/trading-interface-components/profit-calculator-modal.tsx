import { useState, useEffect } from "react";
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
import type { Asset } from "@/store/assetStore";
import useAssetStore from "@/store/assetStore";
import { quoteToUsd } from "@/lib/trade-pnl";

const formSchema = z.object({
  volume: z.string().refine(val => !isNaN(Number(val)) && Number(val) > 0, {
    message: "Volume must be a positive number",
  }),
  entryPrice: z.string().refine(val => !isNaN(Number(val)) && Number(val) > 0, {
    message: "Entry price must be a positive number",
  }),
  takeProfit: z.string().refine(val => !isNaN(Number(val)) && Number(val) >= 0, {
    message: "Take profit must be a non-negative number",
  }),
  stopLoss: z.string().refine(val => !isNaN(Number(val)) && Number(val) >= 0, {
    message: "Stop loss must be a non-negative number",
  }),
});

interface ProfitCalculatorModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  symbol?: string;
  asset?: Asset | null;
}

export function ProfitCalculatorModal({
                                        open,
                                        onOpenChange,
                                        symbol = "AUD/JPY",
                                        asset = null,
                                      }: ProfitCalculatorModalProps) {
  const [tradeType, setTradeType] = useState<"buy" | "sell">("buy");
  const [calculationResults, setCalculationResults] = useState({
    requiredMargin: 0,
    profitFromTp: 0,
    lossFromSl: 0,
    roe: 0,
    pip: 0,
  });

  // Track if the modal has been initialized
  const [initialized, setInitialized] = useState(false);

  // Initialize form with asset data when modal opens
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      volume: "1", // Default volume is 1
      entryPrice: asset?.rate || "89.78",
      takeProfit: "",
      stopLoss: "",
    },
  });

  // Reset form ONLY when the modal OPENS (not on every asset update)
  useEffect(() => {
    // Only run this effect when the modal goes from closed to open
    if (open && !initialized) {
      if (asset) {
        const currentPrice = parseFloat(asset.rate || "0");

        // Initialize form
        form.setValue("volume", "1");
        form.setValue("entryPrice", asset.rate);

        // Default take profit (0.1% higher for buy, lower for sell)
        const defaultTp = tradeType === "buy"
            ? (currentPrice * 1.001).toFixed(5)
            : (currentPrice * 0.999).toFixed(5);

        // Default stop loss (0.1% lower for buy, higher for sell)
        const defaultSl = tradeType === "buy"
            ? (currentPrice * 0.999).toFixed(5)
            : (currentPrice * 1.001).toFixed(5);

        form.setValue("takeProfit", defaultTp);
        form.setValue("stopLoss", defaultSl);
      }

      setInitialized(true);
    } else if (!open) {
      // Reset initialization flag when modal closes
      setInitialized(false);
    }
  }, [open]); // Only depend on the open state, not on asset or form

  const calculateProfit = (values: z.infer<typeof formSchema>) => {
    if (!asset) return;

    const volume = parseFloat(values.volume);
    const entryPrice = parseFloat(values.entryPrice);
    const takeProfit = parseFloat(values.takeProfit);
    const stopLoss = parseFloat(values.stopLoss);
    const leverage = asset.leverage || 20;
    const quoteRate = quoteToUsd(asset, useAssetStore.getState().assets);

    // Skip calculation if any values are invalid
    if (isNaN(volume) || isNaN(entryPrice) || quoteRate === null) {
      return;
    }

    // Calculate required margin based on entry price and leverage
    const requiredMargin = (volume * entryPrice) / leverage;

    // Calculate profit from take profit
    let profitFromTp = 0;
    if (!isNaN(takeProfit) && takeProfit > 0) {
      const priceDifferenceTP = tradeType === "buy"
          ? takeProfit - entryPrice
          : entryPrice - takeProfit;
      profitFromTp = priceDifferenceTP * volume * quoteRate;
    }

    // Calculate potential loss from stop loss
    let lossFromSl = 0;
    if (!isNaN(stopLoss) && stopLoss > 0) {
      const priceDifferenceSL = tradeType === "buy"
          ? entryPrice - stopLoss
          : stopLoss - entryPrice;
      lossFromSl = priceDifferenceSL * volume * quoteRate;
    }

    // Calculate ROE (Return on Equity) - Properly accounting for leverage and trade direction
    let roe = 0;
    if (!isNaN(takeProfit) && takeProfit > 0) {
      if (tradeType === "buy") {
        // For buy positions: ROE = ((TP - Entry) / Entry) * Leverage * 100
        roe = ((takeProfit - entryPrice) / entryPrice) * leverage * 100;
      } else {
        // For sell positions: ROE = ((Entry - TP) / Entry) * Leverage * 100
        roe = ((entryPrice - takeProfit) / entryPrice) * leverage * 100;
      }
    }

    // Calculate pip value (standard 0.0001 pip size for forex)
    // Adjust pip size based on asset type (forex = 0.0001, crypto might be different)
    const pipSize = 0.0001;
    const pip = volume * pipSize * quoteRate;

    setCalculationResults({
      requiredMargin,
      profitFromTp,
      lossFromSl,
      roe,
      pip,
    });
  };

  const onSubmit = (values: z.infer<typeof formSchema>) => {
    // Only calculate when the button is clicked
    calculateProfit(values);
  };

  const switchTradeType = (type: "buy" | "sell") => {
    setTradeType(type);

    if (asset) {
      // Keep the current entry price
      const entryPrice = parseFloat(form.getValues().entryPrice);

      if (!isNaN(entryPrice)) {
        // Switch TP/SL based on trade type
        const defaultTp = type === "buy"
            ? (entryPrice * 1.001).toFixed(5)
            : (entryPrice * 0.999).toFixed(5);

        const defaultSl = type === "buy"
            ? (entryPrice * 0.999).toFixed(5)
            : (entryPrice * 1.001).toFixed(5);

        form.setValue("takeProfit", defaultTp);
        form.setValue("stopLoss", defaultSl);
      }
    }
  };

  return (
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="sm:max-w-[550px] border-muted p-0 overflow-hidden text-sm">
          <DialogHeader className="p-2">
            <div className="flex justify-between items-center">
              <DialogTitle className="text-base">
                Calculations for {symbol}
              </DialogTitle>
              <DialogClose />
            </div>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-2 px-2 pb-2">
            <div className="space-y-2">
              <Form {...form}>
                <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    className="space-y-2"
                >
                  <div className="grid grid-cols-1 gap-2">
                    <div className="grid grid-cols-2 gap-2">
                      <Button
                          type="button"
                          size="sm"
                          className={`text-xs ${tradeType === "buy" ? "bg-green-500 hover:bg-green-600" : "bg-muted hover:bg-muted/80"}`}
                          onClick={() => switchTradeType("buy")}
                      >
                        BUY
                      </Button>
                      <Button
                          type="button"
                          size="sm"
                          className={`text-xs ${tradeType === "sell" ? "bg-red-500 hover:bg-red-600" : "bg-muted hover:bg-muted/80"}`}
                          onClick={() => switchTradeType("sell")}
                      >
                        SELL
                      </Button>
                    </div>

                    <div className="bg-secondary p-2 rounded flex justify-between items-center text-xs">
                    <span className="text-muted-foreground">
                      Price from profit
                    </span>
                      <span>{tradeType === "buy" ? "↑" : "↓"}</span>
                    </div>

                    <div className="bg-slate-700 p-2 rounded flex justify-between items-center">
                      <span className="text-gray-400 text-xs">Volume</span>
                      <FormField
                          control={form.control}
                          name="volume"
                          render={({ field }) => (
                              <FormItem className="m-0">
                                <FormControl>
                                  <Input
                                      {...field}
                                      className="border-none text-right w-20 text-white focus-visible:ring-0 focus-visible:ring-offset-0 text-xs h-6 px-1"
                                  />
                                </FormControl>
                              </FormItem>
                          )}
                      />
                    </div>

                    <div className="bg-slate-700 p-2 rounded flex justify-between items-center">
                      <span className="text-gray-400 text-xs">Entry Price</span>
                      <FormField
                          control={form.control}
                          name="entryPrice"
                          render={({ field }) => (
                              <FormItem className="m-0">
                                <FormControl>
                                  <Input
                                      {...field}
                                      className="bg-transparent border-none text-right w-20 focus-visible:ring-0 focus-visible:ring-offset-0 text-xs h-6 px-1"
                                  />
                                </FormControl>
                              </FormItem>
                          )}
                      />
                    </div>

                    <div className="bg-slate-700 p-2 rounded flex justify-between items-center">
                      <span className="text-gray-400 text-xs">Take Profit</span>
                      <FormField
                          control={form.control}
                          name="takeProfit"
                          render={({ field }) => (
                              <FormItem className="m-0">
                                <FormControl>
                                  <Input
                                      {...field}
                                      className="bg-transparent border-none text-right w-20 focus-visible:ring-0 focus-visible:ring-offset-0 text-xs h-6 px-1"
                                  />
                                </FormControl>
                              </FormItem>
                          )}
                      />
                    </div>

                    <div className="bg-slate-700 p-2 rounded flex justify-between items-center">
                      <span className="text-gray-400 text-xs">Stop Loss</span>
                      <FormField
                          control={form.control}
                          name="stopLoss"
                          render={({ field }) => (
                              <FormItem className="m-0">
                                <FormControl>
                                  <Input
                                      {...field}
                                      className="bg-transparent border-none text-right w-20 text-white focus-visible:ring-0 focus-visible:ring-offset-0 text-xs h-6 px-1"
                                  />
                                </FormControl>
                              </FormItem>
                          )}
                      />
                    </div>

                    <div className="text-gray-400 text-xs">
                      Max Position: {asset ? `${Math.round((asset?.leverage || 20) * 1000)} ${symbol}` : '19000 ' + symbol}
                    </div>

                    <Button type="submit" size="sm" className="text-xs h-7">
                      Calculate
                    </Button>
                  </div>
                </form>
              </Form>
            </div>

            <div className="space-y-2 bg-slate-700 p-4 flex flex-col gap-4">
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">Leverage</span>
                <span className="text-white text-xs">1:{asset?.leverage || 20}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">Required Margin</span>
                <span className="text-white text-xs">
                {calculationResults.requiredMargin.toFixed(2)} <span className="text-gray-400 text-xs">USD</span>
              </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">Profit from TP</span>
                <span className="text-white text-xs">
                {calculationResults.profitFromTp.toFixed(2)} <span className="text-gray-400 text-xs">USD</span>
              </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">Loss from SL</span>
                <span className="text-white text-xs">
                {calculationResults.lossFromSl.toFixed(2)} <span className="text-gray-400 text-xs">USD</span>
              </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">ROE</span>
                <span className="text-white text-xs">
                {calculationResults.roe > 0 ? "+" : ""}{calculationResults.roe.toFixed(2)} <span className="text-gray-400 text-xs">%</span>
              </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">PIP</span>
                <span className="text-white text-xs">
                {calculationResults.pip.toFixed(2)} <span className="text-gray-400 text-xs">USD</span>
              </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400 text-xs">Price from profit</span>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>
  );
}

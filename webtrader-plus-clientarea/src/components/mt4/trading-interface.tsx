import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Star, Plus, Minus, TrendingUp, Info, Loader2 } from "lucide-react";
import { Slider } from "../ui/slider";
import { toast } from "sonner";
import useAssetStore from "@/store/assetStore";
import useUserStore from "@/store/userStore";
import useTradeStore from "@/store/tradeStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { AxiosError } from "axios";
// import axiosInstance from "@/lib/axios";

const AUTO_TRADER_LOCK_MESSAGE =
  "Auto trader is currently enabled on your account please contact support....";

export default function TradingInterface() {
  // Get data from stores
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const user = useUserStore((state) => state.user);
  const enableAutotrader = useSiteSettingsStore(
    (state) => state.settings?.enable_autotrader === true
  );
  const { fetchOpenTrades } = useTradeStore();
  const isAutoTraderLocked = enableAutotrader && Boolean(user?.autotrader);

  // Local state
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [amount, setAmount] = useState(1.0);
  const [percentage, setPercentage] = useState([25]);
  const [tpEnabled, setTpEnabled] = useState(false);
  const [spEnabled, setSpEnabled] = useState(true);
  const [tpAmount, setTpAmount] = useState(10.054);
  const [spAmount, setSpAmount] = useState(10.054);

  const tradingInfo = {
    contractSize: activeAsset?.contract_size || 100000,
    leverage: user?.account_type?.leverage || 20,
    buyPrice: activeAsset?.buy_price || 0,
    sellPrice: activeAsset?.sell_price || 0,
  };

  const adjustAmount = (value: number, delta: number) => {
    return Math.max(0, value + delta);
  };

  // Handle trade execution
  const handleTrade = async (type: "buy" | "sell") => {
    if (isAutoTraderLocked) {
      toast.error(AUTO_TRADER_LOCK_MESSAGE);
      return;
    }

    if (!activeAsset) {
      toast.error("No asset selected for trading");
      return;
    }

    setIsSubmitting(true);

    // const calculatedAmount = amount * tradingInfo.contractSize;
    const price = type === "buy" ? tradingInfo.buyPrice : tradingInfo.sellPrice;

    try {
      // const tradeData = {
      //   type,
      //   leverage: tradingInfo.leverage,
      //   amount: calculatedAmount,
      //   qty: calculatedAmount,
      //   volume: calculatedAmount,
      //   margin: (calculatedAmount * price) / tradingInfo.leverage,
      //   opening_price: price,
      //   take_profit: tpEnabled ? tpAmount : 0,
      //   stop_loss: spEnabled ? spAmount : 0,
      //   id: activeAsset.id,
      //   asset_symbol: activeAsset.symbol_display || activeAsset.symbol,
      //   asset_name: activeAsset.name,
      // };

      // const response = await axiosInstance.post("/trades/store", tradeData);

      toast.success(`${type.toUpperCase()} order placed`, {
        description: `${
          type === "buy" ? "Bought" : "Sold"
        } ${amount} lots at ${price.toFixed(5)}`,
      });

      await fetchOpenTrades();
    } catch (error) {
      console.error("Error executing trade:", error);
      const axiosError = error as AxiosError<{ message: string }>;
      toast.error("Failed to execute trade", {
        description: axiosError.response?.data?.message || "Please try again",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className=" w-full bg-[#1C2030] text-white">
      <Card className="bg-[#1C2030] border-slate-700 text-white">
        <div className="p-4 space-y-4">
          {/* Header */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center text-xs font-bold">
                {activeAsset?.base || "₿"}
              </div>
              <div>
                <div className="font-semibold">
                  {activeAsset?.symbol_display || "BTC/USD"}
                </div>
                <div className="text-xs text-slate-400">
                  {activeAsset?.type || "Crypto"}
                </div>
              </div>
            </div>
            <Star className="w-5 h-5 text-slate-400" />
          </div>

          {/* Units Section */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label className="text-slate-400">Units</Label>
              <Badge
                variant="secondary"
                className="bg-orange-600 text-white hover:bg-orange-700"
              >
                Amount
              </Badge>
            </div>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="icon"
                className="h-8 w-8 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                onClick={() => setAmount(adjustAmount(amount, -0.001))}
              >
                <Minus className="h-3 w-3" />
              </Button>
              <Input
                type="number"
                value={amount.toFixed(3)}
                onChange={(e) =>
                  setAmount(Number.parseFloat(e.target.value) || 0)
                }
                className="text-center bg-slate-700 border-slate-600 text-white"
                step="0.001"
              />
              <Button
                variant="outline"
                size="icon"
                className="h-8 w-8 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                onClick={() => setAmount(adjustAmount(amount, 0.001))}
              >
                <Plus className="h-3 w-3" />
              </Button>
            </div>
          </div>

          {/* Percentage Slider */}
          <div className="space-y-3">
            <div className="flex justify-between text-sm text-slate-400">
              <span>{percentage[0]}%</span>
              <span>100%</span>
            </div>
            <Slider
              value={percentage}
              onValueChange={setPercentage}
              max={100}
              step={1}
              className="w-full"
            />
          </div>

          {/* Price */}
          <div className="flex justify-between items-center">
            <Label className="text-slate-400">Price</Label>
            <div className="text-right">
              <div className="font-semibold">
                {activeAsset?.rate || "0.00"}{" "}
                <span className="text-slate-400">USD</span>
              </div>
            </div>
          </div>

          {/* Leverage */}
          <div className="flex justify-between items-center">
            <Label className="text-slate-400">Leverage</Label>
            <div className="font-semibold">1:{tradingInfo.leverage}</div>
          </div>

          {/* Auto Trader */}
          <div className="flex justify-between items-center">
            <Label className="text-slate-400">Auto Trader</Label>
            <Badge
              variant="outline"
              className="border-slate-600 text-slate-400"
            >
              In-active
            </Badge>
          </div>

          {/* TP Section */}
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <Label className="text-slate-400 flex items-center gap-1">
                TP <Info className="w-3 h-3" />
              </Label>
              <Switch
                checked={tpEnabled}
                onCheckedChange={setTpEnabled}
                className="data-[state=checked]:bg-green-500"
              />
              <div className="flex items-center gap-2 ml-auto">
                <Button
                  variant="outline"
                  size="icon"
                  className="h-6 w-6 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                  onClick={() => setTpAmount(adjustAmount(tpAmount, -0.001))}
                  disabled={!tpEnabled}
                >
                  <Minus className="h-2 w-2" />
                </Button>
                <span className="text-sm w-12 text-center">
                  {tpAmount.toFixed(3)}
                </span>
                <Button
                  variant="outline"
                  size="icon"
                  className="h-6 w-6 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                  onClick={() => setTpAmount(adjustAmount(tpAmount, 0.001))}
                  disabled={!tpEnabled}
                >
                  <Plus className="h-2 w-2" />
                </Button>
              </div>
            </div>
          </div>

          {/* SP Section */}
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <Label className="text-slate-400 flex items-center gap-1">
                SP <Info className="w-3 h-3" />
              </Label>
              <Switch
                checked={spEnabled}
                onCheckedChange={setSpEnabled}
                className="data-[state=checked]:bg-green-500"
              />
              <div className="flex items-center gap-2 ml-auto">
                <Button
                  variant="outline"
                  size="icon"
                  className="h-6 w-6 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                  onClick={() => setSpAmount(adjustAmount(spAmount, -0.001))}
                  disabled={!spEnabled}
                >
                  <Minus className="h-2 w-2" />
                </Button>
                <span className="text-sm w-12 text-center">
                  {spAmount.toFixed(3)}
                </span>
                <Button
                  variant="outline"
                  size="icon"
                  className="h-6 w-6 bg-slate-700 border-slate-600 text-white hover:bg-slate-600"
                  onClick={() => setSpAmount(adjustAmount(spAmount, 0.001))}
                  disabled={!spEnabled}
                >
                  <Plus className="h-2 w-2" />
                </Button>
              </div>
            </div>
          </div>

          {/* Buy/Sell Buttons */}
          <div className="grid grid-cols-2 gap-2">
            <Button
              className={`w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 ${
                isAutoTraderLocked
                  ? "opacity-60 cursor-not-allowed hover:bg-green-500"
                  : ""
              }`}
              onClick={() => handleTrade("buy")}
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
              ) : (
                <>
                  <TrendingUp className="w-4 h-4 mr-2" />
                  BUY {tradingInfo.buyPrice.toFixed(5)}
                </>
              )}
            </Button>
            <Button
              className={`w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 ${
                isAutoTraderLocked
                  ? "opacity-60 cursor-not-allowed hover:bg-red-500"
                  : ""
              }`}
              onClick={() => handleTrade("sell")}
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
              ) : (
                <>
                  <TrendingUp className="w-4 h-4 mr-2 rotate-180" />
                  SELL {tradingInfo.sellPrice.toFixed(5)}
                </>
              )}
            </Button>
          </div>

          {/* Close Button */}
          {/* <Button
            variant="ghost"
            className="w-full text-slate-400 hover:text-white hover:bg-slate-700"
            onClick={() => {
              if (activeAsset) {
                toast.info("Order modal closed");
              }
            }}
          >
            <X className="w-4 h-4 mr-2" />
            CLOSE
          </Button> */}
        </div>
      </Card>
    </div>
  );
}

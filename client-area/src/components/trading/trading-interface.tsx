import React, { useState, useEffect, useCallback } from "react";
import { v4 as uuidv4 } from "uuid";
import { toast } from "sonner";
import { Form, FormControl, FormField, FormItem } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ChevronDown, ChevronUp, BarChart2, Loader2, ArrowUp, ArrowDown } from "lucide-react";
import { Separator } from "@/components/ui/separator";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import useAssetStore from "@/store/assetStore";
import useUserStore from "@/store/userStore";
import useTradeStore from "@/store/tradeStore";
import axiosInstance from "@/lib/axios";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { ProfitCalculatorModal } from "./trading-interface-components/profit-calculator-modal";
import { TakeProfitStopLossModal } from "./trading-interface-components/take-profit-stop-loss-modal";
import { PendingOrderModal } from "./trading-interface-components/pending-order-modal";
import { useCurrency } from "@/hooks/useCurrency.ts";
import {
  formatSpreadQuote,
  formatTradePrice,
  spreadQuote,
} from "@/lib/price-format";
import { cn } from "@/lib/utils.ts";
import useDataStore from "@/store/dataStore.ts";
import { useSearchParams } from "react-router-dom";
import { currentTranslateLang, normalizeLang } from "@/lib/funnelLanguage";

// import TechnicalAnalysisWidget from "@/components/trading/partials/TechnicalAnalysisWidget";
import { useOrientation } from "@/hooks/use-orientation";

// AudioContext type for sound effects
type AudioContextType = typeof AudioContext;

const formSchema = z.object({
  type: z.enum(["buy", "sell"]),
  id: z.string(),
  amount: z.number(),
  volume: z.number(),
  takeProfit: z.number().optional(),
  stopLoss: z.number().optional(),
});

const AUTO_TRADER_LOCK_MESSAGE =
  "Auto trader is currently enabled on your account please contact support....";

export function TradingInterface() {
  const isLandscape = useOrientation();
  const [searchParams] = useSearchParams();
  const isRussian =
    (normalizeLang(searchParams.get("lang")) ?? currentTranslateLang()) === "ru";
  const copy = isRussian
    ? {
        volume: "Объём",
        lots: "Лоты",
        units: "Единицы",
        currency: "Валюта",
        contract: "Размер контракта:",
        position: "Позиция:",
        margin: "Маржа:",
        freeMargin: "Свободная маржа:",
        spread: "Спред:",
        leverage: "Кредитное плечо:",
        profitCalculator: "Калькулятор прибыли",
        pending: "Отложенный ордер",
        market: "Рыночный",
        notSet: "Не задано",
        buy: "КУПИТЬ",
        sell: "ПРОДАТЬ",
      }
    : {
        volume: "Volume",
        lots: "lots",
        units: "units",
        currency: "currency",
        contract: "Contract size:",
        position: "Position:",
        margin: "Margin:",
        freeMargin: "Free Margin:",
        spread: "Spread:",
        leverage: "Leverage:",
        profitCalculator: "Profit Calculator",
        pending: "Pending",
        market: "Market",
        notSet: "Not set",
        buy: "BUY",
        sell: "SELL",
      };
  // Get asset and user data
  const activeAsset = useAssetStore((state) => state.activeAsset);
  const getActiveLeverage = useAssetStore((state) => state.getActiveLeverage);
  const user = useUserStore((state) => state.user);
  const leverage = useDataStore((state) => state.leverage);
  const enableAutotrader = useSiteSettingsStore(
    (state) => state.settings?.enable_autotrader === true
  );
  const { fetchOpenTrades, accountSummary } = useTradeStore();
  const isAutoTraderLocked = enableAutotrader && Boolean(user?.autotrader);


  // State
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeTab, setActiveTab] = useState("lots");
  const [baseVolumeLots, setBaseVolumeLots] = useState(0.01);

  const [displayVolume, setDisplayVolume] = useState<number | string>(0.01);

  const [audioContext, setAudioContext] = useState<AudioContext | null>(null);

  // Modal state
  const [isProfitCalculatorOpen, setIsProfitCalculatorOpen] = useState(false);
  const [isTpSlModalOpen, setIsTpSlModalOpen] = useState(false);
  const [isPendingModalOpen, setIsPendingModalOpen] = useState(false);

  // Trading information state with initial values
  const [tradingInfo, setTradingInfo] = useState({
    contractSize: 100000,
    position: 1000,
    margin: 0,
    freeMargin: user?.balance || 0,
    spread: 0.00006,
    leverage: 20,
    buyPrice: 0.51954,
    sellPrice: 0.51948,
  });

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      type: "buy",
      id: uuidv4(),
      amount: 1,
      volume: 0.01,
      takeProfit: undefined,
      stopLoss: undefined,
    },
  });

  const { formatCurrencyValue } = useCurrency();

  // Initialize audio context on first user interaction
  useEffect(() => {
    const initializeAudio = () => {
      if (audioContext) return;

      // Get AudioContext with cross-browser support
      const getAudioContextConstructor = (): AudioContextType | null => {
        if (window.AudioContext) {
          return window.AudioContext;
        } else if ("webkitAudioContext" in window) {
          return (window as { webkitAudioContext: AudioContextType })
            .webkitAudioContext;
        }
        return null;
      };

      const AudioContextClass = getAudioContextConstructor();
      if (AudioContextClass) {
        setAudioContext(new AudioContextClass());
      }
    };

    // Add event listeners for user interaction
    const events = ["mousedown", "keydown", "touchstart"];
    events.forEach((event) =>
      document.addEventListener(event, initializeAudio, { once: true })
    );

    return () => {
      events.forEach((event) =>
        document.removeEventListener(event, initializeAudio)
      );
    };
  }, [audioContext]);

  // Sound effect functions
  const playSuccessSound = useCallback(() => {
    if (!audioContext) return;

    try {
      // Create oscillator and gain nodes
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      // Configure oscillator for success sound
      oscillator.type = "sine";
      oscillator.frequency.setValueAtTime(880, audioContext.currentTime); // A5 note
      oscillator.frequency.exponentialRampToValueAtTime(
        440,
        audioContext.currentTime + 0.15
      ); // Down to A4

      // Configure gain (volume)
      gainNode.gain.setValueAtTime(0, audioContext.currentTime);
      gainNode.gain.linearRampToValueAtTime(
        0.3,
        audioContext.currentTime + 0.01
      );
      gainNode.gain.exponentialRampToValueAtTime(
        0.001,
        audioContext.currentTime + 0.5
      );

      // Connect the nodes
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      // Play the sound
      oscillator.start();
      oscillator.stop(audioContext.currentTime + 0.5);
    } catch (err) {
      console.error("Error playing success sound:", err);
    }
  }, [audioContext]);

  const playErrorSound = useCallback(() => {
    if (!audioContext) return;

    try {
      // Create oscillator and gain nodes
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      // Configure oscillator for error sound
      oscillator.type = "sawtooth";
      oscillator.frequency.setValueAtTime(220, audioContext.currentTime); // A3 note

      // Configure gain with quick pulses for error sound
      gainNode.gain.setValueAtTime(0, audioContext.currentTime);
      gainNode.gain.linearRampToValueAtTime(
        0.3,
        audioContext.currentTime + 0.01
      );
      gainNode.gain.linearRampToValueAtTime(
        0.05,
        audioContext.currentTime + 0.1
      );
      gainNode.gain.linearRampToValueAtTime(
        0.3,
        audioContext.currentTime + 0.2
      );
      gainNode.gain.exponentialRampToValueAtTime(
        0.001,
        audioContext.currentTime + 0.3
      );

      // Connect the nodes
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      // Play the sound
      oscillator.start();
      oscillator.stop(audioContext.currentTime + 0.3);
    } catch (err) {
      console.error("Error playing error sound:", err);
    }
  }, [audioContext]);

  useEffect(() => {
    if (activeAsset) {
      const assetRate = Number.parseFloat(activeAsset.rate || "0");
      // Per-side PERCENTAGES as configured -- "0.01" is 0.01%, so it has to
      // be divided by 100 before it touches a price. Applying it raw marked
      // BUY up by 1% (100x the intended cost), and the SELL side below was
      // adding it to the market price instead of subtracting, so a SELL
      // filled ABOVE the market it was selling into.
      const buySpread = Number.parseFloat(activeAsset.buy_spread || "0");
      const sellSpread = Number.parseFloat(activeAsset.sell_spread || "0");


      // const leverage = user?.account_type?.leverage || 20;

      const leverage = getActiveLeverage();


      // ask = price * (1 + buy%/100), bid = price * (1 - sell%/100).
      // BUY is always above the market, SELL always below.
      const buyPrice = activeAsset.buy_price || assetRate * (1 + buySpread / 100);
      const sellPrice = activeAsset.sell_price || assetRate * (1 - sellSpread / 100);

      // Calculate margin based on volume and leverage. Use the instrument's own
      // contract_size here, not tradingInfo.contractSize -- that's still the
      // PREVIOUS asset's value at this point in the effect (it's only written
      // below), so switching to a different-contract-size instrument would
      // otherwise compute one tick of margin against the wrong instrument.
      // Floor at 0: margin is collateral, it can never legitimately be negative.
      // Use margin_rate, NOT assetRate -- assetRate is only USD-denominated
      // when the pair's quote currency is USD (EURUSD, GBPUSD). For a
      // USD-base pair (USDJPY) or a true cross (GBPJPY, EURGBP), assetRate
      // is in the wrong currency entirely -- ~150x too high on JPY pairs.
      // margin_rate is the server-resolved USD conversion for this instrument.
      const marginRate = activeAsset.margin_rate ?? assetRate;
      // Position is the order's notional quantity in contract units. The
      // instrument-level `position` value is static metadata and must not be
      // shown here: changing lots/units/currency would otherwise leave the
      // preview frozen even though margin was recalculated correctly.
      const positionUnits = Math.max(
        0,
        baseVolumeLots * activeAsset.contract_size
      );
      const calculatedMargin = Math.max(
        0,
        (positionUnits * marginRate) / leverage
      );
      const userBalance = user?.balance || 0;

      setTradingInfo({
        contractSize: activeAsset.contract_size,
        position: positionUnits,
        margin: calculatedMargin,
        freeMargin: accountSummary?.freeMargin || userBalance,
        spread: Math.abs(buyPrice - sellPrice),
        leverage: leverage,
        buyPrice: buyPrice,
        sellPrice: sellPrice,
      });
    }
  }, [activeAsset, baseVolumeLots, user?.balance, leverage]);

  // The order box's spread line, in the instrument's own units plus what it
  // costs on this order size -- "3.8 pips (3.82 AUD)". It used to print the
  // raw config percentage ("Spread: 0.02000"), which is unitless and reads as
  // pips to anyone who has used another platform, when 0.02% on GBP/AUD is
  // actually 3.8 pips.
  const spreadDisplay = React.useMemo(() => {
    if (!activeAsset) return null;

    return spreadQuote({
      price: Number.parseFloat(activeAsset.rate || "0"),
      buySpreadPercent: Number.parseFloat(activeAsset.buy_spread || "0"),
      sellSpreadPercent: Number.parseFloat(activeAsset.sell_spread || "0"),
      category: activeAsset.type,
      symbol: activeAsset.sy ?? "",
      contractSize: activeAsset.contract_size,
      volumeLots: baseVolumeLots,
      pipSizeOverride: activeAsset.pip_size,
      // The live two-sided quote, which already carries this customer's
      // spread_multiplier; the percentages are only the fallback.
      quotedSpread: activeAsset.buy_price - activeAsset.sell_price,
    });
  }, [activeAsset, baseVolumeLots]);

  const spreadText = spreadDisplay ? formatSpreadQuote(spreadDisplay) : "-";

  // Take Profit / Stop Loss are price levels, meaningful only for the
  // instrument they were set against -- a BTC TP/SL carried onto an FX pair
  // is nowhere near a valid price and could get an order rejected, or worse,
  // accepted with wildly wrong risk levels the client never intended.
  // Keyed on activeAsset?.id specifically (not the whole activeAsset object,
  // which gets a new reference on every live price tick) so this only fires
  // on an actual instrument switch, not every time the price updates.
  useEffect(() => {
    form.setValue("takeProfit", undefined);
    form.setValue("stopLoss", undefined);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeAsset?.id]);

  const convertVolume = (
    value: number,
    fromFormat: string,
    toFormat: string
  ): number => {
    // First convert to lots (base unit)
    let inLots = value;
    if (fromFormat === "units") {
      inLots = value / tradingInfo.contractSize;
    } else if (fromFormat === "currency") {
      // Convert from currency to lots
      const assetRate = activeAsset
        ? Number.parseFloat(activeAsset.rate || "0")
        : 1;
      inLots = value / (assetRate * tradingInfo.contractSize);
    }

    // Then convert from lots to target format
    if (toFormat === "lots") {
      return inLots;
    } else if (toFormat === "units") {
      return inLots * tradingInfo.contractSize;
    } else if (toFormat === "currency") {
      // Convert from lots to currency
      const assetRate = activeAsset
        ? Number.parseFloat(activeAsset.rate || "0")
        : 1;
      return inLots * assetRate * tradingInfo.contractSize;
    }

    return inLots; // Default fallback
  };

  const handleVolumeChange = (increment: boolean) => {
    // Calculate step size based on active tab
    let step = 0.01;
    if (activeTab === "units") {
      step = 1000;
    } else if (activeTab === "currency") {
      step = 10;
    }

    // Ensure displayVolume is treated as a number for calculations
    const currentValue =
      typeof displayVolume === "string"
        ? parseFloat(displayVolume) || 0
        : displayVolume;

    // Calculate new volume with proper numeric addition
    const rawNewDisplayVolume = increment
      ? currentValue + step
      : Math.max(step, currentValue - step);
    // Round off floating-point drift (e.g. 0.1 + 0.2) so the lots field
    // never grows extra decimal places from repeated stepper clicks.
    const newDisplayVolume =
      activeTab === "lots"
        ? Number.parseFloat(rawNewDisplayVolume.toFixed(2))
        : rawNewDisplayVolume;

    // Update state with the numeric value
    setDisplayVolume(newDisplayVolume);

    // Always update the base volume in lots
    const newBaseLots = convertVolume(newDisplayVolume, activeTab, "lots");
    setBaseVolumeLots(newBaseLots);

    // Update form value
    form.setValue("volume", newDisplayVolume);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const inputValue = e.target.value;
    const volumePattern =
      activeTab === "lots" ? /^[0-9]*\.?[0-9]{0,2}$/ : /^[0-9]*\.?[0-9]*$/;

    if (inputValue === "" || volumePattern.test(inputValue)) {
      // Update display value
      setDisplayVolume(inputValue);

      if (
        inputValue !== "" &&
        inputValue !== "." &&
        !inputValue.endsWith(".")
      ) {
        const numValue = Number.parseFloat(inputValue);
        if (!isNaN(numValue)) {
          // Convert to base lots
          const newBaseLots = convertVolume(numValue, activeTab, "lots");
          setBaseVolumeLots(newBaseLots);
          form.setValue("volume", numValue);
        }
      } else {
        form.setValue("volume", 0);
      }
    }
  };

  // Handle tab changes
  const handleTabChange = (value: string) => {
    setActiveTab(value);

    // Get current numeric value (handle string case)
    const currentNumericValue =
      typeof displayVolume === "string"
        ? parseFloat(displayVolume) || 0
        : displayVolume;

    // Only convert when we have a valid numeric value
    if (currentNumericValue > 0) {
      // First convert current display value to lots
      const currentLots = convertVolume(currentNumericValue, activeTab, "lots");

      // Then convert from lots to the new format
      const newDisplayValue = convertVolume(currentLots, "lots", value);

      // Update the display with the new value in the selected format
      setDisplayVolume(newDisplayValue);
      setBaseVolumeLots(currentLots);
      form.setValue("volume", newDisplayValue);
    } else {
      // Use baseVolumeLots as fallback for partial inputs
      const newDisplayValue = convertVolume(baseVolumeLots, "lots", value);
      setDisplayVolume(newDisplayValue);
      form.setValue("volume", newDisplayValue);
    }
  };

  // Form submission - create trade
  const onSubmit = async (values: z.infer<typeof formSchema>) => {
    if (isAutoTraderLocked) {
      toast.error(AUTO_TRADER_LOCK_MESSAGE);
      return;
    }

    if (!activeAsset) {
      toast.error("No asset selected for trading");
      return;
    }

    setIsSubmitting(true);

    const calculatedAmount = baseVolumeLots * tradingInfo.contractSize;
    const calculatedMargin = tradingInfo.margin;

    const price =
      values.type === "buy" ? tradingInfo.buyPrice : tradingInfo.sellPrice;

    const tradeData = {
      type: values.type,
      leverage: tradingInfo?.leverage,
      amount: calculatedAmount,
      qty: calculatedAmount,
      volume: calculatedAmount,
      margin: calculatedMargin,
      opening_price: price,
      take_profit: values.takeProfit || 0,
      stop_loss: values.stopLoss || 0,
      id: activeAsset.id,
      asset_symbol: activeAsset.symbol_display || activeAsset.symbol,
      asset_name: activeAsset.name,
    };

    try {
      const response = await axiosInstance.post("/trades/store", tradeData);

      console.log("Trade executed successfully:", response.data);

      playSuccessSound();

      // Confirm the price the order ACTUALLY filled at, as recorded on the
      // trade, not the figure the order box happened to be showing when the
      // button was pressed. The server refreshes the feed and prices the fill
      // itself; if the market moved in between, this is what Active Orders,
      // Trading History, the Client Area and the back office will all show,
      // and the confirmation was previously the one surface disagreeing with
      // them -- the reported web-trader-vs-back-office mismatch.
      const executed = response.data?.data;
      const filledPrice = Number(executed?.opening_price);
      const displayPrice = Number.isFinite(filledPrice) ? filledPrice : price;
      const filledLots = Number.isFinite(Number(executed?.volume_lots))
        ? Number(executed.volume_lots)
        : baseVolumeLots;

      toast.success(`${values.type === "buy" ? "Buy" : "Sell"} order placed`, {
        description: `${
          values.type === "buy" ? "Bought" : "Sold"
        } ${filledLots} lots (${calculatedAmount.toLocaleString()} units) at ${formatTradePrice(
          displayPrice,
          activeAsset.type,
          activeAsset.sy ?? ""
        )}`,
        position: "top-right",
        duration: 3000,
      });

      await fetchOpenTrades();
    } catch (error: unknown) {
      console.error("Error executing trade:", error);
      playErrorSound();

      interface AxiosError {
        response?: {
          data?: {
            message?: string;
          };
        };
      }
      const axiosError = error as AxiosError;

      toast.error("Failed to execute trade", {
        description:
          axiosError.response?.data?.message ||
          "There was an error processing your trade. Please try again.",
        position: "top-right",
        duration: 5000,
      });
    } finally {
      setIsSubmitting(false);
      form.setValue("id", uuidv4());
    }
  };

  const handleTradeButtonClick =
    (type: "buy" | "sell") => (event: React.MouseEvent<HTMLButtonElement>) => {
      if (isAutoTraderLocked) {
        event.preventDefault();
        toast.error(AUTO_TRADER_LOCK_MESSAGE);
        return;
      }

      form.setValue("type", type);
      form.setValue("id", uuidv4());
      form.setValue("amount", baseVolumeLots * tradingInfo.contractSize);
    };

  // Get TP/SL display text
  const getTpSlText = () => {
    const tp = form.getValues().takeProfit;
    const sl = form.getValues().stopLoss;

    // Price levels, so instrument precision -- 2dp collapsed a forex TP/SL
    // to the nearest big figure (1.91022 -> "1.91"), which is the entire
    // range the level was being set within.
    const level = (value: number) =>
      formatTradePrice(value, activeAsset?.type, activeAsset?.sy ?? "");

    if (tp && sl) {
      return `TP: ${level(tp)} SL: ${level(sl)}`;
    } else if (tp) {
      return `TP: ${level(tp)}`;
    } else if (sl) {
      return `SL: ${level(sl)}`;
    } else {
      return copy.notSet;
    }
  };

  return (
    <div
      className={cn(
        "w-full bg-background rounded-lg text-foreground",
        isRussian && "notranslate",
      )}
      translate={isRussian ? "no" : undefined}
      style={{ maxHeight: "500px", overflowY: "auto", overflowX: "hidden" }}
    >
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="md:space-y-2">
          {/* Mobile layout - adapts to portrait/landscape */}
          <div className="lg:hidden w-full">
            {!isLandscape ? (
              // Portrait layout - existing 3-column grid
              <div className="grid grid-cols-3 gap-1 w-full">
                {/* Section 2: Trading Info */}
                <div className="col-span-1 p-2">
                  <div className="grid gap-y-[2px] text-[10px]">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">{copy.contract}</span>
                      <span className="text-primary">
                        {tradingInfo.contractSize.toLocaleString()}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">{copy.position}</span>
                      <span className="text-primary">
                        {tradingInfo.position.toLocaleString()}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">{copy.margin}</span>
                      <span
                        className={cn(
                          tradingInfo.margin > accountSummary.freeMargin
                            ? "text-red-500"
                            : "text-primary"
                        )}
                      >
                        {formatCurrencyValue(
                          parseFloat(tradingInfo.margin.toFixed(2))
                        )}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">
                        {copy.freeMargin}
                      </span>
                      <span
                        className={
                          accountSummary.freeMargin < 0
                            ? "text-red-500"
                            : "text-primary"
                        }
                      >
                        {formatCurrencyValue(
                          parseFloat(accountSummary.freeMargin.toFixed(2))
                        )}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">{copy.leverage}</span>
                      <span className="text-primary">
                        1:{tradingInfo.leverage}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Section 3: Profit Calculator and Buttons */}
                <div className="col-span-1 p-2 rounded-r flex flex-col justify-between">
                  <div className="space-y-1">
                    <Button
                      type="button"
                      className="flex items-center justify-start py-1 w-full h-auto bg-secondary hover:bg-secondary/50"
                      onClick={() => setIsProfitCalculatorOpen(true)}
                    >
                      <BarChart2 className="h-3 w-3 mr-1 " />
                      <div className="flex flex-col items-start justify-start">
                        <span className="text-[10px] font-medium text-foreground">
                          {copy.profitCalculator}
                        </span>
                      </div>
                    </Button>

                    <div>
                      <Button
                        type="button"
                        className="flex flex-col items-start justify-center w-full h-auto bg-secondary hover:bg-secondary/50"
                        onClick={() => setIsTpSlModalOpen(true)}
                      >
                        <div className="text-[8px] text-muted-foreground">
                          TP & SL
                        </div>
                        <div className="text-[10px] text-foreground">
                          {getTpSlText()}
                        </div>
                      </Button>
                    </div>

                    <div>
                      <Button
                        type="button"
                        className="flex flex-col items-start justify-center w-full h-auto bg-secondary hover:bg-secondary/50"
                        onClick={() => setIsPendingModalOpen(true)}
                      >
                        <div className="text-[8px] text-muted-foreground">
                          {copy.pending}
                        </div>
                        <div className="text-[10px] text-foreground">
                          {copy.market}
                        </div>
                      </Button>
                    </div>
                  </div>

                  {/* <div className="grid grid-cols-2 gap-1 mt-1">
                <Button
                  type="submit"
                  className="bg-green-500 hover:bg-green-600 text-white h-8 px-1"
                  onClick={() => {
                    form.setValue("type", "buy");
                    form.setValue("id", uuidv4());
                    form.setValue(
                      "amount",
                      baseVolumeLots * tradingInfo.contractSize
                    );
                  }}
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : (
                    <div className="flex flex-col items-center">
                      <span className="text-[10px] font-bold inline-flex items-center gap-1"><ArrowUp className="h-2.5 w-2.5" />BUY</span>
                      <span className="text-[8px]">
                        {formatTradePrice(tradingInfo.buyPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                      </span>
                    </div>
                  )}
                </Button>
                <Button
                  type="submit"
                  className="bg-red-500 hover:bg-red-600 text-white h-8 px-1"
                  onClick={() => {
                    form.setValue("type", "sell");
                    form.setValue("id", uuidv4());
                    form.setValue(
                      "amount",
                      baseVolumeLots * tradingInfo.contractSize
                    );
                  }}
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-1" />
                  ) : (
                    <div className="flex flex-col items-center">
                      <span className="text-[10px] font-bold inline-flex items-center gap-1"><ArrowDown className="h-2.5 w-2.5" />SELL</span>
                      <span className="text-[8px]">
                        {formatTradePrice(tradingInfo.sellPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                      </span>
                    </div>
                  )}
                </Button>
              </div> */}
                </div>

                {/* Section 1: Volume and Tabs */}
                <div className="col-span-1 p-2 rounded-l">
                  <div className="flex items-center mb-1">
                    <div className="flex-1">
                      <label className="text-xs">{copy.volume}</label>
                      <FormField
                        control={form.control}
                        name="volume"
                        render={({ field }) => (
                          <FormItem className="space-y-0">
                            <FormControl>
                              <Input
                                {...field}
                                value={displayVolume}
                                onChange={(e) => {
                                  handleInputChange(e);
                                }}
                                className="h-6 bg-muted border-0 text-foreground text-sm font-medium p-1"
                              />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </div>
                    <div className="flex flex-col gap-1 ml-1 mt-2.5">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-4 w-4 text-muted-foreground bg-secondary hover:text-foreground p-0"
                        onClick={() => handleVolumeChange(true)}
                      >
                        <ChevronUp className="h-3 w-3" />
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-4 w-4 text-muted-foreground bg-secondary hover:text-foreground p-0"
                        onClick={() => handleVolumeChange(false)}
                      >
                        <ChevronDown className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>

                  <Tabs
                    defaultValue="lots"
                    value={activeTab}
                    onValueChange={handleTabChange}
                    className="w-full"
                  >
                    <TabsList className="grid grid-cols-3 h-6 bg-muted">
                      <TabsTrigger
                        value="lots"
                        className="text-[10px] h-5 px-1"
                      >
                        {copy.lots}
                      </TabsTrigger>
                      <TabsTrigger
                        value="units"
                        className="text-[10px] h-5 px-1"
                      >
                        {copy.units}
                      </TabsTrigger>
                      <TabsTrigger
                        value="currency"
                        className="text-[10px] h-5 px-1"
                      >
                        {copy.currency}
                      </TabsTrigger>
                    </TabsList>
                  </Tabs>

                  <div className="grid grid-cols-1 gap-1 mt-1">
                    <Button
                      type="submit"
                      className={cn(
                        "bg-green-500 hover:bg-green-600 text-white h-8 px-1",
                        isAutoTraderLocked &&
                          "opacity-60 cursor-not-allowed hover:bg-green-500"
                      )}
                      onClick={handleTradeButtonClick("buy")}
                      disabled={isSubmitting}
                    >
                      {isSubmitting ? (
                        <Loader2 className="h-4 w-4 animate-spin mr-1" />
                      ) : (
                        <div className="flex flex-col items-center">
                          <span className="text-[10px] font-bold inline-flex items-center gap-1"><ArrowUp className="h-2.5 w-2.5" />{copy.buy}</span>
                          <span className="text-[8px]">
                            {formatTradePrice(tradingInfo.buyPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                          </span>
                        </div>
                      )}
                    </Button>
                    <Button
                      type="submit"
                      className={cn(
                        "bg-red-500 hover:bg-red-600 text-white h-8 px-1",
                        isAutoTraderLocked &&
                          "opacity-60 cursor-not-allowed hover:bg-red-500"
                      )}
                      onClick={handleTradeButtonClick("sell")}
                      disabled={isSubmitting}
                    >
                      {isSubmitting ? (
                        <Loader2 className="h-4 w-4 animate-spin mr-1" />
                      ) : (
                        <div className="flex flex-col items-center">
                          <span className="text-[10px] font-bold inline-flex items-center gap-1"><ArrowDown className="h-2.5 w-2.5" />{copy.sell}</span>
                          <span className="text-[8px]">
                            {formatTradePrice(tradingInfo.sellPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                          </span>
                        </div>
                      )}
                    </Button>
                  </div>
                </div>
              </div>
            ) : (
              // Landscape layout - simplified and balanced
              <div className="flex items-center justify-between h-full px-3">
                {/* Left side - Volume controls and tabs in one row */}
                <div className="flex items-center gap-4">
                  <div className="flex items-center space-x-1">
                    <FormField
                      control={form.control}
                      name="volume"
                      render={({ field }) => (
                        <FormItem className="space-y-0">
                          <FormControl>
                            <Input
                              {...field}
                              value={displayVolume}
                              onChange={handleInputChange}
                              className="h-7 bg-muted border-0 text-foreground text-sm w-24"
                            />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                    <div className="flex flex-col gap-0.5">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-3.5 w-3.5 bg-secondary p-0"
                        onClick={() => handleVolumeChange(true)}
                      >
                        <ChevronUp className="h-2.5 w-2.5" />
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-3.5 w-3.5 bg-secondary p-0"
                        onClick={() => handleVolumeChange(false)}
                      >
                        <ChevronDown className="h-2.5 w-2.5" />
                      </Button>
                    </div>
                  </div>
                  <Tabs
                    defaultValue="lots"
                    value={activeTab}
                    onValueChange={handleTabChange}
                    className="w-[180px]"
                  >
                    <TabsList className="grid grid-cols-3 h-7 bg-muted">
                      <TabsTrigger value="lots" className="text-xs px-2">
                        {copy.lots}
                      </TabsTrigger>
                      <TabsTrigger value="units" className="text-xs px-2">
                        {copy.units}
                      </TabsTrigger>
                      <TabsTrigger value="currency" className="text-xs px-2">
                        {copy.currency}
                      </TabsTrigger>
                    </TabsList>
                  </Tabs>{" "}
                </div>

                {/* Right side - Trading buttons */}
                <div className="flex gap-2">
                  <Button
                    type="submit"
                    className={cn(
                      "bg-green-500 hover:bg-green-600 text-white h-7 w-20",
                      isAutoTraderLocked &&
                        "opacity-60 cursor-not-allowed hover:bg-green-500"
                    )}
                    onClick={handleTradeButtonClick("buy")}
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <Loader2 className="h-3 w-3 animate-spin" />
                    ) : (
                      <span className="text-sm font-bold inline-flex items-center gap-1"><ArrowUp className="h-3.5 w-3.5" />{copy.buy}</span>
                    )}
                  </Button>
                  <Button
                    type="submit"
                    className={cn(
                      "bg-red-500 hover:bg-red-600 text-white h-7 w-20",
                      isAutoTraderLocked &&
                        "opacity-60 cursor-not-allowed hover:bg-red-500"
                    )}
                    onClick={handleTradeButtonClick("sell")}
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <Loader2 className="h-3 w-3 animate-spin" />
                    ) : (
                      <span className="text-sm font-bold inline-flex items-center gap-1"><ArrowDown className="h-3.5 w-3.5" />{copy.sell}</span>
                    )}
                  </Button>
                </div>
              </div>
            )}
          </div>

          {/* Desktop layout - stacked sections */}
          <div className="hidden lg:block">
            {/* Section 1: Volume and Tabs */}
            <div className="p-3">
              <div className="flex items-center">
                <div className="flex-1">
                  <label className="text-xs text-muted-foreground">
                    {copy.volume}
                  </label>
                  <FormField
                    control={form.control}
                    name="volume"
                    render={({ field }) => (
                      <FormItem>
                        <FormControl>
                          <Input
                            {...field}
                            value={displayVolume}
                            onChange={(e) => {
                              field.onChange(e);
                              handleInputChange(e);
                            }}
                            className="h-8 bg-muted border-0 text-foreground text-lg font-medium"
                          />
                        </FormControl>
                      </FormItem>
                    )}
                  />
                </div>
                <div className="flex flex-col gap-0.5 ml-2 mt-1.5">
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6 text-muted-foreground bg-secondary hover:text-foreground"
                    onClick={() => handleVolumeChange(true)}
                  >
                    <ChevronUp className="h-4 w-4" />
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6 text-muted-foreground bg-secondary hover:text-foreground"
                    onClick={() => handleVolumeChange(false)}
                  >
                    <ChevronDown className="h-4 w-4" />
                  </Button>
                </div>
              </div>

              <Tabs
                defaultValue="lots"
                value={activeTab}
                onValueChange={handleTabChange}
                className="w-full mt-3"
              >
                <TabsList className="grid grid-cols-3 h-8 bg-muted">
                  <TabsTrigger value="lots" className="text-xs">
                    {copy.lots}
                  </TabsTrigger>
                  <TabsTrigger value="units" className="text-xs">
                    {copy.units}
                  </TabsTrigger>
                  <TabsTrigger value="currency" className="text-xs">
                    {copy.currency}
                  </TabsTrigger>
                </TabsList>
              </Tabs>
            </div>

            <Separator className="bg-muted" />

            {/* Section 2: Trading Info */}
            <div className="p-3">
              <div className="space-y-1 text-xs">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">{copy.contract}</span>
                  <span className="text-primary">
                    {activeAsset?.contract_size}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">{copy.position}</span>
                  <span className="text-primary">
                    {tradingInfo.position.toLocaleString(undefined, {
                      maximumFractionDigits: 8,
                    })}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">{copy.margin}</span>
                  <span
                    className={cn(
                      tradingInfo.margin > accountSummary.freeMargin
                        ? "text-red-500"
                        : "text-primary"
                    )}
                  >
                    {formatCurrencyValue(
                      parseFloat(tradingInfo.margin.toFixed(2))
                    )}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">{copy.freeMargin}</span>
                  <span className="text-primary">
                    {formatCurrencyValue(
                      parseFloat(accountSummary.freeMargin.toFixed(2))
                    )}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">{copy.leverage}</span>
                  <span className="text-primary">1:{tradingInfo.leverage}</span>
                </div>
              </div>
            </div>

            <Separator className="bg-muted" />

            {/* Section 3: Profit Calculator and Buttons */}
            <div className="p-3 space-y-2">
              <Button
                type="button"
                className="flex items-center justify-center py-1 w-full h-auto bg-secondary hover:bg-secondary/50"
                onClick={() => setIsProfitCalculatorOpen(true)}
              >
                <BarChart2 className="h-4 w-4 mr-2" />
                <span className="text-sm font-medium text-foreground">
                  {copy.profitCalculator}
                </span>
              </Button>

              <div className="flex gap-2">
                <div className="space-y-1 flex-1">
                  <Button
                    type="button"
                    className="flex flex-col items-start justify-center w-full h-auto bg-secondary hover:bg-secondary/50"
                    onClick={() => setIsTpSlModalOpen(true)}
                  >
                    <div className="text-xs text-muted-foreground items-center">
                      TP & SL
                    </div>
                    <div className="text-xs text-foreground">
                      {getTpSlText()}
                    </div>
                  </Button>
                </div>

                <div className="space-y-1 flex-1">
                  <Button
                    type="button"
                    className="flex flex-col items-start justify-center w-full h-auto bg-secondary hover:bg-secondary/50"
                    onClick={() => setIsPendingModalOpen(true)}
                  >
                    <div className="text-xs text-muted-foreground">{copy.pending}</div>
                    <div className="text-xs text-foreground">{copy.market}</div>
                  </Button>
                </div>
              </div>

              <div className="grid grid-cols-[1fr_auto_1fr] items-stretch gap-1 pt-1">
                <Button
                  type="submit"
                  className={cn(
                    "bg-green-500 hover:bg-green-600 text-white h-10 text-xs",
                    isAutoTraderLocked &&
                      "opacity-60 cursor-not-allowed hover:bg-green-500"
                  )}
                  onClick={handleTradeButtonClick("buy")}
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <div className="flex items-center">
                      <Loader2 className="h-5 w-5 animate-spin mr-2" />
                      <span>Processing...</span>
                    </div>
                  ) : (
                    <div className="flex flex-col items-center">
                      <span className="font-bold inline-flex items-center gap-1"><ArrowUp className="h-4 w-4" />{copy.buy}</span>
                      <span className="text-sm">
                        {formatTradePrice(tradingInfo.buyPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                      </span>
                    </div>
                  )}
                </Button>
                <div className="flex min-w-[48px] flex-col items-center justify-center rounded bg-muted px-1 text-center text-[10px] leading-tight text-muted-foreground" title={`${copy.spread} ${spreadText}`}>
                  <span>{copy.spread.replace(":", "")}</span>
                  <span className="font-semibold text-primary">{spreadDisplay ? Number(spreadDisplay.units.toFixed(2)) : "-"}</span>
                </div>
                <Button
                  type="submit"
                  className={cn(
                    "bg-red-500 hover:bg-red-600 text-white h-10 text-xs",
                    isAutoTraderLocked &&
                      "opacity-60 cursor-not-allowed hover:bg-red-500"
                  )}
                  onClick={handleTradeButtonClick("sell")}
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <div className="flex items-center">
                      <Loader2 className="h-5 w-5 animate-spin mr-2" />
                      <span>Processing...</span>
                    </div>
                  ) : (
                    <div className="flex flex-col items-center">
                      <span className="font-bold inline-flex items-center gap-1"><ArrowDown className="h-4 w-4" />{copy.sell}</span>
                      <span className="text-sm">
                        {formatTradePrice(tradingInfo.sellPrice, activeAsset?.type, activeAsset?.sy ?? "")}
                      </span>
                    </div>
                  )}
                </Button>
              </div>
            </div>
          </div>
        </form>
      </Form>

      <ProfitCalculatorModal
        open={isProfitCalculatorOpen}
        onOpenChange={setIsProfitCalculatorOpen}
        symbol={activeAsset?.symbol_display || ""}
        asset={activeAsset}
      />

      <TakeProfitStopLossModal
        open={isTpSlModalOpen}
        onOpenChange={setIsTpSlModalOpen}
        currentTakeProfit={form.getValues().takeProfit}
        currentStopLoss={form.getValues().stopLoss}
        asset={activeAsset}
        currentPrice={
          activeAsset ? Number.parseFloat(activeAsset.rate || "0") : 0
        }
        onApplyValues={(takeProfit, stopLoss) => {
          form.setValue("takeProfit", takeProfit);
          form.setValue("stopLoss", stopLoss);
        }}
      />
      <PendingOrderModal
        open={isPendingModalOpen}
        onOpenChange={setIsPendingModalOpen}
        currentPrice={
          activeAsset ? Number.parseFloat(activeAsset.rate || "0") : 0
        }
      />
    </div>
  );
}

export default TradingInterface;

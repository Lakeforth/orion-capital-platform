import TradingViewWidget from "../../trading/trading-interface-components/trading-view-widget";
// import BuySellFloatingButtons from "./buy-sell-floating-buttons";

export default function ChartArea() {
  return (
    <div className="flex-1 bg-[#1C2030] relative h-full">
      <div className="h-full">
        <TradingViewWidget />
      </div>
      {/* <BuySellFloatingButtons /> */}
    </div>
  );
}

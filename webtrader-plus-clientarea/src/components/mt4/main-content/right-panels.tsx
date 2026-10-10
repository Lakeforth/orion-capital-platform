import AlgoTrader from "../right-panels/algo-trader";
import MarketWatch from "../right-panels/market-watch";


export default function RightPanels() {
  return (
    <aside className="h-full bg-slate-800 border-l border-slate-700 w-full">
      <div className="h-full grid grid-rows-2">
        <div className="overflow-hidden">
          <MarketWatch />
        </div>
        <div className="overflow-hidden border-t border-slate-700">
          <AlgoTrader />
        </div>
      </div>
    </aside>
  );
}

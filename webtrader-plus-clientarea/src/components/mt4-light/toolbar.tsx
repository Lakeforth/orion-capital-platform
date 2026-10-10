import { Button } from "@/components/ui/button";
import { Plus, Menu } from "lucide-react";
import useDarkModeStore from "@/store/darkModeStore";
import { useCallback, useState } from "react";
import { OrderWindow } from "./order-window";
import { Switch } from "@/components/ui/switch";
import { useAiTradeToggle } from "@/hooks/useAiTradeToggle";
import BuySellFloatingButtonsLight from "./chart-area/buy-sell-floating-buttons-light";

interface ToolbarProps {
  isMobile?: boolean;
  onToggleSidebar?: () => void;
}

export function Toolbar({ isMobile, onToggleSidebar }: ToolbarProps) {
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);
  const [isTradeModalOpen, setIsTradeModalOpen] = useState(false);
  const closeOrderWindow = useCallback(() => setIsTradeModalOpen(false), []);
  const aiTrade = useAiTradeToggle();

  return (
    <div
      className={`${
        isDarkMode
          ? "bg-[#1A202C] border-[#4A5568]"
          : "bg-[#F0F0F0] border-[#C3C3C3]"
      } border-b px-4 h-[38px] flex items-center justify-between shadow-[inset_0_4px_20px_0_rgba(0,0,0,0.05)]`}
    >
      <div className="flex items-center gap-4">
        {isMobile && (
          <Button
            variant="ghost"
            size="icon"
            onClick={onToggleSidebar}
            className="md:hidden"
          >
            <Menu className="h-5 w-5" />
          </Button>
        )}
        <Button
          onClick={() => setIsTradeModalOpen((open) => !open)}
          aria-pressed={isTradeModalOpen}
          variant="outline"
          className={`h-7 px-3 flex items-center gap-2 text-[12px] rounded-md shadow-sm transition-colors font-medium ${
            isDarkMode
              ? "bg-slate-800 border-slate-600 text-slate-200 hover:bg-slate-700"
              : "bg-gradient-to-b from-[#F8FAFC] to-[#EDF2F7] border-[#CBD5E0] text-[#4A5568] hover:from-[#EDF2F7] hover:to-[#E2E8F0]"
          }`}
        >
          <Plus className={`h-4 w-4 ${isDarkMode ? "text-blue-400" : "text-blue-600 font-bold"}`} />
          New Order
        </Button>
        <OrderWindow open={isTradeModalOpen} onClose={closeOrderWindow} />
        {/* AI Trading panel toggle: shows the AI Trading module over the chart while on (tenants with AI trading enabled). */}
        {aiTrade.available && (
          <label
            htmlFor="mt4-ai-trading-toggle"
            className={`h-7 pl-2.5 pr-1.5 flex items-center gap-2 rounded-md border cursor-pointer select-none ${
              isDarkMode ? "bg-slate-800 border-slate-600" : "bg-gradient-to-b from-[#F8FAFC] to-[#EDF2F7] border-[#CBD5E0]"
            }`}
          >
            <span className="flex flex-col leading-none">
              <span className={`text-[10px] font-bold uppercase tracking-wider ${aiTrade.active ? "text-emerald-500" : isDarkMode ? "text-slate-300" : "text-[#4A5568]"}`}>
                AI Trader
              </span>
              <span className={`text-[9px] font-medium ${aiTrade.active ? "text-emerald-500/80" : "text-slate-400"}`}>
                {aiTrade.active ? "ACTIVE" : "INACTIVE"}
              </span>
            </span>
            <Switch
              id="mt4-ai-trading-toggle"
              checked={aiTrade.active}
              disabled={aiTrade.pending}
              onCheckedChange={aiTrade.toggle}
              aria-label="AI Trading"
              className="scale-90 data-[state=checked]:bg-emerald-500"
            />
          </label>
        )}
      </div>
      <div className="flex items-center gap-2">
        <BuySellFloatingButtonsLight />
      </div>
    </div>
  );
}

import type React from "react";
import {
  BarChart3,
  // Play,
  // Star,
  // History,
  LineChart,
  Clock,
  Calendar,
  Newspaper,
  ClipboardList,
} from "lucide-react";
import { useMobile } from "@/hooks/use-mobile";
import useOverlayStore, { type PanelType } from "@/store/overlayStore";
import { cn } from "@/lib/utils";

interface SidebarItem {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  isActive?: boolean;
  panelId: PanelType;
}

const sidebarItems: SidebarItem[] = [
  {
    icon: BarChart3,
    label: "TRADE ROOM",
    panelId: "trade-room", // This item should not toggle
    isActive: true, // This item is always active
  },
  { icon: LineChart, label: "MARKET WATCH", panelId: "market-watch" },
  { icon: ClipboardList, label: "ACTIVE ORDERS", panelId: "active-orders" },
  { icon: Clock, label: "TRADING HISTORY", panelId: "trading-history" },
  { icon: Calendar, label: "CALENDAR", panelId: "calendar" },
  { icon: Newspaper, label: "MARKET NEWS", panelId: "market-news" },
  // { icon: Star, label: "WATCH LIST", panelId: "watch-list" },
  // { icon: History, label: "FINANCIAL HISTORY", panelId: "financial-history" },
  // { icon: Play, label: "VIDEO GUIDES", panelId: "video-guides" },
  // {
  //   icon: TrendingUp,
  //   label: "TECHNICAL ANALYSIS",
  //   panelId: "technical-analysis",
  // },
];

export default function Sidebar() {
  const isMobile = useMobile(768);
  const { activePanel, setActivePanel } = useOverlayStore();

  const handleItemClick = (item: SidebarItem) => {
    if (item.panelId === "trade-room") return; // Don't toggle for trade room
    setActivePanel(activePanel === item.panelId ? null : item.panelId);
  };

  return (
    <div
      className={cn(
        "flex flex-col bg-background border-r border-border transition-all duration-300",
        isMobile ? "w-[60px]" : "w-[62px]"
      )}
    >
      <nav className="flex flex-col items-center py-4  h-full">
        {sidebarItems.map((item, index) => {
          const Icon = item.icon;
          const isSelected = activePanel === item.panelId || item.isActive;
          return (
            <button
              key={index}
              className={cn(
                "flex flex-col items-center justify-center w-full py-4 px-1 transition-colors",
                isSelected
                  ? "bg-slate-700"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted/50"
              )}
              onClick={() => handleItemClick(item)}
            >
              <Icon className="h-5 w-5 mb-1" />
              {!isMobile && (
                <span className="text-[9px] font-medium">{item.label}</span>
              )}
            </button>
          );
        })}
      </nav>
    </div>
  );
}

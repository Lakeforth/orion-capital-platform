import React, { useState } from "react";
import {
  format,
  addMonths,
  subMonths,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  eachDayOfInterval,
  isSameMonth,
  isToday,
  setMonth as setDateMonth,
  setYear as setDateYear,
  getYear,
} from "date-fns";
import {
  Plus,
  Calendar as CalendarIcon,
  Clock,
  Trash2,
  Edit2,
  Timer,
  ChevronLeft,
  ChevronRight,
  TrendingUp,
  TrendingDown,
  ArrowRight,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";

// Mock data for initial implementation
const MOCK_ASSETS = ["BTC/USD", "ETH/USD", "EUR/USD", "GBP/JPY", "GOLD", "OIL"];
const MOCK_DURATIONS = ["1m", "5m", "15m", "30m", "1h", "4h", "1d"];

interface QueuedTrade {
  id: string;
  asset: string;
  type: "buy" | "sell";
  amount: number;
  duration: string;
  profitTarget: number;
  scheduledTime: Date;
  status: "pending" | "executing" | "completed" | "cancelled";
}

const AITradingPage: React.FC = () => {
  const [queuedTrades, setQueuedTrades] = useState<QueuedTrade[]>([
    {
      id: "1",
      asset: "BTC/USD",
      type: "buy",
      amount: 1000,
      duration: "15m",
      profitTarget: 2.5,
      scheduledTime: new Date(Date.now() + 1000 * 60 * 30), // 30 mins from now
      status: "pending",
    },
    {
      id: "2",
      asset: "EUR/USD",
      type: "sell",
      amount: 500,
      duration: "5m",
      profitTarget: 1.2,
      scheduledTime: new Date(Date.now() + 1000 * 60 * 60 * 2), // 2 hours from now
      status: "pending",
    },
  ]);

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [editingTrade, setEditingTrade] = useState<QueuedTrade | null>(null);
  const [viewMode, setViewMode] = useState<"list" | "calendar">("list");

  // Form states
  const [formData, setFormData] = useState({
    asset: "BTC/USD",
    type: "buy" as "buy" | "sell",
    amount: 1000,
    duration: "15m",
    profitTarget: 2.5,
    scheduledTime: new Date(Date.now() + 1000 * 60 * 30),
  });

  const handleScheduleTrade = () => {
    if (editingTrade) {
      setQueuedTrades((prev) =>
        prev.map((t) => (t.id === editingTrade.id ? { ...t, ...formData } : t)),
      );
    } else {
      const newTrade: QueuedTrade = {
        id: Math.random().toString(36).substr(2, 9),
        ...formData,
        status: "pending",
      };
      setQueuedTrades((prev) => [newTrade, ...prev]);
    }
    setIsAddModalOpen(false);
    setEditingTrade(null);
  };

  const handleEdit = (trade: QueuedTrade) => {
    setEditingTrade(trade);
    setFormData({
      asset: trade.asset,
      type: trade.type,
      amount: trade.amount,
      duration: trade.duration,
      profitTarget: trade.profitTarget,
      scheduledTime: trade.scheduledTime,
    });
    setIsAddModalOpen(true);
  };

  // Simulate real-time status updates
  React.useEffect(() => {
    const interval = setInterval(() => {
      setQueuedTrades((prev) =>
        prev.map((trade) => {
          if (trade.status === "pending" && trade.scheduledTime <= new Date()) {
            return { ...trade, status: "executing" };
          }
          if (trade.status === "executing") {
            // After 10 seconds of executing, mark as completed
            const executionTime =
              new Date().getTime() - trade.scheduledTime.getTime();
            if (executionTime > 10000) {
              return { ...trade, status: "completed" };
            }
          }
          return trade;
        }),
      );
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="bg-[#EDF0F4] min-h-screen p-6 md:p-8 space-y-8">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#00234B]">AI Trading Hub</h1>
          <p className="text-gray-500 mt-1">
            Automate your trading strategies with scheduled AI execution.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <div className="bg-white p-1 rounded-xl border border-gray-200 flex">
            <button
              onClick={() => setViewMode("list")}
              className={cn(
                "px-4 py-2 rounded-lg text-sm font-bold transition-all",
                viewMode === "list"
                  ? "bg-[#00234B] text-white shadow-md"
                  : "text-gray-500 hover:bg-gray-50",
              )}
            >
              List View
            </button>
            <button
              onClick={() => setViewMode("calendar")}
              className={cn(
                "px-4 py-2 rounded-lg text-sm font-bold transition-all",
                viewMode === "calendar"
                  ? "bg-[#00234B] text-white shadow-md"
                  : "text-gray-500 hover:bg-gray-50",
              )}
            >
              Calendar
            </button>
          </div>
          <Button
            onClick={() => {
              setEditingTrade(null);
              setIsAddModalOpen(true);
            }}
            className="bg-[#FF7700] hover:bg-[#FF8811] text-white px-6 py-6 rounded-xl font-bold text-base flex items-center gap-2 shadow-lg shadow-[#FF7700]/20 transition-all transform active:scale-95"
          >
            <Plus size={20} />
            Schedule Trade
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Main Content Area */}
        <div className="lg:col-span-2 space-y-6">
          {viewMode === "list" ? (
            <QueueList
              trades={queuedTrades}
              onCancel={(id) =>
                setQueuedTrades((prev) => prev.filter((t) => t.id !== id))
              }
              onEdit={handleEdit}
            />
          ) : (
            <CalendarPlanner />
          )}
        </div>

        {/* Sidebar / Stats Area */}
        <div className="space-y-6">
          <AIStats />
          <MarketPulse />
        </div>
      </div>

      {/* Add/Edit Trade Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-[24px] w-full max-w-lg p-8 shadow-2xl animate-in fade-in zoom-in duration-200">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-[#00234B]">
                {editingTrade
                  ? "Edit Scheduled Trade"
                  : "Schedule New AI Trade"}
              </h2>
              <button
                onClick={() => setIsAddModalOpen(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <Plus className="rotate-45" size={24} />
              </button>
            </div>

            <p className="text-gray-500 mb-8">
              Configure your AI trade parameters and schedule execution.
            </p>

            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                    Asset
                  </label>
                  <select
                    value={formData.asset}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        asset: e.target.value,
                      }))
                    }
                    className="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold text-[#00234B] outline-none focus:border-[#FF7700] transition-all"
                  >
                    {MOCK_ASSETS.map((asset) => (
                      <option key={asset}>{asset}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                    Type
                  </label>
                  <div className="flex bg-gray-50 p-1 rounded-xl border border-gray-200">
                    <button
                      onClick={() =>
                        setFormData((prev) => ({ ...prev, type: "buy" }))
                      }
                      className={cn(
                        "flex-1 py-2 rounded-lg font-bold text-sm transition-all",
                        formData.type === "buy"
                          ? "bg-[#22C55E] text-white shadow-sm"
                          : "text-gray-400 hover:bg-gray-100",
                      )}
                    >
                      BUY
                    </button>
                    <button
                      onClick={() =>
                        setFormData((prev) => ({ ...prev, type: "sell" }))
                      }
                      className={cn(
                        "flex-1 py-2 rounded-lg font-bold text-sm transition-all",
                        formData.type === "sell"
                          ? "bg-[#EF4444] text-white shadow-sm"
                          : "text-gray-400 hover:bg-gray-100",
                      )}
                    >
                      SELL
                    </button>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                    Amount ($)
                  </label>
                  <input
                    type="number"
                    value={formData.amount}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        amount: Number(e.target.value),
                      }))
                    }
                    className="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold text-[#00234B] outline-none focus:border-[#FF7700] transition-all"
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                    Duration
                  </label>
                  <select
                    value={formData.duration}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        duration: e.target.value,
                      }))
                    }
                    className="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold text-[#00234B] outline-none focus:border-[#FF7700] transition-all"
                  >
                    {MOCK_DURATIONS.map((d) => (
                      <option key={d}>{d}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                  Profit Target (%)
                </label>
                <div className="relative">
                  <input
                    type="number"
                    step="0.1"
                    value={formData.profitTarget}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        profitTarget: Number(e.target.value),
                      }))
                    }
                    className="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold text-[#00234B] outline-none focus:border-[#FF7700] transition-all"
                  />
                  <div className="absolute right-4 top-1/2 -translate-y-1/2 text-[#22C55E] font-bold">
                    TARGET
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                  Execution Time
                </label>
                <input
                  type="datetime-local"
                  value={new Date(
                    formData.scheduledTime.getTime() -
                      formData.scheduledTime.getTimezoneOffset() * 60000,
                  )
                    .toISOString()
                    .slice(0, 16)}
                  onChange={(e) =>
                    setFormData((prev) => ({
                      ...prev,
                      scheduledTime: new Date(e.target.value),
                    }))
                  }
                  className="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold text-[#00234B] outline-none focus:border-[#FF7700] transition-all"
                />
              </div>
            </div>

            <div className="mt-8 flex gap-4">
              <Button
                className="flex-1 bg-[#FF7700] hover:bg-[#FF8811] text-white py-4 rounded-xl font-bold shadow-lg shadow-[#FF7700]/20"
                onClick={handleScheduleTrade}
              >
                {editingTrade ? "Update Schedule" : "Schedule Now"}
              </Button>
              <Button
                variant="ghost"
                className="flex-1 border border-gray-200 text-gray-500 py-4 rounded-xl font-bold"
                onClick={() => setIsAddModalOpen(false)}
              >
                Cancel
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const QueueList: React.FC<{
  trades: QueuedTrade[];
  onCancel: (id: string) => void;
  onEdit: (trade: QueuedTrade) => void;
}> = ({ trades, onCancel, onEdit }) => {
  return (
    <div className="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
      <div className="flex justify-between items-center mb-8">
        <h3 className="text-[22px] font-bold text-[#00234B]">Trade Queue</h3>
        <div className="flex items-center gap-2 text-sm text-gray-400 font-medium">
          <Clock size={16} />
          <span>
            {trades.filter((t) => t.status === "pending").length} Active
            Schedules
          </span>
        </div>
      </div>

      <div className="space-y-4">
        {trades.map((trade) => (
          <div
            key={trade.id}
            className="group flex flex-col md:flex-row md:items-center gap-6 p-6 rounded-2xl bg-[#F4F7FF] hover:bg-[#EDF2FF] transition-all border border-transparent hover:border-[#D1E0FF]"
          >
            <div
              className={cn(
                "w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm transition-transform group-hover:scale-110",
                trade.type === "buy"
                  ? "bg-[#DCFCE7] text-[#166534]"
                  : "bg-[#FEE2E2] text-[#991B1B]",
              )}
            >
              {trade.type === "buy" ? (
                <TrendingUp size={28} />
              ) : (
                <TrendingDown size={28} />
              )}
            </div>

            <div className="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
              <div>
                <h4 className="font-bold text-[#00234B] text-lg">
                  {trade.asset}
                </h4>
                <div className="flex items-center gap-2">
                  <span
                    className={cn(
                      "text-[10px] font-bold uppercase tracking-wider",
                      trade.type === "buy"
                        ? "text-[#22C55E]"
                        : "text-[#EF4444]",
                    )}
                  >
                    {trade.type}
                  </span>
                  <span className="w-1 h-1 bg-gray-300 rounded-full" />
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    {trade.duration}
                  </span>
                </div>
              </div>

              <div>
                <p className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">
                  Amount
                </p>
                <p className="font-bold text-[#00234B]">
                  ${trade.amount.toLocaleString()}
                </p>
              </div>

              <div>
                <p className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">
                  Execution
                </p>
                <div className="flex items-center gap-2 font-bold text-[#00234B]">
                  <Timer size={14} className="text-[#FF7700]" />
                  <span>
                    {trade.scheduledTime.toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-3 justify-end">
                <span
                  className={cn(
                    "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider",
                    trade.status === "pending"
                      ? "bg-blue-100 text-blue-600"
                      : trade.status === "executing"
                        ? "bg-yellow-100 text-yellow-600 animate-pulse"
                        : trade.status === "completed"
                          ? "bg-green-100 text-green-600"
                          : "bg-gray-100 text-gray-600",
                  )}
                >
                  {trade.status}
                </span>

                {trade.status === "pending" && (
                  <div className="flex gap-1">
                    <button
                      onClick={() => onEdit(trade)}
                      className="p-2.5 rounded-lg bg-white text-gray-400 hover:text-[#00234B] border border-gray-100 shadow-sm hover:shadow-md transition-all"
                    >
                      <Edit2 size={16} />
                    </button>
                    <button
                      onClick={() => onCancel(trade.id)}
                      className="p-2.5 rounded-lg bg-white text-gray-400 hover:text-[#EF4444] border border-gray-100 shadow-sm hover:shadow-md transition-all"
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        ))}

        {trades.length === 0 && (
          <div className="text-center py-12">
            <div className="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <CalendarIcon size={32} className="text-gray-300" />
            </div>
            <h4 className="text-lg font-bold text-[#00234B]">
              No scheduled trades
            </h4>
            <p className="text-gray-500 mt-1">
              Schedule your first AI trade to see it here.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

const CalendarPlanner: React.FC = () => {
  const [currentMonth, setCurrentMonth] = useState(new Date());
  const [selectedDay, setSelectedDay] = useState<Date | null>(null);
  const [isPickerOpen, setIsPickerOpen] = useState(false);

  const daysInMonth = eachDayOfInterval({
    start: startOfWeek(startOfMonth(currentMonth)),
    end: endOfWeek(endOfMonth(currentMonth)),
  });

  const nextMonth = () => setCurrentMonth(addMonths(currentMonth, 1));
  const prevMonth = () => setCurrentMonth(subMonths(currentMonth, 1));

  const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  const currentYear = getYear(currentMonth);
  const years = Array.from({ length: 10 }, (_, i) => currentYear - 5 + i);

  const handleMonthSelect = (monthIndex: number) => {
    setCurrentMonth(setDateMonth(currentMonth, monthIndex));
  };

  const handleYearSelect = (year: number) => {
    setCurrentMonth(setDateYear(currentMonth, year));
  };

  return (
    <div className="bg-white rounded-xl p-8 shadow-sm border border-gray-100 min-h-[600px] transition-all duration-300 relative">
      {!selectedDay ? (
        <div className="animate-in fade-in duration-500">
          <div className="flex justify-between items-center mb-8">
            <div>
              <h3 className="text-[22px] font-bold text-[#00234B]">
                30-Day Planner
              </h3>
              <p className="text-sm text-gray-400 font-medium">
                Select a day to schedule trades
              </p>
            </div>
            <div className="flex items-center gap-4 bg-gray-50 p-1 rounded-xl border border-gray-100 relative">
              <button
                onClick={prevMonth}
                className="p-2 rounded-lg hover:bg-[#00234B] hover:text-white transition-all text-gray-400"
              >
                <ChevronLeft size={20} />
              </button>

              <div className="relative">
                <button
                  onClick={() => setIsPickerOpen(!isPickerOpen)}
                  className="px-4 py-2 font-bold text-[#00234B] min-w-[140px] text-center hover:bg-white rounded-lg transition-all flex items-center justify-center gap-2"
                >
                  {format(currentMonth, "MMMM yyyy")}
                  <ChevronRight
                    size={16}
                    className={cn(
                      "transition-transform",
                      isPickerOpen ? "rotate-90" : "rotate-0",
                    )}
                  />
                </button>

                {isPickerOpen && (
                  <>
                    <div
                      className="fixed inset-0 z-20"
                      onClick={() => setIsPickerOpen(false)}
                    />
                    <div className="absolute top-full left-1/2 -translate-x-1/2 mt-2 bg-white border border-gray-100 shadow-2xl rounded-2xl p-4 z-30 min-w-[280px] animate-in fade-in slide-in-from-top-2 duration-200">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-2 mb-2">
                            Month
                          </p>
                          <div className="max-h-[200px] overflow-y-auto custom-scrollbar pr-1">
                            {months.map((month, idx) => (
                              <button
                                key={month}
                                onClick={() => {
                                  handleMonthSelect(idx);
                                  // Keep open to allow year selection too
                                }}
                                className={cn(
                                  "w-full text-left px-3 py-2 rounded-lg text-sm font-bold transition-all",
                                  idx === currentMonth.getMonth()
                                    ? "bg-[#00234B] text-white"
                                    : "text-[#00234B] hover:bg-gray-50",
                                )}
                              >
                                {month}
                              </button>
                            ))}
                          </div>
                        </div>
                        <div className="space-y-1">
                          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-2 mb-2">
                            Year
                          </p>
                          <div className="max-h-[200px] overflow-y-auto custom-scrollbar pr-1">
                            {years.map((year) => (
                              <button
                                key={year}
                                onClick={() => {
                                  handleYearSelect(year);
                                }}
                                className={cn(
                                  "w-full text-left px-3 py-2 rounded-lg text-sm font-bold transition-all",
                                  year === currentYear
                                    ? "bg-[#00234B] text-white"
                                    : "text-[#00234B] hover:bg-gray-50",
                                )}
                              >
                                {year}
                              </button>
                            ))}
                          </div>
                        </div>
                      </div>
                      <div className="mt-4 pt-4 border-t border-gray-50">
                        <button
                          onClick={() => setIsPickerOpen(false)}
                          className="w-full bg-gray-50 hover:bg-gray-100 text-[#00234B] py-2 rounded-xl text-xs font-bold transition-all"
                        >
                          Done
                        </button>
                      </div>
                    </div>
                  </>
                )}
              </div>

              <button
                onClick={nextMonth}
                className="p-2 rounded-lg hover:bg-[#00234B] hover:text-white transition-all text-gray-400"
              >
                <ChevronRight size={20} />
              </button>
            </div>
          </div>

          <div className="grid grid-cols-7 gap-2 mb-4">
            {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => (
              <div
                key={day}
                className="text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest"
              >
                {day}
              </div>
            ))}
          </div>

          <div className="grid grid-cols-7 gap-2">
            {daysInMonth.map((day, i) => {
              const isCurrentMonth = isSameMonth(day, currentMonth);
              const isTodayDay = isToday(day);
              const dayNum = format(day, "d");

              return (
                <div
                  key={i}
                  onClick={() => setSelectedDay(day)}
                  className={cn(
                    "h-24 rounded-xl border p-2 transition-all cursor-pointer hover:border-[#FF7700] group relative overflow-hidden",
                    isCurrentMonth ? "bg-white" : "bg-gray-50/30 opacity-40",
                    isTodayDay
                      ? "border-[#FF7700] ring-1 ring-[#FF7700]/20"
                      : "border-gray-100",
                    "hover:shadow-md",
                  )}
                >
                  <span
                    className={cn(
                      "text-sm font-bold",
                      isTodayDay ? "text-[#FF7700]" : "text-[#00234B]",
                      !isCurrentMonth && "text-gray-300",
                    )}
                  >
                    {dayNum}
                  </span>

                  {/* Mock trades indicator for demo */}
                  {(format(day, "d") === "18" || format(day, "d") === "22") &&
                    isCurrentMonth && (
                      <div className="mt-2 space-y-1">
                        <div className="h-1.5 w-full bg-[#FF7700] rounded-full" />
                        <div className="text-[9px] font-bold text-[#FF7700] truncate">
                          {format(day, "d") === "18" ? "2 TRADES" : "1 TRADE"}
                        </div>
                      </div>
                    )}
                </div>
              );
            })}
          </div>
        </div>
      ) : (
        <div className="animate-in slide-in-from-right-4 duration-500">
          <div className="flex justify-between items-center mb-8">
            <div className="flex items-center gap-4">
              <button
                onClick={() => setSelectedDay(null)}
                className="p-2 rounded-lg bg-gray-50 text-gray-500 hover:bg-[#00234B] hover:text-white transition-all shadow-sm"
              >
                <ChevronLeft size={20} />
              </button>
              <div>
                <h3 className="text-xl font-bold text-[#00234B]">
                  Hourly Schedule
                </h3>
                <p className="text-sm text-gray-400 font-medium">
                  {format(selectedDay, "MMMM d, yyyy")}
                </p>
              </div>
            </div>
            <Button
              variant="ghost"
              className="text-sm font-bold text-gray-400 hover:text-[#00234B]"
            >
              Full Day View <ArrowRight size={16} className="ml-2" />
            </Button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
            {Array.from({ length: 24 }).map((_, hour) => {
              const hasTrade =
                format(selectedDay, "d") === "18" &&
                (hour === 10 || hour === 14);
              return (
                <div
                  key={hour}
                  className={cn(
                    "p-4 rounded-xl border flex items-center justify-between group transition-all",
                    hasTrade
                      ? "bg-[#F4F7FF] border-[#D1E0FF] shadow-sm"
                      : "bg-gray-50 border-gray-100 hover:bg-white hover:border-gray-200 hover:shadow-sm",
                  )}
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={cn(
                        "w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xs",
                        hasTrade
                          ? "bg-[#00234B] text-white"
                          : "bg-white text-gray-400 border border-gray-100",
                      )}
                    >
                      {hour.toString().padStart(2, "0")}:00
                    </div>
                    <div>
                      <p className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        Slot
                      </p>
                      <p
                        className={cn(
                          "font-bold text-sm",
                          hasTrade ? "text-[#00234B]" : "text-gray-300",
                        )}
                      >
                        {hasTrade
                          ? hour === 10
                            ? "BTC/USD BUY"
                            : "ETH/USD SELL"
                          : "Available"}
                      </p>
                    </div>
                  </div>
                  {hasTrade ? (
                    <div className="w-2 h-2 rounded-full bg-[#FF7700] shadow-[0_0_8px_#FF7700]" />
                  ) : (
                    <Plus
                      size={16}
                      className="text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"
                    />
                  )}
                </div>
              );
            })}
          </div>
          <style>{`
            .custom-scrollbar::-webkit-scrollbar {
              width: 4px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
              background: transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
              background: #E5E7EB;
              border-radius: 10px;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
              background: #D1D5DB;
            }
          `}</style>
        </div>
      )}
    </div>
  );
};

const AIStats: React.FC = () => {
  return (
    <div className="bg-[#00234B] rounded-[24px] p-8 text-white shadow-xl relative overflow-hidden">
      <div className="relative z-10">
        <h3 className="text-xl font-bold mb-6">AI Performance</h3>
        <div className="space-y-6">
          <div>
            <p className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">
              Success Rate
            </p>
            <div className="flex items-end gap-3">
              <span className="text-4xl font-bold">88.4%</span>
              <span className="text-green-400 text-sm font-bold mb-1">
                +2.1%
              </span>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 pt-6 border-t border-white/10">
            <div>
              <p className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">
                Profit
              </p>
              <p className="text-xl font-bold">+$12,450</p>
            </div>
            <div>
              <p className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">
                Trades
              </p>
              <p className="text-xl font-bold">142</p>
            </div>
          </div>
        </div>
      </div>
      {/* Background Graphic */}
      <div className="absolute top-0 right-0 -mr-16 -mt-16 opacity-10">
        <TrendingUp size={240} strokeWidth={1} />
      </div>
    </div>
  );
};

const MarketPulse: React.FC = () => {
  return (
    <div className="bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
      <h3 className="text-xl font-bold text-[#00234B] mb-6">Market Pulse</h3>
      <div className="space-y-4">
        {[
          { asset: "BTC/USD", sentiment: "Bullish", strength: 85 },
          { asset: "ETH/USD", sentiment: "Neutral", strength: 52 },
          { asset: "EUR/USD", sentiment: "Bearish", strength: 74 },
        ].map((item) => (
          <div key={item.asset} className="space-y-2">
            <div className="flex justify-between items-center">
              <span className="font-bold text-[#00234B]">{item.asset}</span>
              <span
                className={cn(
                  "text-xs font-bold px-2 py-1 rounded-md",
                  item.sentiment === "Bullish"
                    ? "bg-green-100 text-green-600"
                    : item.sentiment === "Bearish"
                      ? "bg-red-100 text-red-600"
                      : "bg-gray-100 text-gray-600",
                )}
              >
                {item.sentiment}
              </span>
            </div>
            <div className="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
              <div
                className={cn(
                  "h-full rounded-full transition-all duration-1000",
                  item.sentiment === "Bullish"
                    ? "bg-green-500"
                    : item.sentiment === "Bearish"
                      ? "bg-red-500"
                      : "bg-gray-400",
                )}
                style={{ width: `${item.strength}%` }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default AITradingPage;

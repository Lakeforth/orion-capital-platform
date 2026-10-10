import * as React from "react";
import { Card } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  TooltipProps,
} from "recharts";
import useUserStore from "@/store/userStore";
import { useCurrency } from "@/hooks/useCurrency";
import {
  NameType,
  ValueType,
} from "recharts/types/component/DefaultTooltipContent";

interface ChartData {
  name: string;
  value: number;
}

const generateEmptyData = (period: "week" | "month"): ChartData[] => {
  if (period === "week") {
    return ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"].map((day) => ({
      name: day,
      value: 0,
    }));
  }
  return [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ].map((month) => ({
    name: month,
    value: 0,
  }));
};

export function TradingResultsChart() {
  const [activeTab, setActiveTab] = React.useState<"week" | "month">("week");
  const user = useUserStore((state) => state.user);
  const { formatCurrency } = useCurrency();

  const tradesSummary = user?.trades_summary;
  const hasTradeData = tradesSummary && tradesSummary.trades_count > 0;

  // Get the appropriate data based on period and trade existence
  const chartData = React.useMemo(() => {
    if (!hasTradeData) {
      return generateEmptyData(activeTab);
    }

    // In a real implementation, you would use actual weekly/monthly data from your API
    // For now, we'll distribute the total PNL across the periods as a placeholder
    const totalPnl = tradesSummary?.total_pnl || 0;
    const data = generateEmptyData(activeTab);
    const perPeriodValue = totalPnl / data.length;

    return data.map((item, index) => ({
      name: item.name,
      value: perPeriodValue * (index + 1),
    }));
  }, [activeTab, hasTradeData, tradesSummary]);

  const CustomTooltip: React.FC<TooltipProps<ValueType, NameType>> = ({
    active,
    payload,
    label,
  }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-card border border-border p-2 rounded-md shadow-sm">
          <p className="text-sm text-muted-foreground">{label}</p>
          <p className="text-sm font-medium">
            {formatCurrency(payload[0].value as number)}
          </p>
        </div>
      );
    }
    return null;
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-semibold">Trading Results</h2>
          {!hasTradeData && (
            <p className="text-sm text-muted-foreground mt-1">
              No trading data available
            </p>
          )}
        </div>
        <Tabs
          defaultValue="week"
          onValueChange={(value) => setActiveTab(value as "week" | "month")}
        >
          <TabsList>
            <TabsTrigger value="week">Week</TabsTrigger>
            <TabsTrigger value="month">Month</TabsTrigger>
          </TabsList>
        </Tabs>
      </div>
      <Card className="bg-card text-card-foreground p-4">
        <div className="h-[200px] w-full">
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart
              data={chartData}
              margin={{ top: 10, right: 30, left: 0, bottom: 0 }}
            >
              <CartesianGrid
                strokeDasharray="3 3"
                stroke="var(--border)"
                opacity={0.2}
              />
              <XAxis
                dataKey="name"
                stroke="var(--muted-foreground)"
                fontSize={12}
              />
              <YAxis
                stroke="var(--muted-foreground)"
                fontSize={12}
                tickFormatter={(value) => formatCurrency(value)}
              />
              <Tooltip content={<CustomTooltip />} />
              <defs>
                <linearGradient id="gradientArea" x1="0" y1="0" x2="0" y2="1">
                  <stop
                    offset="0%"
                    stopColor="var(--primary)"
                    stopOpacity={0.5}
                  />
                  <stop
                    offset="100%"
                    stopColor="var(--primary)"
                    stopOpacity={0.1}
                  />
                </linearGradient>
              </defs>
              <Area
                type="monotone"
                dataKey="value"
                stroke="var(--primary)"
                fill="url(#gradientArea)"
                strokeWidth={2}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </Card>
    </div>
  );
}

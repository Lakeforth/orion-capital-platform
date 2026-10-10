import * as React from "react";
import { Card } from "@/components/ui/card";
import { Pie, PieChart, Cell } from "recharts";
import { ChartConfig, ChartContainer } from "@/components/ui/chart";
import useUserStore from "@/store/userStore";

const getChartData = (wins: number, losses: number) => {
  // If both are 0, show empty state
  if (wins === 0 && losses === 0) {
    return [{ name: "profit", value: 100, fill: "var(--muted)" }];
  }

  const total = wins + losses;
  return [
    {
      name: "profit",
      value: (wins / total) * 100,
      fill: "var(--primary)",
    },
    {
      name: "loss",
      value: (losses / total) * 100,
      fill: "var(--muted)",
    },
  ];
};

const chartConfig = {
  profit: {
    label: "Profit",
    color: "var(--primary)",
  },
  loss: {
    label: "Loss",
    color: "var(--muted)",
  },
} satisfies ChartConfig;

type PieChartProps = {
  activeIndex?: number;
  onPieEnter?: (_: unknown, index: number) => void;
  onPieLeave?: () => void;
};

export function PieChartComponent({
  activeIndex: externalActiveIndex,
  onPieEnter: externalOnPieEnter,
  onPieLeave: externalOnPieLeave,
}: PieChartProps) {
  const user = useUserStore((state) => state.user);
  const tradesSummary = user?.trades_summary || {
    total_wins: 0,
    total_losses: 0,
  };

  const chartData = getChartData(
    tradesSummary.total_wins,
    tradesSummary.total_losses,
  );
  const [internalActiveIndex, setInternalActiveIndex] = React.useState<
    number | undefined
  >();
  const activeIndex =
    externalActiveIndex !== undefined
      ? externalActiveIndex
      : internalActiveIndex;

  const onPieEnter = (_: unknown, index: number) => {
    if (externalOnPieEnter) {
      externalOnPieEnter(_, index);
    } else {
      setInternalActiveIndex(index);
    }
  };

  const onPieLeave = () => {
    if (externalOnPieLeave) {
      externalOnPieLeave();
    } else {
      setInternalActiveIndex(undefined);
    }
  };

  return (
    <Card className="flex flex-col h-full w-full bg-transparent shadow-none border-0">
      <ChartContainer config={chartConfig} className="mx-auto h-full w-full">
        <PieChart>
          <defs>
            <linearGradient id="primaryGradient" x1="0" y1="1" x2="0" y2="0">
              <stop offset="0%" stopColor="var(--primary)" />
              <stop offset="100%" stopColor="var(--accent)" />
            </linearGradient>
          </defs>
          <Pie
            data={chartData}
            dataKey="value"
            nameKey="name"
            innerRadius={40}
            outerRadius={60}
            strokeWidth={5}
            paddingAngle={2}
            onMouseEnter={onPieEnter}
            onMouseLeave={onPieLeave}
          >
            {chartData.map((_entry, index) => (
              <Cell
                key={`cell-${index}`}
                fill={index === 0 ? "url(#primaryGradient)" : "var(--muted)"}
                stroke={index === 0 ? "var(--primary)" : "var(--border)"}
                strokeWidth={activeIndex === index ? 8 : 2}
                opacity={
                  activeIndex !== undefined && activeIndex !== index ? 0.5 : 1
                }
                style={{
                  filter:
                    activeIndex === index
                      ? "drop-shadow(0px 0px 8px var(--primary))"
                      : "none",
                  transition: "all 0.3s ease",
                }}
              />
            ))}
          </Pie>
        </PieChart>
      </ChartContainer>
    </Card>
  );
}

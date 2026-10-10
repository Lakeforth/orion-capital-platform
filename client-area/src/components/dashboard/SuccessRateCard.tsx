import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PieChartComponent } from "./pie-cahrt";
import { useState } from "react";

export function SuccessRateCard() {
  const [activeIndex, setActiveIndex] = useState<number | undefined>();

  const handlePieEnter = (_: unknown, index: number) => {
    setActiveIndex(index);
  };

  const handlePieLeave = () => {
    setActiveIndex(undefined);
  };

  return (
    <Card className="bg-card text-card-foreground row-span-2">
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle className="text-sm font-medium">Success rate</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col items-center justify-center">
        <PieChartComponent
          activeIndex={activeIndex}
          onPieEnter={handlePieEnter}
          onPieLeave={handlePieLeave}
        />
        <div className="flex items-center justify-center gap-2 mt-1">
          <div
            className="flex items-center gap-2 cursor-pointer transition-opacity duration-300"
            style={{
              opacity:
                activeIndex !== undefined ? (activeIndex === 0 ? 1 : 0.3) : 1,
            }}
            onMouseEnter={() => setActiveIndex(0)}
            onMouseLeave={() => setActiveIndex(undefined)}
          >
            <div
              className="w-3 h-3 rounded-full"
              style={{
                background:
                  "linear-gradient(to bottom, var(--primary), #ced920)",
              }}
            ></div>
            <span className="text-xs">Closed With Profit</span>
          </div>
          <div
            className="flex items-center gap-2 cursor-pointer transition-opacity duration-300"
            style={{
              opacity:
                activeIndex !== undefined ? (activeIndex === 1 ? 1 : 0.3) : 1,
            }}
            onMouseEnter={() => setActiveIndex(1)}
            onMouseLeave={() => setActiveIndex(undefined)}
          >
            <div
              className="w-3 h-3 rounded-full"
              style={{
                background: "linear-gradient(to bottom, #a91d26, #4361A7)",
              }}
            ></div>
            <span className="text-xs">Closed With Loss</span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

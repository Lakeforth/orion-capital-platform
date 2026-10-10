import type { ReactNode } from "react";
import { Card } from "@/components/ui/card";
import { cn } from "@/lib/utils";

interface StatCardProps {
    title: string;
    value: string;
    icon: ReactNode;
    footnote?: string;
    valueClassName?: string;
}

export function StatCard({
                             title,
                             value,
                             icon,
                             footnote,
                             valueClassName = "",
                         }: StatCardProps) {
    return (
        <Card className="p-4 h-full">
            <div className="flex items-center">
                <div className="mr-3 text-muted-foreground/80">
                    {icon}
                </div>
                <div className="flex-1">
                    <h3 className="text-sm font-medium text-muted-foreground">{title}</h3>
                    <div className={cn("text-xl font-bold mt-0.5", valueClassName)}>
                        {value}
                    </div>
                    {footnote && (
                        <p className="text-xs text-muted-foreground mt-0.5">{footnote}</p>
                    )}
                </div>
            </div>
        </Card>
    );
}

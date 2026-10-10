import type { ReactNode } from "react";
import { Card } from "@/components/ui/card";

interface VerificationCardProps {
  title: string;
  confirmed: boolean;
  icon: ReactNode;
}

export function VerificationCard({
  title,
  confirmed,
  icon,
}: VerificationCardProps) {
  return (
    <Card className="flex flex-col items-center justify-center p-6 h-[150px] bg-card border-card-foreground/10">
      <div className="mb-3">{icon}</div>
      <p className="text-center font-medium">
        {title}
        {confirmed && <span className="text-success ml-1">- CONFIRMED</span>}
      </p>
    </Card>
  );
}

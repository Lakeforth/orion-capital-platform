import React from "react";
import { CheckCircle2 } from "lucide-react";
import { useStepNumberColor } from "@/hooks/useStepNumberColor";

interface Step {
  number: number;
  label: string;
}

interface StepProgressBarProps {
  steps: Step[];
  currentStep: number;
}

const StepProgressBar: React.FC<StepProgressBarProps> = ({ steps, currentStep }) => {
  const stepNumberColor = useStepNumberColor();
  const percentage = (currentStep / steps.length) * 100;

  return (
    <div className="space-y-6 my-4">
      {/* Progress Steps Circles */}
      <div className="flex items-center justify-between max-w-2xl px-2">
        {steps.map((step, index) => (
          <div key={step.number} className="flex items-center flex-1">
            <div
              className={`flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full font-bold text-[11px] md:text-sm transition-all duration-300 flex-shrink-0 ${
                step.number < currentStep
                  ? "bg-accent text-slate-900 shadow-sm"
                  : step.number === currentStep
                    ? "bg-primary text-primary-foreground border-2 border-accent ring-2 ring-primary/20"
                    : `bg-muted ${stepNumberColor}`
              }`}
            >
              {step.number < currentStep ? (
                <CheckCircle2 className="w-4 h-4" />
              ) : (
                step.number
              )}
            </div>

            <span
              className={`hidden md:inline-block ml-2 text-[10px] font-black uppercase tracking-tighter whitespace-nowrap ${
                step.number <= currentStep ? "text-accent" : "text-foreground opacity-40"
              }`}
            >
              {step.label}
            </span>

            {index < steps.length - 1 && (
              <div
                className={`flex-1 h-px mx-3 md:mx-4 ${
                  step.number < currentStep ? "bg-accent" : "bg-border"
                }`}
              />
            )}
          </div>
        ))}
      </div>

      {/* Actual Progress Bar */}
      <div className="px-2">
        <div className="relative w-full h-1.5 bg-accent/10 rounded-full overflow-hidden">
          <div 
            className="absolute top-0 left-0 h-full bg-accent transition-all duration-700 ease-in-out shadow-[0_0_15px_rgba(255,119,0,0.4)]"
            style={{ width: `${percentage}%` }}
          />
        </div>
        <div className="flex justify-between mt-2 px-1">
          <span className="text-[9px] font-black text-accent uppercase tracking-widest">Progress</span>
          <span className="text-[9px] font-black text-foreground/60 uppercase tracking-widest">{Math.round(percentage)}%</span>
        </div>
      </div>
      
      {/* Current Step Title - Mobile Only */}
      <div className="md:hidden pt-1 px-2">
        <div className="flex items-center gap-2">
          <span className="text-[10px] font-black font-mono bg-accent/10 text-accent px-1.5 py-0.5 rounded tracking-tighter uppercase">
            Step 0{currentStep}
          </span>
          <h2 className="text-sm font-black text-foreground uppercase tracking-tight">
            {steps.find(s => s.number === currentStep)?.label}
          </h2>
        </div>
      </div>
    </div>
  );
};

export default StepProgressBar;

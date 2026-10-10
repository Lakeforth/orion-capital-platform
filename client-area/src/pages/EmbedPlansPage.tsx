import React, { useEffect, useMemo, useRef } from "react";
import { Check, Sparkles } from "lucide-react";
import { planFeatureIconMap } from "@/constants/planFeatureIcons";
import type { PlanFeatureIconKey } from "@/constants/planFeatureIcons";
import type { PlanFeatureDefinition, Plan } from "@/services/plans/plans-types";
import { usePlansQuery } from "@/services/plans/plans-queries";
import { cn } from "@/lib/utils";

function formatPrice(value: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 0,
  }).format(value);
}

function formatDepositValue(value: string): string {
  const num = Number(value.replace(/[€$,]/g, ""));
  if (isNaN(num)) return value;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 0,
  }).format(num);
}

function getFeatureIcon(def: PlanFeatureDefinition | undefined) {
  const cls = "h-[15px] w-[15px] text-[#4A5568] stroke-[2.5] shrink-0";
  if (!def) return <Sparkles className={cls} />;
  if (def.use_image && def.image_url) {
    return (
      <img src={def.image_url} className="h-[15px] w-[15px] shrink-0 object-contain" />
    );
  }
  const Icon = planFeatureIconMap[def.icon_key as PlanFeatureIconKey] ?? Sparkles;
  return <Icon className={cls} />;
}

function FeatureValue({
  value,
  feature,
  featureIndex,
  plan,
  textSize = "13px",
}: {
  value: string;
  feature: string;
  featureIndex: number;
  plan: Plan;
  textSize?: string;
}) {
  if (featureIndex === 6 || feature.toLowerCase().includes("deposit")) {
    const isGold = plan.title.toLowerCase().includes("gold");
    return (
      <span
        className={cn("font-extrabold", isGold ? "text-[#0F517F]" : "text-[#131722]")}
        style={{ fontSize: textSize }}
      >
        {isNaN(Number(value.replace(/[€$,]/g, ""))) ? value : formatDepositValue(value)}
      </span>
    );
  }
  if (value === "Enabled" || value === "Yes") {
    return (
      <div className="inline-flex items-center justify-center bg-green-500 rounded-full p-0.5 text-white">
        <Check className="h-3 w-3 stroke-[3]" />
      </div>
    );
  }
  const isBold = value === "Available";
  return (
    <span
      className={cn(isBold ? "text-[#0F517F] font-bold" : "text-slate-700 font-medium")}
      style={{ fontSize: textSize }}
    >
      {value || "—"}
    </span>
  );
}

function PlansSkeleton() {
  return (
    <div className="bg-white w-full min-h-screen">
      <div className="p-5 pb-1.5 space-y-2">
        <div className="h-7 w-24 bg-gray-200 animate-pulse rounded" />
        <div className="h-3 w-3/4 bg-gray-100 animate-pulse rounded" />
      </div>
      {/* Mobile skeleton */}
      <div className="flex flex-col gap-4 p-4 md:hidden">
        {[1, 2, 3].map((i) => (
          <div key={i} className="rounded-xl border border-[#C4C6D0] overflow-hidden">
            <div className="h-16 bg-gray-200 animate-pulse" />
            <div className="p-4 space-y-3">
              {[1, 2, 3, 4, 5].map((j) => (
                <div key={j} className="h-4 bg-gray-100 animate-pulse rounded" />
              ))}
            </div>
            <div className="p-4 pt-0">
              <div className="h-9 bg-gray-200 animate-pulse rounded-md" />
            </div>
          </div>
        ))}
      </div>
      {/* Desktop skeleton */}
      <div className="hidden md:block px-6 pt-2 pb-6">
        <div className="border border-[#C4C6D0] rounded-xl overflow-hidden">
          <div className="h-12 bg-gray-200 animate-pulse" />
          <div className="h-10 bg-gray-100 animate-pulse border-t border-[#C4C6D0]" />
          {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
            <div key={i} className="h-9 bg-white animate-pulse border-t border-[#C4C6D0] flex items-center px-3">
              <div className="h-3 w-40 bg-gray-100 rounded animate-pulse" />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function EmbedPlansPage() {
  const { data, isLoading, isError } = usePlansQuery();
  const containerRef = useRef<HTMLDivElement>(null);

  const plans = useMemo(() => data?.data.plans ?? [], [data]);
  const planFeatures = data?.data.plan_features ?? [];
  const planFeatureDefs = data?.data.plan_feature_definitions ?? [];

  const params = new URLSearchParams(window.location.search);
  const redirectUrl = params.get("redirect") || `${window.location.origin}/register`;

  useEffect(() => {
    if (isLoading) return;
    const sendHeight = () => {
      const height = containerRef.current?.scrollHeight ?? document.body.scrollHeight;
      window.parent.postMessage({ type: "plans-embed-height", height }, "*");
    };
    sendHeight();
    const observer = new ResizeObserver(sendHeight);
    if (containerRef.current) observer.observe(containerRef.current);
    return () => observer.disconnect();
  }, [isLoading, plans]);

  const handleCTA = () => {
    try {
      window.top!.location.href = redirectUrl;
    } catch {
      window.location.href = redirectUrl;
    }
  };

  if (isLoading) return <PlansSkeleton />;

  if (isError || plans.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[200px] bg-white">
        <p className="text-xs text-slate-600 font-medium">
          No plans available. Please try again later.
        </p>
      </div>
    );
  }

  return (
    <div ref={containerRef} className="bg-white w-full min-h-screen">
      {/* Header */}
      <div className="p-5 pb-1.5">
        <h2 className="text-2xl font-extrabold text-[#0C1A30] tracking-tight">Plans</h2>
        <p className="text-xs text-slate-500 mt-1">
          Compare our institutional-grade account types designed for diverse trading
          strategies, from retail participants to high-frequency professional desks.
        </p>
      </div>

      {/* Mobile Cards */}
      <div className="flex flex-col gap-4 p-4 md:hidden">
        {plans.map((plan, planIndex) => {
          const isRecommended = plan.title === "Pro";
          const isLastPlan = planIndex === plans.length - 1;

          return (
            <div
              key={`mobile-${plan.id}`}
              className="rounded-xl border overflow-hidden bg-white shadow-sm border-[#C4C6D0]"
            >
              {/* Plan header */}
              <div className="p-4 py-3 border-b border-[#C4C6D0] flex flex-col gap-1 bg-[#DCE9FF80]">
                <div className="flex items-center justify-between">
                  <div className="flex flex-col">
                    {isRecommended && (
                      <span className="bg-[#0F517F] text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-sm mb-1 uppercase w-max tracking-wider">
                        Popular
                      </span>
                    )}
                    <div className="flex items-center gap-1.5">
                      {plan.icon && (
                        <img src={plan.icon} alt="" className="w-4 h-4 object-contain" />
                      )}
                      <h3
                        className="font-extrabold text-lg"
                        style={{ color: plan.color || "gray" }}
                      >
                        {plan.title}
                      </h3>
                    </div>
                  </div>
                  {plan.price > 0 && (
                    <div className="text-right">
                      <div className="text-lg font-bold text-slate-800">
                        {formatPrice(plan.price)}
                      </div>
                      <span className="text-[9px] font-bold text-slate-400 uppercase tracking-tight">
                        Access
                      </span>
                    </div>
                  )}
                </div>
              </div>

              {/* Features */}
              <div className={cn("p-4 pt-3 space-y-3", isLastPlan ? "bg-[#EFF4FF]" : "bg-white")}>
                {planFeatures.map((feature, featureIndex) => {
                  const value =
                    featureIndex < plan.features.length ? plan.features[featureIndex] : "";
                  return (
                    <div
                      key={`mobile-${plan.id}-${feature}`}
                      className="flex justify-between items-center text-xs pb-2 border-b border-[#C4C6D0]/40 last:border-0 last:pb-0"
                    >
                      <span className="text-slate-500 font-medium flex items-center gap-2">
                        {getFeatureIcon(planFeatureDefs[featureIndex])}
                        {feature}
                      </span>
                      <div className="text-right">
                        <FeatureValue
                          value={value}
                          feature={feature}
                          featureIndex={featureIndex}
                          plan={plan}
                          textSize="13px"
                        />
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* CTA */}
              <div className="p-4 pt-0 bg-[#EFF4FF]">
                <button
                  onClick={handleCTA}
                  className="w-full h-9 text-xs font-bold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white shadow-sm rounded-md transition-colors"
                >
                  {plan.title.toLowerCase().includes("diamond") ? "Contact Desk" : "Get Started"}
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Desktop Table */}
      <div className="hidden md:block p-1 px-6 pt-2 pb-6 overflow-x-auto">
        <div className="min-w-[900px] border border-[#C4C6D0] rounded-xl overflow-hidden shadow-sm bg-white">
          <div
            className="grid"
            style={{
              gridTemplateColumns: `repeat(${plans.length + 1}, minmax(0, 1fr))`,
            }}
          >
            {/* Top-left header */}
            <div className="col-span-1 bg-[#DCE9FF80] p-3 flex items-center justify-center font-bold text-[10px] uppercase text-[#4A5568] tracking-wider border-b border-r border-[#C4C6D0]">
              Specifications
            </div>

            {/* Plan name headers */}
            {plans.map((plan, planIndex) => {
              const isRecommended = plan.title === "Pro";
              const isLastPlan = planIndex === plans.length - 1;
              return (
                <div
                  key={`header-${plan.id}`}
                  className={cn(
                    "col-span-1 text-center py-3 px-2 flex flex-col items-center justify-center relative border-b border-[#C4C6D0] bg-[#DCE9FF80]",
                    isLastPlan ? "" : "border-r border-[#C4C6D0]",
                  )}
                >
                  {isRecommended && (
                    <div className="bg-[#0F517F] text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-sm mb-0.5 tracking-wider uppercase">
                      Popular
                    </div>
                  )}
                  <h3
                    className="font-extrabold text-base tracking-tight"
                    style={{ color: plan.color || "#1A202C" }}
                  >
                    {plan.title}
                  </h3>
                </div>
              );
            })}

            {/* CTA row — left label */}
            <div className="col-span-1 py-3 px-3 bg-[#EFF4FF] border-b border-r border-[#C4C6D0] flex flex-col justify-center">
              <span className="text-[9px] text-slate-500 font-medium leading-normal">
                Choose your plan to start trading.
              </span>
            </div>

            {/* CTA row — per-plan buttons */}
            {plans.map((plan, planIndex) => {
              const isLastPlan = planIndex === plans.length - 1;
              return (
                <div
                  key={`cta-${plan.id}`}
                  className={cn(
                    "col-span-1 py-2.5 px-3 flex items-center justify-center border-b border-[#C4C6D0] bg-[#EFF4FF]",
                    isLastPlan ? "" : "border-r border-[#C4C6D0]",
                  )}
                >
                  <button
                    onClick={handleCTA}
                    className="w-full h-7 text-[10px] px-2 font-extrabold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white rounded-sm transition-colors shadow-sm"
                  >
                    {plan.title.toLowerCase().includes("diamond") ? "Contact Desk" : "Get Started"}
                  </button>
                </div>
              );
            })}

            {/* Feature rows */}
            {planFeatures.map((feature, featureIndex) => {
              const isLastRow = featureIndex === planFeatures.length - 1;
              return (
                <React.Fragment key={feature}>
                  <div
                    className={cn(
                      "col-span-1 py-2 px-3 font-semibold text-[11px] text-[#2D3748] flex items-center gap-2.5 bg-[#EFF4FF] border-r border-[#C4C6D0]",
                      isLastRow ? "" : "border-b border-[#C4C6D0]",
                    )}
                  >
                    {getFeatureIcon(planFeatureDefs[featureIndex])}
                    <span>{feature}</span>
                  </div>

                  {plans.map((plan, planIndex) => {
                    const isLastPlan = planIndex === plans.length - 1;
                    const value =
                      featureIndex < plan.features.length ? plan.features[featureIndex] : "";
                    return (
                      <div
                        key={`${feature}-${plan.id}`}
                        className={cn(
                          "col-span-1 py-2 px-2 flex items-center justify-center text-[11px] text-center border-[#C4C6D0]",
                          isLastRow ? "" : "border-b border-[#C4C6D0]",
                          isLastPlan ? "" : "border-r border-[#C4C6D0]",
                          isLastPlan ? "bg-[#EFF4FF]" : "bg-white",
                        )}
                      >
                        <FeatureValue
                          value={value}
                          feature={feature}
                          featureIndex={featureIndex}
                          plan={plan}
                          textSize="12px"
                        />
                      </div>
                    );
                  })}
                </React.Fragment>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

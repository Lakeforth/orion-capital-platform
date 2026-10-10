import React, { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Check, Loader2, Sparkles } from "lucide-react";
import { planFeatureIconMap } from "@/constants/planFeatureIcons";
import type { PlanFeatureIconKey } from "@/constants/planFeatureIcons";
import type { PlanFeatureDefinition } from "@/store/dataStore";
import { cn } from "@/lib/utils";
import useDataStore from "@/store/dataStore";
import useUserStore from "@/store/userStore";
import { useCurrency } from "@/hooks/useCurrency";
import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import { toast } from "@/components/ui/sonner";
import { AxiosError } from "axios";
import { useNavigate } from "react-router-dom";

interface AccountPlansModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

const getFeatureIcon = (
  def: PlanFeatureDefinition | undefined,
) => {
  const iconClass = "h-[15px] w-[15px] text-[#4A5568] stroke-[2.5] shrink-0";
  if (!def) return <Sparkles className={iconClass} />;
  if (def.use_image && def.image_url) {
    return (
      <img
        src={def.image_url}
        className="h-[15px] w-[15px] shrink-0 object-contain"
      />
    );
  }
  const Icon =
    planFeatureIconMap[def.icon_key as PlanFeatureIconKey] ?? Sparkles;
  return <Icon className={iconClass} />;
};

export function AccountPlansModal({
  open,
  onOpenChange,
}: AccountPlansModalProps) {
  const navigate = useNavigate();
  const { data, isLoading } = useDataStore();
  const user = useUserStore((state) => state.user);
  const getCurrentUser = useUserStore((state) => state.getCurrentUser);
  const { formatCurrency } = useCurrency();
  const visiblePlans = data?.plans ?? [];
  const planFeatures = data?.plan_features ?? [];
  const planFeatureDefinitions = data?.plan_feature_definitions ?? [];
  const [subscribingPlanId, setSubscribingPlanId] = useState<string | null>(
    null,
  );
  const [isConfirmOpen, setIsConfirmOpen] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState<{
    id: string;
    title: string;
    priceLabel: string | null;
  } | null>(null);

  // Get current user's plan title if available
  const currentPlanTitle = user?.account_type?.title || "Basic";

  const handleSubscribe = async (planId: string) => {
    setSubscribingPlanId(planId);
    try {
      const response = await axiosInstance.post("/plans/subscribe", {
        plan_id: planId,
      });

      const message = response?.data?.message || "Plan subscribed successfully";
      toast.success(message);

      await getCurrentUser();
      onOpenChange(false);
    } catch (error) {
      const axiosError = error as AxiosError<{ message?: string }>;
      const message =
        axiosError.response?.data?.message ||
        "Failed to subscribe to plan. Please try again.";
      toast.error(message);
    } finally {
      setSubscribingPlanId(null);
    }
  };

  const formatPlanPrice = (price: unknown): string | null => {
    if (price === null || price === undefined || price === "") {
      return null;
    }

    const numericPrice =
      typeof price === "number"
        ? price
        : Number.parseFloat(String(price).replace(/,/g, "").trim());

    if (!Number.isFinite(numericPrice)) {
      return null;
    }

    return formatCurrency(numericPrice);
  };

  const openSubscribeConfirm = (
    planId: string,
    planTitle: string,
    priceLabel: string | null,
  ) => {
    setSelectedPlan({ id: planId, title: planTitle, priceLabel });
    setIsConfirmOpen(true);
  };

  const confirmSubscribe = async () => {
    if (!selectedPlan) return;
    setIsConfirmOpen(false);
    await handleSubscribe(selectedPlan.id);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-5xl p-0 overflow-hidden bg-white border-[#C4C6D0] max-h-[90vh] overflow-y-auto">
        <DialogHeader className="p-5 pb-1.5 text-left">
          <DialogTitle className="text-2xl font-extrabold text-[#0C1A30] tracking-tight">
            Plans
          </DialogTitle>
          <DialogDescription className="text-xs text-slate-500 mt-1">
            Compare our institutional-grade account types designed for diverse
            trading strategies, from retail participants to high-frequency
            professional desks.
          </DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="flex items-center justify-center p-6 bg-white">
            <Loader2 className="h-5 w-5 animate-spin mr-2 text-[#0F517F]" />
            <p className="text-xs text-slate-600 font-medium">
              Loading plans...
            </p>
          </div>
        ) : visiblePlans.length === 0 ? (
          <div className="text-center p-8 bg-white">
            <p className="text-xs text-slate-600 font-medium">
              No plans available. Please try again later.
            </p>
          </div>
        ) : (
          <>
            {/* Mobile View - Cards */}
            <div className="flex flex-col gap-4 p-4 md:hidden">
              {visiblePlans.map((plan, planIndex) => {
                const isRecommended = plan.title === "Pro";
                const isCurrentPlan =
                  user?.account_type?.id === plan.id ||
                  plan.title === currentPlanTitle;
                const priceLabel = formatPlanPrice(plan.price);
                const isSubscribing = subscribingPlanId === plan.id;
                const isLastPlan = planIndex === visiblePlans.length - 1;

                return (
                  <div
                    key={`mobile-${plan.id}`}
                    className={cn(
                      "rounded-xl border overflow-hidden relative bg-white transition-all shadow-sm border-[#C4C6D0]",
                      isCurrentPlan ? "ring-2 ring-primary" : "",
                    )}
                  >
                    {/* Plan Header */}
                    <div
                      className={cn(
                        "p-4 py-3 border-b border-[#C4C6D0] flex flex-col gap-1",
                        "bg-[#DCE9FF80]",
                      )}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex flex-col">
                          {isRecommended && (
                            <span className="bg-[#0F517F] text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-sm mb-1 uppercase w-max tracking-wider">
                              Popular
                            </span>
                          )}
                          <div className="flex items-center gap-1.5">
                            {plan.icon && (
                              <img
                                src={plan.icon}
                                alt=""
                                className="w-4 h-4 object-contain"
                              />
                            )}
                            <h3
                              className="font-extrabold text-lg"
                              style={{ color: plan.color || "gray" }}
                            >
                              {plan.title}
                            </h3>
                          </div>
                        </div>
                        {priceLabel && (
                          <div className="text-right">
                            <div className="text-lg font-bold text-slate-800">
                              {priceLabel}
                            </div>
                            <span className="text-[9px] font-bold text-slate-400 uppercase tracking-tight">
                              Access
                            </span>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Features List */}
                    <div
                      className={cn(
                        "p-4 pt-3 space-y-3",
                        isLastPlan ? "bg-[#EFF4FF]" : "bg-white",
                      )}
                    >
                      {planFeatures.map((feature, featureIndex) => {
                        const value =
                          featureIndex < plan.features.length
                            ? plan.features[featureIndex]
                            : "";

                        let displayValue;
                        if (
                          featureIndex === 6 ||
                          feature.toLowerCase().includes("deposit")
                        ) {
                          const isGold = plan.title
                            .toLowerCase()
                            .includes("gold");
                          if (!isNaN(Number(value.replace(/[€$,]/g, "")))) {
                            displayValue = (
                              <span
                                className={cn(
                                  "font-extrabold text-[13px]",
                                  isGold ? "text-[#0F517F]" : "text-[#131722]",
                                )}
                              >
                                {formatCurrency(
                                  Number(value.replace(/[€$,]/g, "")),
                                )}
                              </span>
                            );
                          } else {
                            displayValue = (
                              <span
                                className={cn(
                                  "font-extrabold text-[13px]",
                                  isGold ? "text-[#0F517F]" : "text-[#131722]",
                                )}
                              >
                                {value}
                              </span>
                            );
                          }
                        } else if (value === "Enabled" || value === "Yes") {
                          displayValue = (
                            <div className="inline-flex items-center justify-center bg-green-500 rounded-full p-0.5 text-white">
                              <Check className="h-3 w-3 stroke-[3]" />
                            </div>
                          );
                        } else {
                          displayValue = (
                            <span className="text-slate-700 font-medium">
                              {value || "—"}
                            </span>
                          );
                        }

                        return (
                          <div
                            key={`mobile-${plan.id}-${feature}`}
                            className="flex justify-between items-center text-xs pb-2 border-b border-[#C4C6D0]/40 last:border-0 last:pb-0"
                          >
                            <span className="text-slate-500 font-medium flex items-center gap-2">
                              {getFeatureIcon(planFeatureDefinitions[featureIndex])}
                              {feature}
                            </span>
                            <div className="text-right">{displayValue}</div>
                          </div>
                        );
                      })}
                    </div>

                    {/* Footer Action */}
                    <div
                      className={cn(
                        "p-4 pt-0",
                        isCurrentPlan ? "bg-[#DCE9FF80]" : "bg-[#EFF4FF]",
                      )}
                    >
                      {isCurrentPlan ? (
                        <Button
                          className="w-full h-9 text-xs font-bold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white shadow-sm"
                          onClick={() => {
                            onOpenChange(false);
                            navigate("/main/chat");
                          }}
                        >
                          Contact Desk
                        </Button>
                      ) : (
                        <Button
                          className="w-full h-9 text-xs font-bold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white shadow-sm"
                          onClick={() => {
                            if (plan.title.toLowerCase().includes("diamond")) {
                              onOpenChange(false);
                              navigate("/main/chat");
                            } else {
                              openSubscribeConfirm(
                                plan.id,
                                plan.title,
                                priceLabel,
                              );
                            }
                          }}
                          disabled={!!subscribingPlanId}
                        >
                          {isSubscribing ? (
                            <>
                              <Loader2 className="h-3 w-3 mr-2 animate-spin" />
                              Subscribing...
                            </>
                          ) : plan.title.toLowerCase().includes("diamond") ? (
                            "Contact Desk"
                          ) : (
                            "Upgrade Plan"
                          )}
                        </Button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Desktop View - Table */}
            <div className="hidden md:block p-1 px-6 pt-2 pb-6 overflow-x-auto">
              <div className="min-w-[900px] border border-[#C4C6D0] rounded-xl overflow-hidden shadow-sm bg-white">
                <div
                  className="grid"
                  style={{
                    gridTemplateColumns: `repeat(${visiblePlans.length + 1}, minmax(0, 1fr))`,
                  }}
                >
                  {/* Specifications Header (Top-Left Cell) */}
                  <div className="col-span-1 bg-[#DCE9FF80] p-3 flex items-center justify-center font-bold text-[10px] uppercase text-[#4A5568] tracking-wider border-b border-r border-[#C4C6D0]">
                    Specifications
                  </div>

                  {/* Plan Headers */}
                  {visiblePlans.map((plan, planIndex) => {
                    const isRecommended = plan.title === "Pro";
                    // const priceLabel = formatPlanPrice(plan.price);
                    const isLastPlan = planIndex === visiblePlans.length - 1;

                    return (
                      <div
                        key={`header-${plan.id}`}
                        className={cn(
                          "col-span-1 text-center py-3 px-2 flex flex-col items-center justify-center relative border-b border-[#C4C6D0]",
                          isLastPlan ? "" : "border-r border-[#C4C6D0]",
                          "bg-[#DCE9FF80]",
                        )}
                      >
                        {isRecommended && (
                          <div className="bg-[#0F517F] text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-sm mb-0.5 tracking-wider uppercase">
                            Popular
                          </div>
                        )}

                        <div className="flex flex-col items-center justify-center gap-0">
                          <h3
                            className="font-extrabold text-base tracking-tight"
                            style={{ color: plan.color || "#1A202C" }}
                          >
                            {plan.title}
                          </h3>
                        </div>
                      </div>
                    );
                  })}

                  {/* Action Row */}
                  <div className="col-span-1 py-3 px-3 bg-[#EFF4FF] border-b border-r border-[#C4C6D0] flex flex-col justify-center">
                    <span className="text-[9px] text-slate-500 font-medium leading-normal">
                      Choose your plan to start trading.
                    </span>
                  </div>

                  {visiblePlans.map((plan, planIndex) => {
                    const isLastPlan = planIndex === visiblePlans.length - 1;
                    const isCurrentPlan =
                      user?.account_type?.id === plan.id ||
                      plan.title === currentPlanTitle;
                    const isSubscribing = subscribingPlanId === plan.id;
                    const priceLabel = formatPlanPrice(plan.price);

                    return (
                      <div
                        key={`subscribe-${plan.id}`}
                        className={cn(
                          "col-span-1 py-2.5 px-3 flex items-center justify-center border-b border-[#C4C6D0]",
                          isLastPlan ? "" : "border-r border-[#C4C6D0]",
                          isCurrentPlan ? "bg-[#DCE9FF80]" : "bg-[#EFF4FF]",
                          "relative",
                        )}
                      >
                        {isCurrentPlan ? (
                          <Button
                            className="w-full h-7 text-[10px] px-2 font-extrabold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white rounded-sm transition-all shadow-sm"
                            onClick={() => {
                              onOpenChange(false);
                              navigate("/main/chat");
                            }}
                          >
                            Contact Desk
                          </Button>
                        ) : (
                          <Button
                            className="w-full h-7 text-[10px] px-2 font-extrabold uppercase bg-[#23BF08] hover:bg-[#1ea307] text-white rounded-sm transition-all shadow-sm"
                            onClick={() => {
                              if (
                                plan.title.toLowerCase().includes("diamond")
                              ) {
                                onOpenChange(false);
                                navigate("/main/chat");
                              } else {
                                openSubscribeConfirm(
                                  plan.id,
                                  plan.title,
                                  priceLabel,
                                );
                              }
                            }}
                            disabled={!!subscribingPlanId}
                          >
                            {isSubscribing ? (
                              <>
                                <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                Subscribing...
                              </>
                            ) : plan.title.toLowerCase().includes("diamond") ? (
                              "Contact Desk"
                            ) : (
                              "Upgrade Now"
                            )}
                          </Button>
                        )}
                      </div>
                    );
                  })}

                  {/* Plan Features */}
                  {planFeatures.map((feature, featureIndex) => {
                    const isLastRow = featureIndex === planFeatures.length - 1;
                    return (
                      <React.Fragment key={feature}>
                        {/* Feature Name Cell (Left Column) */}
                        <div
                          className={cn(
                            "col-span-1 py-2 px-3 font-semibold text-[11px] text-[#2D3748] flex items-center gap-2.5 bg-[#EFF4FF] border-r border-[#C4C6D0]",
                            isLastRow ? "" : "border-b border-[#C4C6D0]",
                          )}
                        >
                          {getFeatureIcon(planFeatureDefinitions[featureIndex])}
                          <span>{feature}</span>
                        </div>

                        {/* Feature Values for each Plan */}
                        {visiblePlans.map((plan, planIndex) => {
                          const isLastPlan =
                            planIndex === visiblePlans.length - 1;
                          const value =
                            featureIndex < plan.features.length
                              ? plan.features[featureIndex]
                              : "";

                          let displayValue;

                          if (
                            featureIndex === 6 ||
                            feature.toLowerCase().includes("deposit")
                          ) {
                            const isGold = plan.title
                              .toLowerCase()
                              .includes("gold");
                            if (!isNaN(Number(value.replace(/[€$,]/g, "")))) {
                              const numVal = Number(
                                value.replace(/[€$,]/g, ""),
                              );
                              displayValue = (
                                <span
                                  className={cn(
                                    "font-extrabold text-[12px]",
                                    isGold
                                      ? "text-[#0F517F]"
                                      : "text-[#131722]",
                                  )}
                                >
                                  {formatCurrency(numVal)}
                                </span>
                              );
                            } else {
                              displayValue = (
                                <span
                                  className={cn(
                                    "font-extrabold text-[12px]",
                                    isGold
                                      ? "text-[#0F517F]"
                                      : "text-[#131722]",
                                  )}
                                >
                                  {value}
                                </span>
                              );
                            }
                          } else if (value === "Enabled" || value === "Yes") {
                            displayValue = (
                              <div className="inline-flex items-center justify-center bg-green-500 rounded-full p-0.5 text-white">
                                <Check className="h-3 w-3 stroke-[3]" />
                              </div>
                            );
                          } else {
                            const isBold = value === "Available";
                            displayValue = (
                              <span
                                className={cn(
                                  "text-[11px]",
                                  isBold
                                    ? "text-[#0F517F] font-bold"
                                    : "text-slate-700 font-medium",
                                )}
                              >
                                {value || "—"}
                              </span>
                            );
                          }

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
                              {displayValue}
                            </div>
                          );
                        })}
                      </React.Fragment>
                    );
                  })}

                </div>
              </div>
            </div>
          </>
        )}
      </DialogContent>

      <Dialog open={isConfirmOpen} onOpenChange={setIsConfirmOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Confirm Plan Subscription</DialogTitle>
            <DialogDescription>
              {selectedPlan
                ? `You are about to subscribe to the ${selectedPlan.title} plan${selectedPlan.priceLabel ? ` (${selectedPlan.priceLabel})` : ""}. Do you want to continue?`
                : "Do you want to continue?"}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setIsConfirmOpen(false)}
              disabled={!!subscribingPlanId}
            >
              Cancel
            </Button>
            <Button
              onClick={confirmSubscribe}
              disabled={
                !selectedPlan ||
                (subscribingPlanId !== null &&
                  subscribingPlanId === selectedPlan.id)
              }
            >
              {selectedPlan && subscribingPlanId === selectedPlan.id ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Subscribing...
                </>
              ) : (
                "Confirm Subscription"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Dialog>
  );
}

export default AccountPlansModal;

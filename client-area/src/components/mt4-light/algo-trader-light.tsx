"use client";

import { useState } from "react";
import { ChevronDown, ChevronRight, Play, Settings } from "lucide-react";
import { Switch } from "@/components/ui/switch";
import { Card, CardContent } from "@/components/ui/card";
import useOverlayStore from "@/store/overlayStore";
import useDataStore, { ExpertAdvisor } from "@/store/dataStore";
import useUserStore from "@/store/userStore";
import useDarkModeStore from "@/store/darkModeStore";
import { toast } from "sonner";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import useSiteSettingsStore from "@/store/siteSettingStore";
import axiosInstance from "@/lib/axios";
import type { AxiosError } from "axios";

type SectionState = {
  account: boolean;
  expertAdvisors: boolean;
  advisors: boolean;
};

export default function AlgoTraderLight() {
  const [expandedSections, setExpandedSections] = useState<SectionState>({
    account: true,
    expertAdvisors: true,
    advisors: true,
  });
  const { setAutomatedTrading } = useOverlayStore();
  const { data, activeEA, activateEA } = useDataStore();
  const user = useUserStore((state) => state.user);
  const setUser = useUserStore((state) => state.setUser);
  const token = useUserStore((state) => state.token);
  const settings = useSiteSettingsStore((state) => state.settings);
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  const handleAiTradeToggle = async () => {
    try {
      const response = await axiosInstance.post("/user/ai-trade/toggle");

      if (
        response.data?.status === "success" &&
        response.data?.data &&
        user &&
        token
      ) {
        const updatedUser = {
          ...user,
          ai_trade: response.data.data.ai_trade,
        };
        setUser(updatedUser, token);
        toast.success(
          `AI Trading ${response.data.data.ai_trade ? "enabled" : "disabled"} successfully`,
        );
      }
    } catch (err: unknown) {
      const axiosErr = err as AxiosError<{ message: string }>;
      const errorMessage =
        axiosErr?.response?.data?.message || "Failed to toggle AI Trading";
      toast.error(errorMessage);
    }
  };

  const toggleSection = (section: keyof SectionState) => {
    setExpandedSections((prev) => ({
      ...prev,
      [section]: !prev[section],
    }));
  };

  const handleAdvisorClick = (advisor: ExpertAdvisor) => {
    if (!user?.eas?.includes(advisor.id)) {
      toast.error(
        "This Expert Advisor is not available for your plan. Please upgrade to access it.",
      );
      return;
    }

    if (activeEA?.id === advisor.id) {
      setAutomatedTrading(true, advisor.id);
    } else {
      toast.info("Please activate the EA before starting automated trading.");
    }
  };

  const handleActivateEA = (ea: ExpertAdvisor) => {
    activateEA(ea);
    toast.success(`${ea.name} has been activated.`);
  };

  return (
    <div className="h-full flex flex-col">
      {/* Algo Trader Header */}
      <div
        className={`p-2 ${
          isDarkMode ? "bg-slate-800" : "bg-[#D2E0EA]"
        } sticky top-0 z-10`}
      >
        <div className="flex items-center justify-between">
          <span
            className={`text-xs font-bold ${
              isDarkMode ? "text-slate-200" : "text-slate-900"
            }`}
          >
            Artificail Intelligence (AI) Trading
          </span>
          <div className="flex items-center gap-2">
            <Switch
              checked={Boolean(user?.ai_trade)}
              onCheckedChange={handleAiTradeToggle}
            />
          </div>
        </div>
      </div>

      {/* Navigation Tree */}
      <div
        className={`flex-1 min-h-0 p-0 ${
          isDarkMode ? "bg-slate-900" : "bg-white"
        }`}
      >
        <Card
          className={`h-full ${
            isDarkMode
              ? "bg-slate-900 border-slate-700"
              : "bg-white border-gray-100"
          } p-1 rounded-none shadow-none`}
        >
          <CardContent className="p-1 h-full">
            <div className="h-full overflow-auto space-y-1">
              {!settings?.expert_advisor && (
                <div className="flex items-center justify-center h-full">
                  <p
                    className={`text-sm ${
                      isDarkMode ? "text-slate-400" : "text-slate-500"
                    } text-center px-4`}
                  >
                    Expert Advisors are currently disabled. Please contact
                    support for more information.
                  </p>
                </div>
              )}
              {/* FTMO MT4 */}
              {/* <div className="flex items-center gap-2 py-1 px-2 text-xs">
                <img
                  src={settings?.logo_sm || settings?.logo}
                  alt="Logo"
                  className=" w-auto h-8 rounded"
                />
              </div> */}

              {/* Advisors Section */}
              {settings?.expert_advisor && (
                <div>
                  <button
                    onClick={() => toggleSection("advisors")}
                    className={`flex items-center gap-1 py-1 px-2 text-xs w-full ${
                      isDarkMode ? "hover:bg-slate-800" : "hover:bg-gray-50"
                    } rounded`}
                  >
                    {expandedSections.advisors ? (
                      <ChevronDown
                        className={`w-3 h-3 ${
                          isDarkMode ? "text-slate-200" : "text-slate-900"
                        }`}
                      />
                    ) : (
                      <ChevronRight
                        className={`w-3 h-3 ${
                          isDarkMode ? "text-slate-200" : "text-slate-900"
                        }`}
                      />
                    )}
                    <div className="w-4 h-4 bg-blue-500 rounded-sm flex items-center justify-center">
                      <span className="text-xs text-white">EA</span>
                    </div>
                    <span
                      className={`${
                        isDarkMode ? "text-slate-200" : "text-slate-900"
                      } font-semibold`}
                    >
                      Expert Advisors
                    </span>
                  </button>

                  {expandedSections.advisors && data?.expert_advisors && (
                    <div className="ml-6 space-y-1">
                      {data.expert_advisors
                        .filter((advisor) => user?.eas?.includes(advisor.id))
                        .map((advisor) => (
                          <div
                            key={advisor.id}
                            className={`flex items-center w-full justify-between gap-2 py-1 px-2 text-xs ${
                              isDarkMode
                                ? "hover:bg-slate-800 text-slate-200"
                                : "hover:bg-gray-50 text-slate-900"
                            } rounded`}
                          >
                            <button
                              onClick={() => handleAdvisorClick(advisor)}
                              className="flex items-center gap-2"
                            >
                              <Play
                                className={`w-3 h-3 font-semibold ${
                                  isDarkMode
                                    ? "text-slate-200"
                                    : "text-slate-900"
                                }`}
                              />
                              <span
                                className={`font-semibold ${
                                  isDarkMode
                                    ? "text-slate-200"
                                    : "text-slate-900"
                                }`}
                              >
                                {advisor.name}
                              </span>
                            </button>
                            {activeEA?.id === advisor.id ? (
                              <Badge
                                variant={isDarkMode ? "outline" : "secondary"}
                              >
                                Activated
                              </Badge>
                            ) : (
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <button>
                                    <Settings
                                      className={`w-4 h-4 ${
                                        isDarkMode
                                          ? "text-slate-200"
                                          : "text-slate-900"
                                      }`}
                                    />
                                  </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                  className={
                                    isDarkMode ? "bg-slate-800" : "bg-white"
                                  }
                                >
                                  <DropdownMenuItem
                                    onClick={() => handleActivateEA(advisor)}
                                    className={
                                      isDarkMode
                                        ? "text-slate-200"
                                        : "text-slate-900"
                                    }
                                  >
                                    Activate
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            )}
                          </div>
                        ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

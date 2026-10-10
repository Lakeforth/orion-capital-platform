import { useCallback, useState } from "react";
import type { AxiosError } from "axios";
import { toast } from "sonner";
import axiosInstance from "@/lib/axios";
import useSiteSettingsStore from "@/store/siteSettingStore";
import useUserStore from "@/store/userStore";

/**
 * The client's AI Trader switch: `available` when the tenant has AI trading
 * enabled, `active` when this client has it on. Same endpoint and messages as
 * the standard trade room's header toggle.
 */
export function useAiTradeToggle() {
  const available = useSiteSettingsStore((state) => state.settings?.enabled_ai_trade === true);
  const active = useUserStore((state) => Boolean(state.user?.ai_trade));
  const [pending, setPending] = useState(false);

  const toggle = useCallback(async () => {
    setPending(true);
    try {
      const response = await axiosInstance.post("/user/ai-trade/toggle");
      const { user, token, setUser } = useUserStore.getState();
      if (response.data?.status === "success" && response.data?.data && user && token) {
        setUser({ ...user, ai_trade: response.data.data.ai_trade }, token);
        toast.success(`AI Trading ${response.data.data.ai_trade ? "enabled" : "disabled"} successfully`);
      }
    } catch (err: unknown) {
      const axiosErr = err as AxiosError<{ message: string }>;
      toast.error(axiosErr?.response?.data?.message || "Failed to toggle AI Trading");
    } finally {
      setPending(false);
    }
  }, []);

  return { available, active, pending, toggle };
}

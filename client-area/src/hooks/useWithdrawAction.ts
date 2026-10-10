/**
 * The one place that answers "what should Withdraw do from here".
 *
 * It moved out of the header when the button moved to the trade room's
 * sidebar, and it is a hook rather than a copied handler because the answer
 * depends on three things that are easy to get subtly wrong: which origin the
 * terminal is running on, whether this session arrived from a client area, and
 * whether the client area is where withdrawal is supposed to happen at all.
 *
 * Whoever calls this must render <WithdrawFunds> with the returned dialog
 * state, or the fallback path leads nowhere.
 */
import { useState } from "react";
import { useNavigate } from "react-router-dom";

import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { isWebtraderHost } from "@/lib/webtrader-host";
import { returnToClientArea } from "@/lib/trade-room-link";
import {
  CLIENT_AREA_PATHS,
  WITHDRAW_OPENS_CLIENT_AREA,
  canOpenClientArea,
} from "@/lib/webtrader-profile-mode";

export function useWithdrawAction() {
  const navigate = useNavigate();
  const [isDialogOpen, setDialogOpen] = useState(false);

  const onWebtrader = isWebtraderHost();
  const clientAreaUrl = useSiteSettingsStore((state) => state.settings?.client_area_url);
  // Only true when this session was launched from the client area's Trade
  // Room -- a direct login on the webtrader form has no client area to be
  // sent back to.
  const viaClientAreaLaunch = useUserStore((state) => state.viaClientAreaLaunch);
  const hasClientArea = onWebtrader && viaClientAreaLaunch && !!clientAreaUrl;

  const withdraw = () => {
    if (!onWebtrader) {
      navigate("/main/withdrawal");
      return;
    }

    if (WITHDRAW_OPENS_CLIENT_AREA && canOpenClientArea(hasClientArea)) {
      returnToClientArea(CLIENT_AREA_PATHS.withdrawal);
      return;
    }

    // Nowhere to send them: the standalone terminal mounts no client-area
    // routes, which is the whole reason the dialog exists.
    setDialogOpen(true);
  };

  return { withdraw, isDialogOpen, setDialogOpen };
}

import React from "react";
import { BotIcon as Robot } from "lucide-react";
import { useLocation } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  DialogClose,
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";

interface Props {
  isMini?: boolean;
}

const LOCKED_MESSAGE =
  "Auto trader is currently enabled on your account please contact support....";

const AutoTraderModal: React.FC<Props> = ({ isMini = false }) => {
  const location = useLocation();
  const showRobot = !location.pathname.startsWith("/main");
  const user = useUserStore((state) => state.user);
  const enableAutotrader = useSiteSettingsStore(
    (state) => state.settings?.enable_autotrader === true
  );

  const isAutotraderEnabled = enableAutotrader && Boolean(user?.autotrader);

  // Requirement: enable_autotrader must be true before checking user flags.
  if (!enableAutotrader) return null;
  if (!isAutotraderEnabled) return null;

  if (isMini) {
    return (
      <Dialog>
        <DialogTrigger asChild>
          <Button
            variant="outline"
            className={`flex-1 h-full border-green-500 text-green-500 hover:bg-green-500/15 w-full ${
              showRobot ? "justify-start" : "justify-center"
            }`}
          >
            <span className="flex flex-col items-center gap-2 max-w-full">
              <Robot className="h-6 w-6" />
              <span className="text-[10px] truncate font-semibold">Auto Trader</span>
            </span>
          </Button>
        </DialogTrigger>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Auto Trader Enabled</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">{LOCKED_MESSAGE}</p>
          <DialogFooter>
            <DialogClose asChild>
              <Button type="button">Close</Button>
            </DialogClose>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    );
  }

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button
          variant="outline"
          className={`flex-1 h-full gap-3 text-green-500 border-green-500 hover:bg-green-500/15 w-full ${
            showRobot ? "justify-start" : "justify-center"
          }`}
        >
          <span className="flex items-center gap-2 max-w-full">
            <Robot className="h-5 w-5 flex-shrink-0" />
            <span className="truncate font-semibold">Auto Trader</span>
          </span>
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Auto Trader Enabled</DialogTitle>
        </DialogHeader>
        <p className="text-sm text-muted-foreground">{LOCKED_MESSAGE}</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default AutoTraderModal;

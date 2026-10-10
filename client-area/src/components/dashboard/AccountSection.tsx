import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { useCurrency } from "@/hooks/useCurrency";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { openWebtrader } from "@/lib/trade-room-link";

export function AccountSection() {
  const user = useUserStore((state) => state.user);
  const enablePlan = useSiteSettingsStore((state) => state.settings?.enable_plan === true);
  const { formatCurrency } = useCurrency();
  const balance = user?.balance || 0;  // Fallback to original value if no user

  return (
    <div className="space-y-4 rounded-xl mt-5 ">
      <div className="flex items-center gap-2"></div>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 ">
        <Card className="space-y-4 p-4 border-secondary shadow-lg shadow-primary w-[250px] flex gap-2">

          <div className="flex justify-between items-center">
            <h2 className="text-xl font-semibold">Account</h2>
            {enablePlan && (
              <img
                  src={
                      user?.account_type?.image ||
                      "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744370355/main_plate_exi8jv.png"
                  }
                  alt={`${user?.account_type?.title || "Basic"} Badge`}
                  className="w-8 h-8"
              />
            )}

          </div>

          <div className="flex justify-between items-center">
            <span className="text-muted-foreground">Balance</span>
            <span className="text-success font-medium">
              {formatCurrency(balance)}
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-muted-foreground">Leverage</span>
            <span className="text-primary font-medium">1:{ user?.account_type?.leverage }</span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-muted-foreground">{ user?.custom_wallet }</span>
            <span className="text-primary font-medium">
              {formatCurrency(user?.credit_balance ?? 0)}
            </span>
          </div>
          <div className="flex items-center justify-center">
            <Button className="w-fit" onClick={() => openWebtrader()}>
              Trade Now
            </Button>
          </div>
        </Card>
      </div>
    </div>
  );
}

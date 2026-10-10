// The client's own settings, as one tabbed page.
//
// This replaces the dialogs the web trader used to open over the terminal:
// Profile and KYC are pages here now, reached from the avatar menu, opened
// full width rather than in a modal.
//
// Account Settings is built; KYC, Security and Withdrawal are placeholders
// until their designs land. The tab lives in the URL (?tab=), so a link can
// point straight at one -- which is how the web trader will reach Profile.

import { useSearchParams } from "react-router-dom";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AccountSettingsTab from "./settings/AccountSettingsTab";
import KycTab from "./settings/KycTab";
import SecurityTab from "./settings/SecurityTab";
import WithdrawalTab from "./settings/WithdrawalTab";

const TABS = [
  { value: "account", label: "Account Settings" },
  { value: "kyc", label: "KYC" },
  { value: "security", label: "Security" },
  { value: "withdrawal", label: "Withdrawal & Wallets" },
] as const;

const VALID = TABS.map((tab) => tab.value) as readonly string[];

export default function SettingsPage() {
  const [params, setParams] = useSearchParams();
  const requested = params.get("tab") ?? "";
  const tab = VALID.includes(requested) ? requested : "account";

  const select = (value: string) => {
    const next = new URLSearchParams(params);
    next.set("tab", value);
    // replace, not push: flicking between tabs should not fill the back button.
    setParams(next, { replace: true });
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6">
      <Tabs value={tab} onValueChange={select}>
        <TabsList className="h-10 w-full justify-start gap-1 overflow-x-auto">
          {TABS.map((item) => (
            <TabsTrigger
              key={item.value}
              value={item.value}
              className="flex-none px-4 data-[state=active]:bg-[#FF7700] data-[state=active]:text-white dark:data-[state=active]:bg-[#FF7700] dark:data-[state=active]:text-white dark:data-[state=active]:border-transparent"
            >
              {item.label}
            </TabsTrigger>
          ))}
        </TabsList>

        <TabsContent value="account" className="mt-5">
          <AccountSettingsTab />
        </TabsContent>
        <TabsContent value="kyc" className="mt-5">
          <KycTab />
        </TabsContent>
        <TabsContent value="security" className="mt-5">
          <SecurityTab />
        </TabsContent>
        <TabsContent value="withdrawal" className="mt-5">
          <WithdrawalTab />
        </TabsContent>
      </Tabs>
    </div>
  );
}

import { Link } from "react-router-dom";
import { AccountsTable } from "@/components/account/accounts-table";
import useUserStore from "@/store/userStore";

export default function AccountsPage() {
  const user = useUserStore((state) => state.user);
  const accounts = user?.accounts || [];

  return (
    <div className="flex flex-col gap-8 p-6 bg-background text-foreground min-h-screen">
      <h1 className="text-2xl font-bold text-center">ACCOUNTS</h1>

      {/* The old TransferForm's only real function was converting the
          legacy Credit Wallet into withdrawable balance -- the backend now
          rejects that outright, unconditionally (Credit is a managed line
          of credit that can only ever fund a trading account directly, see
          UserController::convert()), so the form would just 422 on every
          submission. Point to the real fund-movement flow instead: the
          Trading Accounts page's per-account "Move Funds" dialog. */}
      <div className="rounded-xl border border-border bg-muted/40 p-6 text-center space-y-2">
        <p className="text-sm text-muted-foreground">
          Moving funds between wallets now happens from your Trading
          Accounts page.
        </p>
        <Link
          to="/main/trading-accounts"
          className="inline-block text-sm font-semibold text-[#FF7700] hover:underline"
        >
          Go to Trading Accounts →
        </Link>
      </div>

      <div className="space-y-4">
        <h2 className="text-lg font-medium">Your Accounts</h2>
        <AccountsTable accounts={accounts} />
      </div>
    </div>
  );
}

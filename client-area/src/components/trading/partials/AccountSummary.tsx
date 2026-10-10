import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { ChevronUp, Info, Wallet } from "lucide-react";
import { cn } from "@/lib/utils";
import { useCurrency } from "@/hooks/useCurrency.ts";
import AssetInfoModal from "@/components/trading/trading-interface-components/panels/inc/AssetInfoModal";
import { Asset } from "@/store/assetStore";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { amountTone } from "@/lib/amount-tone";

export interface AccountSummary {
  balance: number;
  credit: number;
  equity: number;
  margin: number;
  marginLevel: string;
  freeMargin: number;
  pnl: number;
  lifetimePnl: number;
}

interface AccountSummaryProps {
  accountData: AccountSummary;
  asset: Asset; // Add asset prop
  isDesktop: boolean;
  isCollapsed?: boolean;
  setIsCollapsed?: (collapsed: boolean) => void;
  children?: React.ReactNode;
  activeActions?: React.ReactNode;
}

export function AccountSummary({
  accountData,
  asset,
  isDesktop,
  isCollapsed = false,
  setIsCollapsed = () => {},
  children,
  activeActions,
}: AccountSummaryProps) {
  const { formatCurrency } = useCurrency();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [showAccountDetails, setShowAccountDetails] = useState(false);

  if (isDesktop) {
    return (
      <div className="w-full bg-muted/30 border-t border-muted p-2">
        <div className="flex flex-wrap gap-4 justify-between">
          <div className="flex gap-4">
            <div className="flex items-center gap-2">
              <span className="text-muted-foreground text-xs">Balance:</span>
              <span className="text-xs">
                {formatCurrency(accountData.balance)}
              </span>
            </div>

            {/*<div className="flex items-center gap-2">*/}
            {/*  <span className="text-muted-foreground text-xs">Credit:</span>*/}
            {/*  <span className="text-xs">*/}
            {/*    {formatCurrency(accountData.credit)}*/}
            {/*  </span>*/}
            {/*</div>*/}

            <div className="flex items-center gap-2">
              <span className="text-muted-foreground text-xs">Equity:</span>
              <span className="text-xs">
                {formatCurrency(accountData.equity)}
              </span>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-muted-foreground text-xs">Margin:</span>
              <span className="text-xs">
                {formatCurrency(accountData.margin)}
              </span>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-muted-foreground text-xs">
                Margin level:
              </span>
              <span className="text-xs">{accountData.marginLevel}</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-muted-foreground text-xs">
                Free Margin:
              </span>
              <span className="text-xs">
                {formatCurrency(accountData.freeMargin)}
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {activeActions}
            <span className="text-muted-foreground text-xs">PnL:</span>
            <span
              className={cn(
                "text-xs",
                  amountTone(accountData.pnl)
              )}
            >
              {accountData.pnl >= 0 ? "" : "-"}
              {formatCurrency(Math.abs(accountData.pnl))}
            </span>
          </div>

          {isCollapsed && (
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6"
              onClick={() => setIsCollapsed(false)}
            >
              <ChevronUp className="h-4 w-4" />
            </Button>
          )}
        </div>
      </div>
    );
  }

  // Mobile layout
  return (
    <div className="p-2 bg-muted/30 border-t border-muted">
      <div className="flex justify-between items-center">
        <div className="flex items-center gap-2">
          <div className="flex items-center gap-1">
            <span className="text-xs text-muted-foreground">Bal:</span>
            <span className="text-xs font-medium">
              {formatCurrency(parseFloat(accountData.balance.toFixed(2)))}
            </span>
          </div>
          <div className="flex items-center gap-1">
            <span className="text-xs text-muted-foreground">PnL:</span>
            <span
              className={cn(
                "text-xs font-medium",
                amountTone(accountData.pnl)
              )}
            >
              {accountData.pnl >= 0 ? "" : "-"}
              {formatCurrency(Math.abs(accountData.pnl))}
            </span>
          </div>
        </div>

        <div className="flex items-center gap-4 pr-2">
          {children}
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6 rounded-full"
            onClick={() => setShowAccountDetails(true)}
          >
            <Wallet className="h-3.5 w-3.5" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6 rounded-full"
            onClick={() => setIsModalOpen(true)}
          >
            <Info className="h-3.5 w-3.5" />
          </Button>

          {/* Asset Info Modal */}
          <AssetInfoModal
            asset={asset}
            open={isModalOpen}
            onOpenChange={setIsModalOpen}
            onTradeClick={() => {}}
          />

          {/* Account Details Dialog */}
          <Dialog
            open={showAccountDetails}
            onOpenChange={setShowAccountDetails}
          >
            <DialogContent className="sm:max-w-md">
              <div className="space-y-4 p-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm">Balance</span>
                  <span className="font-medium">
                    {formatCurrency(accountData.balance)}
                  </span>
                </div>
                {/*<div className="flex justify-between items-center">*/}
                {/*  <span className="text-sm">Credit</span>*/}
                {/*  <span className="font-medium">*/}
                {/*    {formatCurrency(accountData.credit)}*/}
                {/*  </span>*/}
                {/*</div>*/}
                <div className="flex justify-between items-center">
                  <span className="text-sm">Equity</span>
                  <span className="font-medium">
                    {formatCurrency(accountData.equity)}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Margin</span>
                  <span className="font-medium">
                    {formatCurrency(accountData.margin)}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Margin Level</span>
                  <span className="font-medium">{accountData.marginLevel}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">Free Margin</span>
                  <span className="font-medium">
                    {formatCurrency(accountData.freeMargin)}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm">PnL</span>
                  <span
                    className={cn(
                      "font-medium",
                      amountTone(accountData.pnl)
                    )}
                  >
                    {accountData.pnl >= 0 ? "" : "-"}
                    {formatCurrency(Math.abs(accountData.pnl))}
                  </span>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>
    </div>
  );
}

export default AccountSummary;

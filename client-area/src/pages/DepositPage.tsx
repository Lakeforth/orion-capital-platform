import { useState, useEffect, useMemo } from "react";
import { Card, CardContent } from "@/components/ui/card";
import useDataStore from "@/store/dataStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { Cloud, CreditCard, Link, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import CardDeposit from "./deposit/CardDeposit";
import QRCodeDeposit from "@/pages/deposit/qr-code-deposit.tsx";
import axiosInstance from "@/lib/axios.ts";
import { toast } from "@/components/ui/sonner.tsx";
import { AxiosError } from "axios";

// Custom PaymentMethod type
interface PaymentMethod {
  id: string;
  name: string;
  icon: React.ReactNode;
  processingTime: string;
}

function DirectPaymentMethods({
  paymentMethods,
  selectedMethodId,
  onSelect,
}: {
  paymentMethods: PaymentMethod[];
  selectedMethodId: string | null;
  onSelect: (methodId: string) => void;
}) {
  return (
    <div className="relative">
      <h3 className="hidden md:block text-sm uppercase tracking-wider text-muted-foreground mb-2 px-2">
        METHODS
      </h3>

      <div className="hidden md:block space-y-1">
        {paymentMethods.map((method) => (
          <Button
            key={method.id}
            variant="ghost"
            className={`flex items-center gap-3 py-3 px-4 w-full justify-start h-auto border-l-4 ${
              selectedMethodId === method.id
                ? "border-accent bg-secondary/40"
                : "border-transparent hover:bg-secondary/30"
            }`}
            onClick={() => onSelect(method.id)}
          >
            <span className="text-muted-foreground w-7">{method.icon}</span>
            <div className="flex-1 text-left">
              <p className="text-sm font-medium">{method.name}</p>
              <p className="text-xs text-muted-foreground">
                {method.processingTime}
              </p>
            </div>
          </Button>
        ))}
      </div>
    </div>
  );
}

export default function DepositPage({
  onDepositSuccess,
}: {
  onDepositSuccess?: () => void;
}) {
  const { data, fetchData, isLoading } = useDataStore();
  const { settings } = useSiteSettingsStore();
  const [selectedMethodId, setSelectedMethodId] = useState<string | null>(null);

  // Static payment methods - conditionally include credit card based on settings
  const staticPaymentMethods: PaymentMethod[] = useMemo(() => {
    const methods: PaymentMethod[] = [];

    if (settings?.credit_card_deposit) {
      methods.push({
        id: "credit-card",
        name: "Credit/Debit Card",
        icon: <CreditCard className="h-5 w-5 opacity-70" />,
        processingTime: "5-10 minutes",
      });
    }

    return methods;
  }, [settings?.credit_card_deposit]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleSubmit = async (amount: string) => {
    try {
      await axiosInstance.post(
        "/user/deposit/store",
        {
          amount,
          method: "crypto",
          wallet_id: selectedWallet?.id,
        },
        {
          headers: {
            Accept: "application/json",
          },
        }
      );

      toast.success("Deposit request submitted successfully");
      onDepositSuccess?.(); // Call the success callback
    } catch (error) {
      const axiosError = error as AxiosError<{ message: string }>;
      toast.error(
        axiosError.response?.data?.message || "Failed to submit deposit request"
      );
      throw error;
    }
  };

  const cryptoPaymentMethods: PaymentMethod[] = useMemo(() => {
    if (!data?.wallets) return [];

    return data.wallets.map((wallet) => {
      const isLinkWallet = wallet.type?.toLowerCase() === "link";
      return {
        id: wallet.id,
        name: isLinkWallet
          ? wallet.crypto
          : `${wallet.crypto} Wallet (${wallet.address.substring(0, 8)}...)`,
        icon: isLinkWallet ? (
          <Link className="h-5 w-5 opacity-70" />
        ) : (
          <Cloud className="h-5 w-5 opacity-70" />
        ),
        processingTime: "5-10 minutes",
      };
    });
  }, [data?.wallets]);

  const paymentMethods = useMemo(() => {
    return [...cryptoPaymentMethods, ...staticPaymentMethods];
  }, [cryptoPaymentMethods, staticPaymentMethods]);

  // Auto-select first method if none selected and data is loaded
  useEffect(() => {
    if (!selectedMethodId && paymentMethods.length > 0 && data?.wallets) {
      setSelectedMethodId(paymentMethods[0].id);
    }
  }, [paymentMethods, selectedMethodId, data]);

  // Get selected wallet
  const selectedWallet = useMemo(() => {
    if (!selectedMethodId || !data?.wallets) return null;
    return data.wallets.find((w) => w.id === selectedMethodId);
  }, [selectedMethodId, data?.wallets]);
  const isLinkWallet = selectedWallet?.type?.toLowerCase() === "link";

  return (
    <div className="animate-fade-in pt-3">
      <h1 className="text-2xl font-semibold tracking-tight mb-4">DEPOSIT</h1>

      {/* Mobile payment methods - horizontal scrollable */}
      <div className="block md:hidden mb-4">
        <div className="relative">
          <div className="overflow-x-auto flex no-scrollbar">
            <div className="flex gap-2 px-2 pb-2 whitespace-nowrap">
              {paymentMethods.map((method) => (
                <button
                  key={method.id}
                  onClick={() => setSelectedMethodId(method.id)}
                  className={`flex items-center gap-2 shrink-0 rounded-lg px-4 py-2 text-sm transition-colors ${
                    selectedMethodId === method.id
                      ? "bg-secondary text-foreground"
                      : "bg-background text-muted-foreground hover:bg-secondary/50"
                  }`}
                >
                  {method.icon}
                  <span>{method.name}</span>
                </button>
              ))}
            </div>
          </div>
          {/* Gradient overlay to indicate scrollable content */}
          <div className="absolute right-0 top-0 h-full w-12 bg-gradient-to-l from-background to-transparent pointer-events-none" />
        </div>
      </div>

      <div className="flex md:gap-6">
        {/* Left sidebar with payment methods - desktop only */}
        {data?.wallets ? (
          <div className="hidden md:block md:w-64 shrink-0">
            <DirectPaymentMethods
              paymentMethods={paymentMethods}
              selectedMethodId={selectedMethodId}
              onSelect={setSelectedMethodId}
            />
          </div>
        ) : null}

        {/* Right content area */}
        <Card className="items-start flex-1 rounded-none">
          <CardContent className="md:p-6 w-full">
            {isLoading && !data ? (
              <div className="flex justify-center items-center h-full">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
              </div>
            ) : selectedMethodId === "credit-card" ? (
              <CardDeposit onDepositSuccess={onDepositSuccess} />
            ) : selectedWallet ? (
              <QRCodeDeposit
                address={selectedWallet.address}
                barcode={selectedWallet.barcode}
                title={`${selectedWallet.crypto} Deposit`}
                qrTitle={
                  isLinkWallet ? null : `${selectedWallet.crypto} QR CODE`
                }
                addressTitle={
                  isLinkWallet ? "PAYMENT LINK" : `${selectedWallet.crypto} ADDRESS`
                }
                instruction={isLinkWallet ? selectedWallet.crypto_network : undefined}
                addressIsLink={isLinkWallet}
                generateQr={!isLinkWallet}
                destinationLabel={isLinkWallet ? "link" : "address"}
                onSubmit={handleSubmit}
                onDepositSuccess={onDepositSuccess}
              />
            ) : (
              <div className="flex justify-center items-center h-full">
                <p className="text-muted-foreground">
                  Please select a payment method
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

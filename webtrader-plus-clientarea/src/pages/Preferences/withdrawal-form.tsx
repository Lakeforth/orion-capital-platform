import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { WithdrawalHistory } from "@/components/withdrawal/withdrawal-history";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
import axiosInstance from "@/lib/axios";
import { useState, useEffect, useMemo } from "react";
import useDataStore from "@/store/dataStore";
import {WireTransferConfirmationModal} from "@/components/withdrawal/WireTransferConfirmationModal.tsx";
import { usePortfolioSummaryQuery } from "@/services/portfolio/portfolio-queries";
import { useCurrency } from "@/hooks/useCurrency";

// Sentinel for "withdraw from Spot Wallet" -- the backend's withdrawStore()
// already defaults to the Spot Wallet whenever no `source` (or a value not
// starting with "trading_account:") is sent, so this never actually reaches
// the API; submitWithdrawal() strips it back out before posting.
const SPOT_WALLET_SOURCE = "spot_wallet";

const baseWithdrawalSchema = z.object({
  amount: z.string().min(1, "Amount is required"),
  method: z.string().min(1, "Payment method is required"),
  source: z.string().min(1, "Select where to withdraw from"),
});

const cryptoWithdrawalSchema = baseWithdrawalSchema.extend({
  network: z.string().min(1, "Network is required"),
  wallet_address: z.string().min(1, "Wallet address is required"),
  // These fields should not be included in crypto validation
  bank_name: z.string().optional(),
  bank_address: z.string().optional(),
  iban_number: z.string().optional(),
  account_number: z.string().optional(),
  account_name: z.string().optional(),
  swiftcode: z.string().optional(),
});

const wireTransferWithdrawalSchema = baseWithdrawalSchema.extend({
  bank_name: z.string().min(1, "Bank name is required"),
  bank_address: z.string().min(1, "Bank address is required"),
  account_number: z.string().min(1, "Account number is required"),
  account_name: z.string().min(1, "Account name is required"),
  iban_number: z.string().optional(),
  swiftcode: z.string().optional(),
  // These fields should not be included in wire transfer validation
  network: z.string().optional(),
  wallet_address: z.string().optional(),
});

const combinedSchema = z.discriminatedUnion("method", [
  cryptoWithdrawalSchema.extend({ method: z.literal("crypto") }),
  wireTransferWithdrawalSchema.extend({ method: z.literal("wire_transfer") }),
]);

type WithdrawalFormData = z.infer<typeof combinedSchema>;

interface WithdrawalFormProps {
  /**
   * Drops the page chrome -- the full-height background, the page padding and
   * the centred heading -- so the same flow can be dropped into a dialog. The
   * webtrader has no client-area routes of its own, so its Withdraw button
   * opens this rather than sending the client to another origin to find it.
   */
  embedded?: boolean;
}

export default function WithdrawalForm({ embedded = false }: WithdrawalFormProps = {}) {
  const { data, fetchData } = useDataStore();
  const { formatCurrency } = useCurrency();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshTrigger, setRefreshTrigger] = useState(0);
  const [showConfirmationModal, setShowConfirmationModal] = useState(false);
  const [formValues, setFormValues] = useState<WithdrawalFormData | null>(null);

  const { data: portfolio, isLoading: isLoadingPortfolio } = usePortfolioSummaryQuery();

  const form = useForm<WithdrawalFormData>({
    resolver: zodResolver(combinedSchema),
    defaultValues: {
      amount: "",
      method: "crypto",
      source: SPOT_WALLET_SOURCE,
      network: "",
      wallet_address: "",
      bank_name: "",
      bank_address: "",
      account_number: "",
      account_name: "",
      swiftcode: "",
      iban_number: "",
    },
  });

  const method = form.watch("method");
  const source = form.watch("source");

  // Only live accounts can be withdrawn from directly -- practice/challenge
  // accounts aren't real money (mirrors TradingAccountController's own
  // isWithdrawable() rule for the equivalent account -> Spot Wallet path).
  const liveAccounts = useMemo(
    () => (portfolio?.trading_accounts ?? []).filter((a) => a.account_type === "live"),
    [portfolio],
  );

  const availableForSource = useMemo(() => {
    if (source === SPOT_WALLET_SOURCE) return portfolio?.spot_wallet_balance ?? 0;
    const account = liveAccounts.find((a) => `trading_account:${a.id}` === source);
    return account?.withdrawable_balance ?? 0;
  }, [source, portfolio, liveAccounts]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Set default network if available
  useEffect(() => {
    if (data?.crypto_networks?.length) {
      const currentNetwork = form.getValues("network");
      if (!currentNetwork) {
        form.setValue("network", data.crypto_networks[0], {
          shouldValidate: true,
        });
      }
    }
  }, [data?.crypto_networks, form]);

  // This function prepares the form submission and shows modal for wire transfers
  async function onSubmit(values: WithdrawalFormData) {
    if (values.method === "wire_transfer") {
      setFormValues(values);
      setShowConfirmationModal(true);
      return;
    }

    // For crypto withdrawals, proceed directly
    await submitWithdrawal(values);
  }

  // This function actually submits the form data
  async function submitWithdrawal(values: WithdrawalFormData) {
    const numericAmount = Number(values.amount);
    if (numericAmount > availableForSource) {
      toast.error(
        source === SPOT_WALLET_SOURCE
          ? `Amount exceeds your Spot Wallet balance (${formatCurrency(availableForSource)} available).`
          : `Amount exceeds what this account can withdraw (${formatCurrency(availableForSource)} available -- Profit and Deposit only).`,
      );
      return;
    }

    try {
      setIsSubmitting(true);
      // Backend (ApiController::withdrawStore) resolves the Spot Wallet by
      // default whenever `source` is absent or doesn't start with
      // "trading_account:" -- the sentinel never needs to reach the API.
      const { source: formSource, ...rest } = values;
      const payload =
        formSource === SPOT_WALLET_SOURCE ? rest : { ...rest, source: formSource };
      await axiosInstance.post("/user/withdrawal/store", payload);
      toast.success("Withdrawal request submitted successfully");
      form.reset();
      setRefreshTrigger((prev) => prev + 1);
    } catch (error: any) {
      // Extract error message from API response
      let errorMessage = "Failed to submit withdrawal request";

      if (error?.response?.data) {
        // Handle the error format: { status: "failed", error: "Insufficient balance", message: "Insufficient balance", error_type: "error" }
        const responseData = error.response.data;
        errorMessage = responseData.message || responseData.error || errorMessage;
      } else if (error?.message) {
        errorMessage = error.message;
      }

      toast.error(errorMessage);
    } finally {
      setIsSubmitting(false);
      setShowConfirmationModal(false);
    }
  }

  // Handle confirmation from the modal
  const handleConfirm = () => {
    if (formValues) {
      submitWithdrawal(formValues);
    }
  };

  return (
      <div
          className={
            embedded
              ? "flex flex-col gap-8 text-foreground"
              : "flex flex-col gap-8 p-6 bg-background text-foreground min-h-screen"
          }
      >
        {!embedded && <h1 className="text-2xl font-bold">Withdrawal</h1>}

        <div>
          <Form {...form}>
            <form
                onSubmit={form.handleSubmit(onSubmit)}
                className="space-y-6 rounded-lg border border-border bg-card p-5 sm:p-6 dark:[&_[data-slot=input]]:border-white/15 dark:[&_[data-slot=input]]:bg-background dark:[&_[data-slot=input]:hover]:border-white/30 dark:[&_[data-slot=select-trigger]]:border-white/15 dark:[&_[data-slot=select-trigger]]:bg-background dark:[&_[data-slot=select-trigger]:hover]:border-white/30"
            >
              <h2 className="text-xl font-semibold">Make a Withdrawal</h2>

              <div className="px-1">
                <FormField
                    control={form.control}
                    name="source"
                    render={({ field }) => (
                        <FormItem>
                          <FormLabel>Withdraw From</FormLabel>
                          <Select onValueChange={field.onChange} defaultValue={field.value}>
                            <FormControl>
                              <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select where to withdraw from" />
                              </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                              <SelectItem value={SPOT_WALLET_SOURCE}>
                                Spot Wallet ({formatCurrency(portfolio?.spot_wallet_balance ?? 0)} available)
                              </SelectItem>
                              {liveAccounts.map((account) => (
                                  <SelectItem key={account.id} value={`trading_account:${account.id}`}>
                                    Live Account #{account.account_id} ({formatCurrency(account.withdrawable_balance)} available)
                                  </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                          {!isLoadingPortfolio && (
                              <p className="text-xs text-muted-foreground">
                                {formatCurrency(availableForSource)} available to withdraw from this source.
                              </p>
                          )}
                          <FormMessage />
                        </FormItem>
                    )}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <FormField
                      control={form.control}
                      name="amount"
                      render={({ field }) => (
                          <FormItem>
                            <FormLabel>Amount</FormLabel>
                            <FormControl>
                              <div className="relative">
                                <Input
                                    placeholder="Enter amount"
                                    {...field}
                                    type="number"
                                    className="pr-8"
                                />
                                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                            $
                          </span>
                              </div>
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                      )}
                  />
                </div>

                <div>
                  <FormField
                      control={form.control}
                      name="method"
                      render={({ field }) => (
                          <FormItem>
                            <FormLabel>Payment Method</FormLabel>
                            <Select
                                onValueChange={field.onChange}
                                defaultValue={field.value}
                            >
                              <FormControl>
                                <SelectTrigger className="w-full">
                                  <SelectValue placeholder="Select payment method" />
                                </SelectTrigger>
                              </FormControl>
                              <SelectContent>
                                <SelectItem value="crypto">Cryptocurrency</SelectItem>
                                <SelectItem value="wire_transfer">
                                  Wire Transfer
                                </SelectItem>
                              </SelectContent>
                            </Select>
                            <FormMessage />
                          </FormItem>
                      )}
                  />
                </div>
              </div>

              {method === "crypto" && (
                  <div className="space-y-4">
                    <FormField
                        control={form.control}
                        name="network"
                        render={({ field }) => (
                            <FormItem>
                              <FormLabel>Network</FormLabel>
                              <Select
                                  onValueChange={field.onChange}
                                  defaultValue={field.value}
                              >
                                <FormControl>
                                  <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select network" />
                                  </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                  {data?.crypto_networks?.map((network) => (
                                      <SelectItem key={network} value={network}>
                                        {network}
                                      </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="wallet_address"
                        render={({ field }) => (
                            <FormItem>
                              <FormLabel>Wallet Address</FormLabel>
                              <FormControl>
                                <Input placeholder="Enter wallet address" {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                        )}
                    />
                  </div>
              )}

              {method === "wire_transfer" && (
                  <div className="space-y-4">
                    <FormField
                        control={form.control}
                        name="bank_name"
                        render={({ field }) => (
                            <FormItem>
                              <FormLabel>Bank Name</FormLabel>
                              <FormControl>
                                <Input placeholder="Enter bank name" {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="bank_address"
                        render={({ field }) => (
                            <FormItem>
                              <FormLabel>Bank Address</FormLabel>
                              <FormControl>
                                <Input placeholder="Enter bank address" {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                        )}
                    />

                    <div className="grid grid-cols-2 gap-4">
                      <FormField
                          control={form.control}
                          name="account_number"
                          render={({ field }) => (
                              <FormItem>
                                <FormLabel>Account Number</FormLabel>
                                <FormControl>
                                  <Input
                                      placeholder="Enter account number"
                                      {...field}
                                  />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                          )}
                      />

                      <FormField
                          control={form.control}
                          name="account_name"
                          render={({ field }) => (
                              <FormItem>
                                <FormLabel>Account Name</FormLabel>
                                <FormControl>
                                  <Input placeholder="Enter account name" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                          )}
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                      <FormField
                          control={form.control}
                          name="iban_number"
                          render={({ field }) => (
                              <FormItem>
                                <FormLabel>IBAN Number</FormLabel>
                                <FormControl>
                                  <Input placeholder="Enter IBAN Number" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                          )}
                      />

                      <FormField
                          control={form.control}
                          name="swiftcode"
                          render={({ field }) => (
                              <FormItem>
                                <FormLabel>Swift Code</FormLabel>
                                <FormControl>
                                  <Input placeholder="Enter SWIFT code" {...field} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                          )}
                      />
                    </div>
                  </div>
              )}

              <div className="flex gap-4">
                <Button
                    type="submit"
                    className="bg-primary text-primary-foreground"
                    disabled={isSubmitting}
                >
                  {isSubmitting ? "Submitting..." : "Submit"}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => form.reset()}
                >
                  Reset Form
                </Button>
              </div>
            </form>
          </Form>
        </div>

        <div>
          <h2 className="text-lg font-medium mb-6">Withdrawal History</h2>
          <WithdrawalHistory refreshTrigger={refreshTrigger} />
        </div>

        {/* Wire Transfer Confirmation Modal */}
        <WireTransferConfirmationModal
            open={showConfirmationModal}
            isCard={false}
            onOpenChange={setShowConfirmationModal}
            onConfirm={handleConfirm}
            isSubmitting={isSubmitting}
        />
      </div>
  );
}

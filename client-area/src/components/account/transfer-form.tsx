import * as React from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { UserAccount } from "@/store/userStore";
import axiosInstance from "@/lib/axios";
import { AxiosError } from "axios";
import { toast } from "sonner";
import useUserStore from "@/store/userStore";

interface TransferFormProps {
  accounts: UserAccount[];
}

export function TransferForm({ accounts }: TransferFormProps) {
  const [fromAccount, setFromAccount] = React.useState<string>("credit_wallet");
  const [toAccount, setToAccount] = React.useState<string>("balance_wallet");
  const [amount, setAmount] = React.useState<string>("");
  const [isLoading, setIsLoading] = React.useState(false);
  const setUser = useUserStore((state) => state.setUser);

  const refreshUserData = async () => {
    try {
      const { data } = await axiosInstance.get("/user");
      if (data?.data) {
        // Get the current token from the store
        const currentToken = useUserStore.getState().token || "";
        setUser(data.data, currentToken);
      }
    } catch {
      console.error("Failed to refresh user data");
    }
  };

  const handleTransfer = async () => {
    try {
      setIsLoading(true);
      const formData = new FormData();
      formData.append("from_account", fromAccount);
      formData.append("to_account", toAccount);
      formData.append("amount", amount);

      await axiosInstance.post("/account/convert", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      toast.success("Transfer completed successfully");
      setAmount("");

      // Subtle refresh of user data after successful transfer
      setTimeout(() => {
        refreshUserData();
      }, 300);
    } catch (error) {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Transfer failed");
      } else {
        toast.error("An unexpected error occurred");
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Get available "from" accounts (those with transfer_type "from")
  const fromAccounts = accounts.filter((acc) => acc.transfer_type === "from");
  // Get available "to" accounts (those with transfer_type "to")
  const toAccounts = accounts.filter((acc) => acc.transfer_type === "to");

  return (
    <div className="space-y-6">
      <h2 className="text-lg font-medium">
        TRANSFER FUNDS BETWEEN YOUR ACCOUNTS
      </h2>

      <div className="grid grid-col-1 lg:grid-cols-2 gap-6">
        <div className="space-y-2">
          <label htmlFor="fromAccount" className="text-muted-foreground">
            From account
          </label>
          <Select
            value={fromAccount}
            onValueChange={setFromAccount}
            disabled={fromAccounts.length === 0}
          >
            <SelectTrigger className="bg-card border-card-foreground/10 w-full">
              <SelectValue>
                {fromAccounts.find(
                  (acc) => acc.type === fromAccount.split("_")[0]
                )?.title || "Select account"}{" "}
                {fromAccounts.find(
                  (acc) => acc.type === fromAccount.split("_")[0]
                )
                  ? ` ($${
                      fromAccounts.find(
                        (acc) => acc.type === fromAccount.split("_")[0]
                      )?.balance
                    })`
                  : ""}
              </SelectValue>
            </SelectTrigger>
            <SelectContent>
              {fromAccounts.map((account) => (
                <SelectItem key={account.type} value={`${account.type}_wallet`}>
                  {account.title} &nbsp;&nbsp;&nbsp;&nbsp;(${account.balance})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <label htmlFor="toAccount" className="text-muted-foreground">
            To account
          </label>
          <Select
            value={toAccount}
            onValueChange={setToAccount}
            disabled={toAccounts.length === 0}
          >
            <SelectTrigger className="bg-card border-card-foreground/10 w-full">
              <SelectValue>
                {toAccounts.find((acc) => acc.type === toAccount.split("_")[0])
                  ?.title || "Select account"}{" "}
                {toAccounts.find((acc) => acc.type === toAccount.split("_")[0])
                  ? ` ($${
                      toAccounts.find(
                        (acc) => acc.type === toAccount.split("_")[0]
                      )?.balance
                    })`
                  : ""}
              </SelectValue>
            </SelectTrigger>
            <SelectContent>
              {toAccounts.map((account) => (
                <SelectItem key={account.type} value={`${account.type}_wallet`}>
                  {account.title} &nbsp;&nbsp;&nbsp;&nbsp;(${account.balance})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <label htmlFor="amount" className="text-muted-foreground">
            Amount
          </label>
          <Input
            id="amount"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            className="bg-card border-card-foreground/10"
            placeholder="0.00"
            type="number"
            step="0.01"
            min="0"
          />
        </div>

        <div className="flex items-end">
          <Button
            onClick={handleTransfer}
            className="w-full bg-primary hover:bg-success/90 text-success-foreground"
            disabled={
              !amount ||
              fromAccount === toAccount ||
              Number.parseFloat(amount) <= 0 ||
              isLoading
            }
          >
            {isLoading ? "Processing..." : "Make Transfer"}
          </Button>
        </div>
      </div>
    </div>
  );
}

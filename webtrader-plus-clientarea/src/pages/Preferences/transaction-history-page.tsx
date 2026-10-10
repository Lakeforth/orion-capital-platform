import TransactionHistory from "@/components/transaction-history";

export default function TransactionHistoryPage() {
  return (
    <div className="flex flex-col p-6 bg-background text-foreground min-h-screen">
      <TransactionHistory />
    </div>
  );
}

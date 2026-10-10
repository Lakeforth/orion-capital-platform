import { Button } from "@/components/ui/button";

const PaypalDeposit = () => {
  return (
    <div className="animate-fade-in">
      <div className="bg-background p-6 rounded-lg border border-border/20 mb-6">
        <h2 className="text-lg font-medium mb-4">PayPal Deposit</h2>
        <div className="mb-4">
          <p className="text-muted-foreground mb-2">Connect your PayPal account:</p>
          <Button className="w-full">
            Connect with PayPal
          </Button>
        </div>
        <div className="flex flex-col space-y-2 mt-4">
          <p className="text-sm text-muted-foreground">• Minimum deposit: $10</p>
          <p className="text-sm text-muted-foreground">• Processing time: Instant</p>
          <p className="text-sm text-muted-foreground">• PayPal fees may apply</p>
        </div>
      </div>

   </div>
  );
};

export default PaypalDeposit;

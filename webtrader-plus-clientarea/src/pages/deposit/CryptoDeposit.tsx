import TransactionHistory from "@/components/transaction-history";

const CryptoDeposit = () => {
  return (
    <div className="animate-fade-in">
      <div className="bg-trading-dark p-6 rounded-lg border border-border/20 mb-6">
        <h2 className="text-lg font-medium mb-4">Crypto Deposit</h2>
        <div className="mb-4">
          <p className="text-muted mb-2">
            Send your crypto to the following address:
          </p>
          <div className="bg-trading-darker p-3 rounded border border-border/20 font-mono text-sm">
            0x7F367cC41522cE07553e823bf3be79A889DEbe1B
          </div>
        </div>
        <div className="flex flex-col space-y-2">
          <p className="text-sm text-muted/70">• Minimum deposit: 0.01 BTC</p>
          <p className="text-sm text-muted/70">
            • Confirmation time: 10-30 minutes
          </p>
          <p className="text-sm text-muted/70">
            • Only send BTC to this address
          </p>
        </div>
      </div>

      <TransactionHistory />
    </div>
  );
};

export default CryptoDeposit;

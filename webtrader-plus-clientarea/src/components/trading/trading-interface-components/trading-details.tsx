export function TradingDetails() {
  return (
    <div className="space-y-2 text-sm">
      <div className="flex justify-between">
        <span className="text-primary">Contract size:</span>
        <span className="text-green-500">100,000</span>
      </div>
      <div className="flex justify-between">
        <span className="text-primary">Position:</span>
        <span className="text-green-500">1,000</span>
      </div>
      <div className="flex justify-between">
        <span className="text-primary">Margin:</span>
        <span className="text-green-500">$31.24</span>
      </div>
      <div className="flex justify-between">
        <span className="text-primary">Free Margin:</span>
        <span className="text-green-500">$609.98</span>
      </div>
      <div className="flex justify-between">
        <span className="text-primary">Spread:</span>
        <span className="text-green-500">0.006</span>
      </div>
      <div className="flex justify-between">
        <span className="text-primary">Leverage:</span>
        <span className="text-green-500">1:20</span>
      </div>
    </div>
  );
}

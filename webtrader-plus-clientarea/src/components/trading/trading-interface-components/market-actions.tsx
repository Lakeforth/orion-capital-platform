import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/sonner";

export function MarketActions() {
  const handleBuy = () => {
    toast(
      <div className="flex items-center gap-2">
        <div className="flex items-center">
          <div className="relative h-6 w-10">
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="h-5 w-5 rounded-full overflow-hidden bg-blue-500">
                <img
                  src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-wctnsLBOuq2uFrbDreBTeFWyNOiWrC.png"
                  alt="AUD/JPY"
                  className="h-full w-full object-cover"
                />
              </div>
            </div>
          </div>
          <div className="ml-1">
            <div className="font-medium text-sm">Buy AUD/JPY</div>
            <div className="text-xs text-green-500">1000 AUD/JPY @ 93.153</div>
          </div>
        </div>
      </div>,
      {
        position: "bottom-left",
      }
    );
  };

  const handleSell = () => {
    toast(
      <div className="flex items-center gap-2">
        <div className="flex items-center">
          <div className="relative h-6 w-10">
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="h-5 w-5 rounded-full overflow-hidden bg-blue-500">
                <img
                  src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-wctnsLBOuq2uFrbDreBTeFWyNOiWrC.png"
                  alt="AUD/JPY"
                  className="h-full w-full object-cover"
                />
              </div>
            </div>
          </div>
          <div className="ml-1">
            <div className="font-medium text-sm">Sell AUD/JPY</div>
            <div className="text-xs text-red-500">1000 AUD/JPY @ 93.147</div>
          </div>
        </div>
      </div>,
      {
        position: "bottom-left",
      }
    );
  };

  return (
    <div className="space-y-2">
      <div className="flex justify-between text-xs">
        <span className="text-muted-foreground">Pending</span>
        <span className="font-medium">Market</span>
      </div>

      <div className="grid grid-cols-2 gap-2">
        <Button
          className="bg-green-500 hover:bg-green-600 text-white"
          onClick={handleBuy}
        >
          <div className="flex flex-col items-center">
            <span className="font-bold">BUY</span>
            <span className="text-sm">93.153</span>
          </div>
        </Button>
        <Button
          className="bg-red-500 hover:bg-red-600 text-white"
          onClick={handleSell}
        >
          <div className="flex flex-col items-center">
            <span className="font-bold">SELL</span>
            <span className="text-sm">93.147</span>
          </div>
        </Button>
      </div>
    </div>
  );
}

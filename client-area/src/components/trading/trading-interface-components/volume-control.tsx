import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ChevronDown, ChevronUp } from "lucide-react";

interface VolumeControlProps {
  volume: number;
  setVolume: (volume: number) => void;
  activeTab: string;
  setActiveTab: (tab: string) => void;
}

export function VolumeControl({
  volume,
  setVolume,
  activeTab,
  setActiveTab,
}: VolumeControlProps) {
  const increaseVolume = () => {
    setVolume(Number.parseFloat((volume + 0.01).toFixed(2)));
  };

  const decreaseVolume = () => {
    if (volume > 0.01) {
      setVolume(Number.parseFloat((volume - 0.01).toFixed(2)));
    }
  };

  const handleTabChange = (value: string) => {
    setActiveTab(value);
  };

  const displayVolume = () => {
    switch (activeTab) {
      case "lots":
        return volume.toFixed(2);
      case "units":
        return (volume * 100000).toFixed(0);
      case "currency":
        return `$${(volume * 100000 * 0.0001).toFixed(2)}`;
      default:
        return volume.toFixed(2);
    }
  };

  return (
    <div className="space-y-4">
      <div className="space-y-1">
        <label className="text-xs text-muted-foreground">Volume</label>
        <div className="flex">
          <Input
            value={displayVolume()}
            readOnly
            className="rounded-r-none bg-background border-r-0"
          />
          <div className="flex flex-col border border-l-0 rounded-r-md bg-background">
            <Button
              variant="ghost"
              size="icon"
              className="h-6 rounded-none hover:bg-muted"
              onClick={increaseVolume}
            >
              <ChevronUp className="h-3 w-3" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              className="h-6 rounded-none hover:bg-muted"
              onClick={decreaseVolume}
            >
              <ChevronDown className="h-3 w-3" />
            </Button>
          </div>
        </div>
      </div>

      <Tabs
        value={activeTab}
        onValueChange={handleTabChange}
        className="w-full"
      >
        <TabsList className="grid grid-cols-3 h-8">
          <TabsTrigger value="lots" className="text-xs">
            lots
          </TabsTrigger>
          <TabsTrigger value="units" className="text-xs">
            units
          </TabsTrigger>
          <TabsTrigger value="currency" className="text-xs">
            currency
          </TabsTrigger>
        </TabsList>
      </Tabs>
    </div>
  );
}

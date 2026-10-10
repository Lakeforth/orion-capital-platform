import { Wifi, WifiOff } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

interface WebSocketStatusIndicatorProps {
    isConnected: boolean;
}

export function WebSocketStatusIndicator({ isConnected }: WebSocketStatusIndicatorProps) {
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger>
                    <div className="flex items-center">
                        {isConnected ? (
                            <Wifi className="h-3.5 w-3.5 text-green-500" />
                        ) : (
                            <WifiOff className="h-3.5 w-3.5 text-red-500" />
                        )}
                    </div>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p className="text-xs">
                        {isConnected
                            ? "Live data connection active"
                            : "Live data connection inactive"}
                    </p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}

export default WebSocketStatusIndicator;

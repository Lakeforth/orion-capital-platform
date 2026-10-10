import { Wifi, WifiOff, AlertCircle } from 'lucide-react';
import { usePusher } from '@/hooks/usePusher';
import { ConnectionStatus } from '@/services/PusherService';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

/**
 * Component to display the WebSocket connection status
 * Can be used in any part of the application where connection status is important
 */
const ConnectionStatusIndicator = () => {
    const { connectionStatus, error, reconnect } = usePusher();

    const getStatusIcon = () => {
        switch (connectionStatus) {
            case 'connected':
                return (
                    <Wifi className="h-4 w-4 text-green-500" />
                );
            case 'connecting':
                return (
                    <Wifi className="h-4 w-4 text-amber-500 animate-pulse" />
                );
            case 'disconnected':
                return (
                    <WifiOff className="h-4 w-4 text-muted-foreground" />
                );
            case 'failed':
                return (
                    <AlertCircle className="h-4 w-4 text-red-500" />
                );
            default:
                return (
                    <WifiOff className="h-4 w-4 text-muted-foreground" />
                );
        }
    };

    const getStatusMessage = () => {
        switch (connectionStatus) {
            case 'connected':
                return 'Connected to server';
            case 'connecting':
                return 'Connecting to server...';
            case 'disconnected':
                return 'Disconnected from server. Click to reconnect.';
            case 'failed':
                return `Connection failed: ${error || 'Unknown error'}. Click to retry.`;
            default:
                return 'Server connection status unknown';
        }
    };

    const getStatusClass = (status: ConnectionStatus): string => {
        switch (status) {
            case 'connected':
                return 'bg-green-500/10 border-green-500/20';
            case 'connecting':
                return 'bg-amber-500/10 border-amber-500/20';
            case 'disconnected':
                return 'bg-muted/30 border-muted/30';
            case 'failed':
                return 'bg-red-500/10 border-red-500/20';
            default:
                return 'bg-muted/30 border-muted/30';
        }
    };

    const handleClick = () => {
        // Only trigger reconnect on disconnected or failed states
        if (connectionStatus === 'disconnected' || connectionStatus === 'failed') {
            reconnect();
        }
    };

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <button
                        className={cn(
                            "flex items-center gap-2 px-2 py-1 rounded-md border text-xs",
                            getStatusClass(connectionStatus),
                            (connectionStatus === 'disconnected' || connectionStatus === 'failed') &&
                            "hover:bg-muted/50 cursor-pointer"
                        )}
                        onClick={handleClick}
                        disabled={connectionStatus === 'connected' || connectionStatus === 'connecting'}
                    >
                        {getStatusIcon()}
                        <span className="hidden sm:inline">{getStatusMessage()}</span>
                    </button>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    <p>{getStatusMessage()}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
};

export default ConnectionStatusIndicator;

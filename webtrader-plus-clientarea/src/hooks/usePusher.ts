import { useState, useEffect, useCallback } from 'react';
import PusherService, { ConnectionStatus } from '@/services/PusherService';


export function usePusher() {
    const [connectionStatus, setConnectionStatus] = useState<ConnectionStatus>('disconnected');
    const [error, setError] = useState<string | null>(null);

    // Get the singleton instance
    const pusherService = PusherService.getInstance();

    // Connect to Pusher and monitor connection status
    useEffect(() => {
        // Setup connection status listener
        const unsubscribe = pusherService.onConnectionChange((status, err) => {
            setConnectionStatus(status);
            setError(err || null);
        });

        // Initialize connection if not already connected
        if (pusherService.getStatus() === 'disconnected') {
            pusherService.connect();
        }

        // Cleanup on unmount
        return () => {
            unsubscribe();
        };
    }, []);


    const subscribeToPrivateChannel = useCallback(<T = unknown>(
        channelName: string,
        events: Record<string, (data: T) => void>
    ): void => {
        pusherService.subscribeToPrivateChannel<T>(channelName, events);
    }, []);

    /**
     * Unsubscribe from a channel
     */
    const unsubscribeFromChannel = useCallback((channelName: string): void => {
        pusherService.unsubscribeFromChannel(channelName);
    }, []);

    /**
     * Force reconnect the WebSocket connection
     */
    const reconnect = useCallback((): void => {
        pusherService.disconnect();
        pusherService.connect();
    }, []);

    return {
        connectionStatus,
        error,
        subscribeToPrivateChannel,
        unsubscribeFromChannel,
        reconnect
    };
}

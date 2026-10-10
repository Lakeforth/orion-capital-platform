import { useEffect, useState } from 'react';
import PusherService from '@/services/PusherService';
import useUserStore from '@/store/userStore';
import { toast } from '@/components/ui/sonner';
import NotificationListener from "@/components/NotificationListener.tsx";


const WebSocketInitializer = () => {
    const token = useUserStore((state) => state.token);
    const [showReconnectingToast, setShowReconnectingToast] = useState(false);

    useEffect(() => {
        if (!token) return;

        const pusherService = PusherService.getInstance();
        let reconnectToastId: string | number | undefined;

        // Setup connection status handler
        const unsubscribe = pusherService.onConnectionChange((status, error) => {
            if (status === 'connecting' && !showReconnectingToast) {
                setShowReconnectingToast(true);
                reconnectToastId = toast.loading('Reconnecting to server...', {
                    duration: 0, // Don't auto-dismiss
                    id: 'websocket-reconnecting',
                });
            } else if (status === 'connected' && showReconnectingToast) {
                setShowReconnectingToast(false);
                toast.success('Connected to server', {
                    id: reconnectToastId,
                    duration: 2000,
                });
            } else if (status === 'failed') {
                setShowReconnectingToast(false);
                toast.error(`Connection failed: ${error || 'Unknown error'}`, {
                    id: reconnectToastId,
                    duration: 4000,
                });
            }
        });

        // Initialize connection
        if (pusherService.getStatus() === 'disconnected') {
            pusherService.connect();
        }

        // Cleanup on unmount
        return () => {
            unsubscribe();
            if (showReconnectingToast && reconnectToastId) {
                toast.dismiss(reconnectToastId);
            }
        };
    }, [token, showReconnectingToast]);


    return <NotificationListener />;

};

export default WebSocketInitializer;

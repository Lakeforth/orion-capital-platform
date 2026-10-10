import { useEffect } from 'react';
import useUserStore from '@/store/userStore';
import useOnlineStatusStore from "@/store/OnlineStatusState";

export function OnlineStatusInitializer() {
    const startHeartbeat = useOnlineStatusStore(state => state.startHeartbeat);
    const stopHeartbeat = useOnlineStatusStore(state => state.stopHeartbeat);
    const user = useUserStore(state => state.user);

    useEffect(() => {
        if (!user) {
            return;
        }

        // Defer heartbeat until critical post-login requests have had time to run.
        const heartbeatTimer = window.setTimeout(() => {
            startHeartbeat();
        }, 3000);

        return () => {
            window.clearTimeout(heartbeatTimer);
            stopHeartbeat();
        };
    }, [user, startHeartbeat, stopHeartbeat]);

    return null;
}

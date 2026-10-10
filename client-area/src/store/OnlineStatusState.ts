import { create } from "zustand";
import axiosInstance from "@/lib/axios";

interface OnlineStatusState {
    startHeartbeat: () => void;
    stopHeartbeat: () => void;
}

const useOnlineStatusStore = create<OnlineStatusState>(() => {
    let heartbeatInterval: ReturnType<typeof setInterval> | null = null;
    let isHeartbeatActive = false;

    const sendHeartbeat = async () => {
        try {
            await axiosInstance.post('/user/heartbeat');
        } catch (error) {
            console.log('Heartbeat error:', error);
        }
    };

    return {
        startHeartbeat: () => {
            if (isHeartbeatActive) {
                console.log('Heartbeat already active');
                return;
            }

            isHeartbeatActive = true;

            sendHeartbeat();

            heartbeatInterval = setInterval(() => {
                if (!document.hidden) {
                    sendHeartbeat();
                }
            }, 60000); // 1 minute


            // Handle visibility changes
            const handleVisibilityChange = () => {
                if (!document.hidden) {
                    // Send a heartbeat when tab becomes visible again
                    sendHeartbeat();
                }
            };

            document.addEventListener('visibilitychange', handleVisibilityChange);

            // Store the cleanup function
            window._heartbeatCleanup = () => {
                document.removeEventListener('visibilitychange', handleVisibilityChange);
            };
        },

        stopHeartbeat: () => {
            if (heartbeatInterval) {
                clearInterval(heartbeatInterval);
                heartbeatInterval = null;
            }

            if (window._heartbeatCleanup) {
                window._heartbeatCleanup();
                delete window._heartbeatCleanup;
            }

            isHeartbeatActive = false;
            console.log('Heartbeat stopped');
        }
    };
});

declare global {
    interface Window {
        _heartbeatCleanup?: () => void;
    }
}

export default useOnlineStatusStore;

import { useEffect, useRef, useState, useCallback }  from "react";

import { useLocation, useNavigate } from "react-router-dom";
import { usePusher } from "@/hooks/usePusher";
import useUserStore from "@/store/userStore";
import { AlertWithIcon } from "@/components/Alert";
import axiosInstance from "@/lib/axios";

interface ChatMessage {
    id: string;
    message: string;
    sender_id: string;
    receiver_id: string | null;
    is_admin: boolean;
    read_at: string | null;
    created_at: string;
    sender?: {
        id: string;
        first_name: string;
        last_name: string;
        avatar: string;
    };
}

interface MessageSentEvent {
    message: ChatMessage;
}

interface UnreadMessagesResponse {
    unread_count: number;
}

export const ChatNotificationListener = () => {
    const { subscribeToPrivateChannel, unsubscribeFromChannel, connectionStatus } = usePusher();
    const userId = useUserStore((state) => state.user?.id);
    const location = useLocation();
    const navigate = useNavigate();
    const audioRef = useRef<HTMLAudioElement | null>(null);

    const [unreadCount, setUnreadCount] = useState<number>(0);
    const [showNotification, setShowNotification] = useState(false);

    // Track if we've subscribed to chat channel
    const chatSubscribed = useRef(false);

    // Check if we're on the chat page
    const isOnChatPage = location.pathname === "/main/chat";

    // Initialize audio
    useEffect(() => {
        audioRef.current = new Audio("/sounds/notification.mp3");

        return () => {
            if (audioRef.current) {
                audioRef.current.pause();
                audioRef.current = null;
            }
        };
    }, []);

    // Function to fetch unread message count
    const fetchUnreadCount = useCallback(async () => {
        try {
            const response = await axiosInstance.get<UnreadMessagesResponse>('/chat/unread-count');
            setUnreadCount(response.data.unread_count);

            // Show notification if we have unread messages and we're not on chat page
            if (response.data.unread_count > 0 && !isOnChatPage) {
                setShowNotification(true);
            }
        } catch (error) {
            console.error("Failed to fetch unread messages count:", error);
        }
    }, [isOnChatPage]);

    // Fetch unread count after login bootstrap traffic has settled.
    useEffect(() => {
        if (!userId) {
            return;
        }

        const unreadTimer = window.setTimeout(() => {
            fetchUnreadCount();
        }, 4000);

        return () => {
            window.clearTimeout(unreadTimer);
        };
    }, [userId, fetchUnreadCount]);

    // Handle new message notification
    const handleNewMessage = useCallback((event: MessageSentEvent) => {
        // Don't show notification if we're on the chat page
        if (isOnChatPage) return;

        // Don't show notification if message is from current user
        if (event.message.sender_id === userId) return;

        // Play notification sound
        if (audioRef.current) {
            audioRef.current.currentTime = 0;
            audioRef.current.play().catch((err) => {
                console.error("Failed to play notification sound:", err);
            });
        }

        // Fetch the latest unread count
        fetchUnreadCount();
    }, [isOnChatPage, userId, fetchUnreadCount]);

    // Subscribe to chat channel
    useEffect(() => {
        // Only proceed if we're connected and have a user ID
        if (connectionStatus !== "connected" || !userId) {
            return;
        }

        // Subscribe to chat channel if not already subscribed
        if (!chatSubscribed.current) {
            subscribeToPrivateChannel<MessageSentEvent>(`chat.customer.${userId}`, {
                '.message.sent': handleNewMessage,
            });
            chatSubscribed.current = true;
        }

        // Cleanup on unmount
        return () => {
            if (chatSubscribed.current) {
                unsubscribeFromChannel(`chat.customer.${userId}`);
                chatSubscribed.current = false;
            }
        };
    }, [connectionStatus, userId, subscribeToPrivateChannel, unsubscribeFromChannel, handleNewMessage]);

    // Hide notification when navigating to chat page
    useEffect(() => {
        if (isOnChatPage) {
            setShowNotification(false);
        }
    }, [isOnChatPage]);

    // Handle notification click
    const handleNotificationClick = () => {
        navigate("/main/chat");
        setShowNotification(false);
    };

    // Don't render if no notification to show
    if (!showNotification || unreadCount === 0) {
        return null;
    }

    // Render the notification
    return (
        <div
            className="fixed top-4 right-4 z-50 max-w-md w-full animate-in fade-in-0 slide-in-from-top-4 duration-300 cursor-pointer"
            onClick={handleNotificationClick}
        >
            <AlertWithIcon
                variant="destructive"
                title="New Messages"
                description={`You have ${unreadCount} unread message${unreadCount > 1 ? 's' : ''}, TAP TO VIEW`}
                className="shadow-lg hover:bg-destructive/90 transition-colors"
            />
        </div>
    );
}

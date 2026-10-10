import { useEffect, useRef, useCallback, useState } from "react";
import { useLocation } from "react-router-dom";
import { useQueryClient } from "@tanstack/react-query";
import { usePusher } from "@/hooks/usePusher";
import useUserStore from "@/store/userStore";
import { AlertWithIcon } from "@/components/Alert";
import axiosInstance from "@/lib/axios";
import useTradeStore from "@/store/tradeStore.ts";
import { TRADING_ACCOUNTS_QUERY_KEY } from "@/services/trading-accounts/trading-account-queries";
import { PORTFOLIO_SUMMARY_QUERY_KEY } from "@/services/portfolio/portfolio-queries";

// Notification structure from the user object
export interface UserNotification {
  id: string;
  title: string;
  message: string;
  image: string | null;
  color: "danger" | "success" | "warning" | null;
  type: string;
  time: string;
  read_at: string | null;
  created_at: string | null;
  read: boolean;
}

// Pusher event data structure
interface NotificationData {
  user_id: string;
  data: {
    type: string;
    message: string;
    title: string;
    color?: "danger" | "success" | "warning" | null;
  };
}

// User update event data structure
interface UserUpdateData {
  id: string;
  type: string;
  data: Record<string, unknown>;
}

// Polling intervals
const POLLING_INTERVAL = 30000; // 30 seconds
const FAST_POLLING_INTERVAL = 5000; // 5 seconds for critical updates

export function NotificationListener() {
  const {
    subscribeToPrivateChannel,
    unsubscribeFromChannel,
    connectionStatus,
  } = usePusher();
  const location = useLocation();
  // Prop Firm challenge accounts aren't reflected by the generic open/closed
  // trades endpoints at all (separate data model) -- polling them while in
  // this section only produces noisy timeouts/errors for data nobody reads.
  const isInPropfirmSection = location.pathname.startsWith("/main/propfirm/");
  const userId = useUserStore((state) => state.user?.id);
  const user = useUserStore((state) => state.user);
  const getCurrentUser = useUserStore((state) => state.getCurrentUser);
  const fetchOpenTrades = useTradeStore((state) => state.fetchOpenTrades);
  const fetchClosedTrades = useTradeStore((state) => state.fetchClosedTrades);
  const queryClient = useQueryClient();
  const audioRef = useRef<HTMLAudioElement | null>(null);

  // State to track the current notification to display
  const [activeNotification, setActiveNotification] = useState<UserNotification | null>(null);
  const [pollingInterval, setPollingInterval] = useState<NodeJS.Timeout | null>(null);
  const [isPolling, setIsPolling] = useState(false);

  // Track if the component is mounted to prevent state updates after unmount
  const isMounted = useRef(true);

  // Use a ref to track whether we've already subscribed to channels
  const channelsSubscribed = useRef<{
    notifications: boolean;
    userUpdates: boolean;
    tradeUpdates: boolean;
  }>({
    notifications: false,
    userUpdates: false,
    tradeUpdates: false,
  });

  // Initialize audio
  useEffect(() => {
    audioRef.current = new Audio("/sounds/notification.mp3");
    isMounted.current = true;

    return () => {
      isMounted.current = false;
      if (audioRef.current) {
        audioRef.current.pause();
        audioRef.current = null;
      }
    };
  }, []);

  // Polling function
  const pollForUpdates = useCallback(async () => {
    if (!isMounted.current || !userId || isPolling || isInPropfirmSection) return;

    setIsPolling(true);
    try {
      // Parallel fetch to reduce latency
      await Promise.all([
        // getCurrentUser(),
        fetchOpenTrades(),
        fetchClosedTrades()
      ]);
    } catch (error) {
      console.error("Polling error:", error);
    } finally {
      setIsPolling(false);
    }
  }, [userId, fetchOpenTrades, fetchClosedTrades, isPolling, isInPropfirmSection]);

  // Set up polling when WebSocket is not connected
  useEffect(() => {
    if (connectionStatus !== "connected" && userId) {
      // Defer the first poll so it does not compete with post-login bootstrap calls.
      const initialPollTimer = setTimeout(() => {
        pollForUpdates();
      }, 5000);

      const interval = setInterval(pollForUpdates, POLLING_INTERVAL);
      setPollingInterval(interval);

      return () => {
        clearTimeout(initialPollTimer);
        if (interval) {
          clearInterval(interval);
          setPollingInterval(null);
        }
      };
    } else {
      // Clear polling when WebSocket is connected
      if (pollingInterval) {
        clearInterval(pollingInterval);
        setPollingInterval(null);
      }
    }
  }, [connectionStatus, userId, pollForUpdates]);

  // Function to mark a notification as read
  const markNotificationAsRead = useCallback(
      async (notificationId: string) => {
        setActiveNotification(null);
        try {
          await axiosInstance.post(`/notifications/${notificationId}/read`);

          // Refresh user data to update the notification list
          if (isMounted.current) {
            console.log('isMounted.current', true)
            await getCurrentUser();
          }else {
            console.log('isMounted.current', true)
          }
        } catch (error) {
          console.error("Failed to mark notification as read:", error);
          // If marking as read fails, refetch user data anyway to ensure consistency
          if (isMounted.current) {
            getCurrentUser();
          }
        }
      },
      [getCurrentUser]
  );

  // Effect to display the first unread notification from user data
  useEffect(() => {
    if (user?.notifications && user.notifications.length > 0) {
      // Find the first unread notification
      const firstUnread = user.notifications.find(
          (notification) => !notification.read
      );

      // Set it as active if it exists and is different from current active
      if (firstUnread && (!activeNotification || firstUnread.id !== activeNotification.id)) {
        setActiveNotification(firstUnread);
      }
    }
  }, [user?.notifications, activeNotification]);

  // Handle notification from Pusher - play sound and refresh user data
  const handleNotification = useCallback(
      async (data: NotificationData) => {
        console.log("Notification received", data);

        // Play notification sound
        if (audioRef.current) {
          audioRef.current.currentTime = 0;
          audioRef.current.play().catch((err) => {
            console.error("Failed to play notification sound:", err);
          });
        }


        await getCurrentUser();


      },
      [getCurrentUser]
  );

  // Handle user updates
  const handleUserUpdate = useCallback(async () => {
    console.log("User data updated, refreshing...");
    try {
      await getCurrentUser();
    } catch (error) {
      console.error("Failed to refresh user data:", error);
      // Retry on failure
      setTimeout(() => {
        if (isMounted.current) {
          getCurrentUser();
        }
      }, FAST_POLLING_INTERVAL);
    }
  }, [getCurrentUser]);

  // Handle trade updates. A trade can close from somewhere other than the
  // tab that's currently open (another tab, the trade room, the
  // trades:auto-close-expired scheduler) -- those don't run the close
  // page's own getCurrentUser()/fetchOpenTrades() refresh, so this socket
  // event is the only thing that keeps this tab's balance in sync. Refresh
  // the user (balance lives there, calculateAccountSummary reads it from
  // the cached user object) and invalidate the react-query caches the
  // Dashboard/Trading Accounts list read from, in addition to the trade
  // lists this already refreshed.
  const handleTradeUpdate = useCallback(async () => {
    if (isInPropfirmSection) return;
    queryClient.invalidateQueries({ queryKey: TRADING_ACCOUNTS_QUERY_KEY });
    queryClient.invalidateQueries({ queryKey: PORTFOLIO_SUMMARY_QUERY_KEY });
    try {
      await Promise.all([getCurrentUser(), fetchOpenTrades(), fetchClosedTrades()]);
    } catch (error) {
      console.error("Failed to refresh trade data:", error);
      // Retry on failure
      setTimeout(() => {
        if (isMounted.current && !isInPropfirmSection) {
          getCurrentUser();
          fetchOpenTrades();
          fetchClosedTrades();
        }
      }, FAST_POLLING_INTERVAL);
    }
  }, [getCurrentUser, fetchOpenTrades, fetchClosedTrades, isInPropfirmSection, queryClient]);

  useEffect(() => {
    if (connectionStatus !== "connected" || !userId) {
      return;
    }

    // Subscribe to notifications channel if not already subscribed
    if (!channelsSubscribed.current.notifications) {
      subscribeToPrivateChannel<NotificationData>(`notification.${userId}`, {
        ".notification.message": handleNotification,
      });
      channelsSubscribed.current.notifications = true;
    }

    // Subscribe to user updates channel if not already subscribed
    if (!channelsSubscribed.current.userUpdates) {
      subscribeToPrivateChannel<UserUpdateData>(`user.${userId}`, {
        ".user.updated": handleUserUpdate,
      });
      channelsSubscribed.current.userUpdates = true;
    }

    // Subscribe to trade updates channel if not already subscribed
    if (!channelsSubscribed.current.tradeUpdates) {
      subscribeToPrivateChannel<UserUpdateData>(`trade.${userId}`, {
        ".trade.updated": handleTradeUpdate,
      });
      channelsSubscribed.current.tradeUpdates = true;
    }

    // Cleanup function to unsubscribe when component unmounts or user changes
    return () => {
      if (channelsSubscribed.current.notifications) {
        unsubscribeFromChannel(`notification.${userId}`);
        channelsSubscribed.current.notifications = false;
      }

      if (channelsSubscribed.current.userUpdates) {
        unsubscribeFromChannel(`user.${userId}`);
        channelsSubscribed.current.userUpdates = false;
      }

      if (channelsSubscribed.current.tradeUpdates) {
        unsubscribeFromChannel(`trade.${userId}`);
        channelsSubscribed.current.tradeUpdates = false;
      }
    };
  }, [
    connectionStatus,
    userId,
    subscribeToPrivateChannel,
    unsubscribeFromChannel,
    handleNotification,
    handleUserUpdate,
    handleTradeUpdate
  ]);

  // Get the alert variant based on notification color
  const getAlertVariant = (color: string | null) => {
    switch (color) {
      case "danger":
        return "destructive";
      case "success":
        return "success";
      case "warning":
        return "warning";
      default:
        return "default";
    }
  };

  // If no active notification, don't render anything
  if (!activeNotification) {
    return null;
  }

  // Render the notification alert
  return (
      <div className="fixed top-4 right-4 z-50 max-w-md w-full animate-in fade-in-0 zoom-in-95 duration-300">
        <AlertWithIcon
            variant={getAlertVariant(activeNotification.color)}
            title={activeNotification.title || "Notification"}
            description={activeNotification.message}
            onClose={() => {
              // If it's a temporary notification, just hide it
              if (activeNotification.id.startsWith('temp-')) {
                setActiveNotification(null);
              } else {
                markNotificationAsRead(activeNotification.id);
              }
            }}
            className="shadow-lg"
        />
      </div>
  );
}

export default NotificationListener;

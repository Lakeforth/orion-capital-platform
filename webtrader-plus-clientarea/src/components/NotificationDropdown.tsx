import React, { useState } from "react";
import { Bell, CheckCircle2, AlertCircle, Info, Loader2, ChevronDown, ChevronUp } from "lucide-react";
import { Button } from "./ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "./ui/dropdown-menu";
import useUserStore from "@/store/userStore";
import {
  useMarkReadMutation,
  useNotificationsQuery,
  useReadAllNotificationsMutation,
} from "@/services/notification/notification-queries";
import { cn } from "@/lib/utils";
import { formatDistanceToNow } from "date-fns";
import { motion, AnimatePresence } from "framer-motion";
import { useCurrency } from "@/hooks/useCurrency";

const NotificationDropdown: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const user = useUserStore((state) => state.user);
  const { formatCurrency } = useCurrency();
  const unreadCount = user?.notifications?.filter((n) => !n.read).length || 0;

  const { data: notificationData, isLoading } = useNotificationsQuery(1, {
    enabled: isOpen,
  });
  const markReadMutation = useMarkReadMutation();
  const readAllMutation = useReadAllNotificationsMutation();

  const notifications = notificationData?.data?.notifications || [];

  const getIcon = (color: string | null) => {
    switch (color) {
      case "success":
        return <CheckCircle2 className="h-3.5 w-3.5" />;
      case "danger":
        return <AlertCircle className="h-3.5 w-3.5" />;
      case "warning":
        return <AlertCircle className="h-3.5 w-3.5" />;
      default:
        return <Info className="h-3.5 w-3.5" />;
    }
  };

  const getIconColorClass = (color: string | null) => {
    switch (color) {
      case "success":
        return "bg-green-500/10 text-green-500";
      case "danger":
        return "bg-red-500/10 text-red-500";
      case "warning":
        return "bg-amber-500/10 text-amber-500";
      default:
        return "bg-blue-500/10 text-blue-500";
    }
  };

  const handleNotificationClick = (id: string, isRead: boolean) => {
    if (!isRead) {
      markReadMutation.mutate(id);
    }
    setExpandedId(expandedId === id ? null : id);
  };

  return (
    <DropdownMenu onOpenChange={setIsOpen}>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className="relative text-gray-400 hover:text-white"
          title="Notifications"
        >
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white border-2 border-card">
              {unreadCount > 9 ? "9+" : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-[340px] p-0 overflow-hidden">
        <div className="flex items-center justify-between px-4 py-3 bg-muted/30">
          <DropdownMenuLabel className="font-bold text-base p-0">
            Notifications
          </DropdownMenuLabel>
          {notifications.length > 0 && (
            <button
              onClick={() => readAllMutation.mutate()}
              className="text-[10px] text-primary hover:underline font-medium"
              disabled={readAllMutation.isPending}
            >
              Mark all as read
            </button>
          )}
        </div>
        <DropdownMenuSeparator className="m-0" />
        <div className="max-h-[450px] overflow-y-auto">
          {isLoading ? (
            <div className="flex flex-col items-center justify-center p-8 gap-2">
              <Loader2 className="h-6 w-6 animate-spin text-primary" />
              <p className="text-xs text-muted-foreground">
                Loading notifications...
              </p>
            </div>
          ) : notifications.length > 0 ? (
            notifications.map((notification, index) => (
              <React.Fragment key={notification.id}>
                <div
                  className={cn(
                    "flex flex-col items-start p-3 cursor-pointer transition-colors hover:bg-muted/50",
                    !notification.read && "bg-primary/5",
                    expandedId === notification.id && "bg-muted/80 ml-1 border-l-2 border-primary"
                  )}
                  onClick={() =>
                    handleNotificationClick(notification.id, notification.read)
                  }
                >
                  <div className="flex items-center gap-2 w-full">
                    <div
                      className={cn(
                        "flex h-7 w-7 items-center justify-center rounded-full flex-shrink-0",
                        getIconColorClass(notification.color),
                      )}
                    >
                      {getIcon(notification.color)}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between gap-2">
                        <span className={cn(
                          "font-semibold text-sm truncate",
                          !notification.read ? "text-foreground" : "text-muted-foreground"
                        )}>
                          {notification.title}
                        </span>
                        <span className="text-[10px] text-muted-foreground whitespace-nowrap">
                          {notification.created_at
                            ? formatDistanceToNow(new Date(notification.created_at), {
                                addSuffix: true,
                              })
                            : "N/A"}
                        </span>
                      </div>
                      <p className={cn(
                        "text-xs leading-relaxed mt-0.5",
                        expandedId === notification.id ? "text-foreground" : "text-muted-foreground line-clamp-1"
                      )}>
                        {notification.message}
                      </p>
                    </div>
                    <div className="flex-shrink-0 text-muted-foreground/50">
                      {expandedId === notification.id ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                    </div>
                  </div>

                  <AnimatePresence>
                    {expandedId === notification.id && (
                      <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{ duration: 0.2 }}
                        className="w-full overflow-hidden"
                      >
                        <div className="mt-3 pt-3 border-t border-border flex flex-col gap-2 pl-9 pr-2">
                          {notification.meta && (
                            <>
                              {notification.meta.transaction_id && (
                                <div className="flex flex-col gap-0.5">
                                  <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Transaction ID</span>
                                  <span className="text-xs font-mono break-all text-primary/80 select-all">{notification.meta.transaction_id}</span>
                                </div>
                              )}
                              <div className="grid grid-cols-2 gap-3 mt-1">
                                {notification.meta.transaction_type && (
                                  <div className="flex flex-col gap-0.5">
                                    <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Type</span>
                                    <span className="text-xs font-medium capitalize">{notification.meta.transaction_type}</span>
                                  </div>
                                )}
                                {notification.meta.amount !== undefined && (
                                  <div className="flex flex-col gap-0.5">
                                    <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Amount</span>
                                    <span className="text-xs font-bold text-green-500">{formatCurrency(notification.meta.amount)}</span>
                                  </div>
                                )}
                                {notification.meta.order_id && (
                                  <div className="flex flex-col gap-0.5">
                                    <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Order ID</span>
                                    <span className="text-xs font-medium">#{notification.meta.order_id}</span>
                                  </div>
                                )}
                              </div>
                            </>
                          )}
                          <div className="flex items-center justify-between mt-2 pt-2 border-t border-border/50 italic text-[10px] text-muted-foreground">
                            <span>Status: {notification.status || "Completed"}</span>
                            <span>Type: {notification.type}</span>
                          </div>
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
                {index < notifications.length - 1 && <DropdownMenuSeparator className="m-0 opacity-50" />}
              </React.Fragment>
            ))
          ) : (
            <div className="flex flex-col items-center justify-center p-12 gap-3 text-center">
              <div className="p-4 rounded-full bg-muted/50">
                <Bell className="h-8 w-8 text-muted-foreground/30" />
              </div>
              <div>
                <p className="text-sm font-bold text-muted-foreground">
                  No notifications yet
                </p>
                <p className="text-xs text-muted-foreground/60 px-4 mt-1">
                  We'll notify you when something important happens.
                </p>
              </div>
            </div>
          )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
};

export default NotificationDropdown;

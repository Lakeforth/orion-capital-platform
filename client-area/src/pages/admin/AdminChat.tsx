import { useState, useEffect } from "react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Loader2, MessageCircle, Menu, X } from "lucide-react";
import useOnlineStatusStore from "@/store/OnlineStatusState";
import useUserStore from "@/store/userStore";
import axiosInstance from "@/lib/axios";
import { useChat } from "@/hooks/useChat";
import ChatMessageList from "@/components/chat/ChatMessageList";
import { ChatInput } from "@/components/chat/chat-input";
import { UserList } from "./UserList";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface ChatUser {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    avatar?: string;
    account_id: string;
    last_activity?: string;
    is_online: boolean;
    unread_count?: number;
}

export default function AdminChat() {
    // State for users and selection
    const [users, setUsers] = useState<ChatUser[]>([]);
    const [searchQuery, setSearchQuery] = useState("");
    const [isInitialLoading, setIsInitialLoading] = useState(true);
    const [selectedTab, setSelectedTab] = useState<"all" | "online" | "unread">("all");
    const [selectedUser, setSelectedUser] = useState<ChatUser | null>(null);
    const [messageText, setMessageText] = useState("");
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [usersFetchError, setUsersFetchError] = useState<string | null>(null);

    // Current admin user
    const currentUser = useUserStore((state) => state.user);

    const startHeartbeat = useOnlineStatusStore(state => state.startHeartbeat);

    console.log(usersFetchError);

    // Chat functionality using the updated hook
    // Fix: Always pass a stable value to useChat
    const selectedUserId = selectedUser?.id || "";
    const {
        messages,
        sendMessage,
        isLoading: chatLoading,
        error: chatError,
        loadMoreMessages,
        hasMoreMessages,
        connectionStatus,
        selectedFiles,
        addFile,
        removeFile,
        isPolling
    } = useChat(selectedUserId);

    // Start heartbeat to track online users
    useEffect(() => {
        startHeartbeat();
    }, [startHeartbeat]);

    // Fetch users with enhanced error handling
    useEffect(() => {
        const fetchUsers = async () => {
            try {
                const response = await axiosInstance.get("/admin/users");
                if (response.data && Array.isArray(response.data.data)) {
                    const newUsers = response.data.data;
                    setUsers(newUsers);
                    setUsersFetchError(null);

                    // Update selected user if it exists in the new data
                    setSelectedUser(prevSelected => {
                        if (prevSelected) {
                            const updatedSelectedUser = newUsers.find((u: { id: string; }) => u.id === prevSelected.id);
                            return updatedSelectedUser || prevSelected;
                        }
                        return prevSelected;
                    });
                }
            } catch (error) {
                console.error("Failed to fetch users:", error);
                setUsersFetchError("Failed to load users. Retrying...");
            } finally {
                if (isInitialLoading) {
                    setIsInitialLoading(false);
                }
            }
        };

        fetchUsers();

        const intervalId = setInterval(fetchUsers, 30000);
        return () => {
            clearInterval(intervalId);
        };
    }, []);

    // Handle sending a message
    const handleSendMessage = async () => {
        if (!messageText.trim() && selectedFiles.length === 0) return;
        if (!selectedUser) return;

        try {
            await sendMessage(messageText);
            setMessageText("");
        } catch (error) {
            console.error("Failed to send message:", error);
        }
    };

    // Handle user selection
    const handleUserSelect = (user: ChatUser) => {
        setSelectedUser(user);
        // Close mobile menu when user is selected
        setIsMobileMenuOpen(false);
        // Clear message text when switching users
        setMessageText("");
    };

    // Get connection status display
    const getConnectionStatus = () => {
        if (connectionStatus === 'connected') {
            return {
                text: 'Connected',
                className: 'text-green-500',
                icon: <span className="w-2 h-2 bg-green-500 rounded-full inline-block mr-2" />
            };
        } else if (isPolling) {
            return {
                text: 'Reconnecting...',
                className: 'text-yellow-500',
                icon: <span className="w-2 h-2 bg-yellow-500 rounded-full inline-block mr-2 animate-pulse" />
            };
        } else {
            return {
                text: 'Disconnected',
                className: 'text-red-500',
                icon: <span className="w-2 h-2 bg-red-500 rounded-full inline-block mr-2" />
            };
        }
    };

    const status = getConnectionStatus();

    return (
        <div className="h-screen flex flex-col bg-background">
            {/* Admin Chat Header */}
            <div className="flex items-center justify-between border-b border-border p-4 lg:hidden">
                <h1 className="text-lg font-semibold">Admin Chat</h1>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                    className="lg:hidden"
                >
                    {isMobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                </Button>
            </div>

            {/* Main Content */}
            <div className="flex-1 flex overflow-hidden">
                {/* Mobile Overlay */}
                {isMobileMenuOpen && (
                    <div
                        className="fixed inset-0 bg-background/80 backdrop-blur-sm z-40 lg:hidden"
                        onClick={() => setIsMobileMenuOpen(false)}
                    />
                )}

                {/* User List - Sidebar */}
                <div className={cn(
                    "fixed lg:relative inset-y-0 left-0 z-50 w-80 bg-background border-r border-border transform transition-transform duration-300 ease-in-out lg:transform-none",
                    isMobileMenuOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
                )}>
                    <UserList
                        users={users}
                        searchQuery={searchQuery}
                        setSearchQuery={setSearchQuery}
                        selectedTab={selectedTab}
                        setSelectedTab={setSelectedTab}
                        selectedUser={selectedUser}
                        setSelectedUser={handleUserSelect}
                        isInitialLoading={isInitialLoading}
                    />
                </div>

                {/* Chat Area */}
                <div className="flex-1 flex flex-col min-w-0">
                    {selectedUser ? (
                        <>
                            {/* Chat Header */}
                            <div className="flex items-center justify-between border-b border-border p-4 bg-card">
                                <div className="flex items-center min-w-0">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="lg:hidden mr-2 flex-shrink-0"
                                        onClick={() => setIsMobileMenuOpen(true)}
                                    >
                                        <Menu className="h-5 w-5" />
                                    </Button>
                                    <Avatar className="h-10 w-10 mr-3 flex-shrink-0">
                                        <AvatarImage src={selectedUser.avatar} alt={`${selectedUser.first_name} ${selectedUser.last_name}`} />
                                        <AvatarFallback className="bg-muted">
                                            {selectedUser.first_name[0]}{selectedUser.last_name[0]}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <div className="font-medium truncate">
                                            {selectedUser.first_name} {selectedUser.last_name}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {selectedUser.is_online ? 'Online' : 'Offline'}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className={`hidden sm:flex items-center text-xs ${status.className}`}>
                                        {status.icon}
                                        {status.text}
                                    </div>
                                    <div className="hidden sm:block text-sm text-muted-foreground flex-shrink-0">
                                        ID: {selectedUser.account_id}
                                    </div>
                                </div>
                            </div>

                            {/* Chat Messages */}
                            <div className="flex-1 overflow-y-auto p-4">
                                {chatLoading && messages.length === 0 ? (
                                    <div className="flex justify-center items-center h-full">
                                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    </div>
                                ) : (
                                    <ChatMessageList
                                        messages={messages}
                                        currentUserId={currentUser?.id || ""}
                                        isLoading={chatLoading}
                                        onLoadMore={loadMoreMessages}
                                        hasMoreMessages={hasMoreMessages}
                                    />
                                )}
                            </div>

                            {/* Chat Input */}
                            <div className="border-t border-border p-4 bg-card">
                                <ChatInput
                                    value={messageText}
                                    onChange={(e) => setMessageText(e.target.value)}
                                    onSend={handleSendMessage}
                                    disabled={false}
                                    onFileSelect={addFile}
                                    selectedFiles={selectedFiles}
                                    onFileRemove={removeFile}
                                />
                                {isPolling && (
                                    <div className="text-xs text-yellow-500 mt-1">
                                        Using backup connection - messages may be slightly delayed
                                    </div>
                                )}
                                {chatError && (
                                    <div className="mt-2 text-xs text-destructive">
                                        {chatError}
                                    </div>
                                )}
                            </div>
                        </>
                    ) : (
                        // No user selected placeholder
                        <div className="flex flex-col items-center justify-center h-full p-4 text-center">
                            <MessageCircle className="h-16 w-16 mb-4 text-muted-foreground" />
                            <h3 className="text-xl font-medium mb-2">Select a user to start chatting</h3>
                            <p className="text-muted-foreground max-w-md">
                                Choose a user from the list to view their conversation and send messages.
                            </p>
                            <Button
                                variant="outline"
                                className="mt-4 lg:hidden"
                                onClick={() => setIsMobileMenuOpen(true)}
                            >
                                Show Users
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

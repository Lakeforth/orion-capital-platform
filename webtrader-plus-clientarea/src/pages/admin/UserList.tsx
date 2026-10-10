import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Search, Loader2, MessageCircle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
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

interface UserListProps {
    users: ChatUser[];
    searchQuery: string;
    setSearchQuery: (query: string) => void;
    selectedTab: "all" | "online" | "unread";
    setSelectedTab: (tab: "all" | "online" | "unread") => void;
    selectedUser: ChatUser | null;
    setSelectedUser: (user: ChatUser) => void;
    isInitialLoading: boolean;
}

export function UserList({
                             users,
                             searchQuery,
                             setSearchQuery,
                             selectedTab,
                             setSelectedTab,
                             selectedUser,
                             setSelectedUser,
                             isInitialLoading,
                         }: UserListProps) {
    // const isUserOnline = useOnlineStatusStore(state => state.isUserOnline);

    // Filter users based on search and tab
    const filteredUsers = users.filter(user => {
        const matchesSearch = searchQuery === "" ||
            user.first_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            user.last_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.toLowerCase());

        let matchesTab = true;
        if (selectedTab === "online") {
            matchesTab = user.is_online;
        } else if (selectedTab === "unread") {
            matchesTab = (user.unread_count || 0) > 0;
        }

        return matchesSearch && matchesTab;
    });

    return (
        <div className="flex flex-col h-full bg-card">
            {/* Header */}
            <div className="p-4 border-b border-border space-y-4">
                <h2 className="text-lg font-semibold hidden lg:block">Admin Chat</h2>
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search users..."
                        className="pl-9 bg-background"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                    />
                </div>
                <Tabs
                    value={selectedTab}
                    onValueChange={(value) => setSelectedTab(value as "all" | "online" | "unread")}
                    className="w-full"
                >
                    <TabsList className="grid grid-cols-3">
                        <TabsTrigger value="all">All</TabsTrigger>
                        <TabsTrigger value="online">Online</TabsTrigger>
                        <TabsTrigger value="unread">Unread</TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            {/* User List */}
            <div className="flex-1 overflow-y-auto">
                {isInitialLoading ? (
                    <div className="flex flex-col items-center justify-center h-full p-4">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        <p className="mt-2 text-muted-foreground">Loading users...</p>
                    </div>
                ) : filteredUsers.length === 0 ? (
                    <div className="flex flex-col items-center justify-center h-full p-4">
                        <MessageCircle className="h-10 w-10 mb-2 text-muted-foreground" />
                        <p className="text-center text-muted-foreground">No users found</p>
                    </div>
                ) : (
                    <div className="divide-y divide-border">
                        {filteredUsers.map(user => (
                            <button
                                key={user.id}
                                className={cn(
                                    "w-full flex items-center p-4 hover:bg-accent/50 transition-colors",
                                    selectedUser?.id === user.id && "bg-accent"
                                )}
                                onClick={() => setSelectedUser(user)}
                            >
                                <div className="relative flex-shrink-0">
                                    <Avatar className="h-10 w-10">
                                        <AvatarImage src={user.avatar} alt={`${user.first_name} ${user.last_name}`} />
                                        <AvatarFallback className="bg-primary/10 text-primary">
                                            {user.first_name[0]}{user.last_name[0]}
                                        </AvatarFallback>
                                    </Avatar>
                                    {user.is_online && (
                                        <div className="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-background"></div>
                                    )}
                                </div>
                                <div className="ml-3 flex-1 min-w-0 text-left">
                                    <div className="flex items-center justify-between">
                                        <div className="font-medium truncate">
                                            {user.first_name} {user.last_name}
                                        </div>
                                        {user.unread_count && user.unread_count > 0 && (
                                            <Badge variant="destructive" className="ml-2">
                                                {user.unread_count}
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="text-sm text-muted-foreground truncate">
                                        {user.account_id} • {user.email}
                                    </div>
                                </div>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

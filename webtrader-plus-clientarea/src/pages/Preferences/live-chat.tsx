import { useState, useRef } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { ChatInput } from '@/components/chat/chat-input';
import ChatMessageList from '@/components/chat/ChatMessageList';
import { useChat } from '@/hooks/useChat';
import useUserStore from '@/store/userStore';
import WhatsAppButton from '@/components/chat/whatsapp-button';

/**
 * Live Chat page that provides real-time communication with support
 */
const LiveChat = () => {
    const [messageText, setMessageText] = useState('');
    const bottomRef = useRef<HTMLDivElement>(null);
    const user = useUserStore(state => state.user);

    // Always call useChat without parameters for regular user chat
    const {
        messages,
        isLoading,
        error,
        sendMessage,
        loadMoreMessages,
        hasMoreMessages,
        selectedFiles,
        addFile,
        removeFile,
        // isPolling
    } = useChat(); // No parameter for user chat

    const handleSendMessage = async () => {
        if (!messageText.trim() && selectedFiles.length === 0) return;

        await sendMessage(messageText);
        setMessageText('');

        // Scroll to bottom after sending
        setTimeout(() => {
            bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        }, 100);
    };


    // If user is not logged in, show a message
    if (!user) {
        return (
            <div className="container mx-auto py-6">
                <div className="flex flex-col h-[calc(100vh-120px)]">
                    <div className="mb-4">
                        <h1 className="text-2xl font-bold">Live Chat Support</h1>
                    </div>
                    <Card className="flex-1 flex items-center justify-center">
                        <CardContent className="text-center">
                            <p className="text-muted-foreground">
                                Please log in to access the chat support.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        );
    }

    return (
        <div className="container mx-auto py-6">
            <div className="flex flex-col h-[calc(100vh-120px)]">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold pl-5">Live Chat Support</h1>
                    <WhatsAppButton isFixed={false} />
                </div>

                <Card className="flex flex-col flex-1 overflow-hidden bg-card/50">
                    <CardContent className="flex flex-col h-full p-0">
                        <div className="flex-1 overflow-y-auto p-4">
                            {error && (
                                <div className="bg-red-500/20 text-red-500 p-4 mb-4 rounded-md">
                                    {error}
                                </div>
                            )}

                            <ChatMessageList
                                messages={messages}
                                currentUserId={user.id}
                                isLoading={isLoading}
                                onLoadMore={loadMoreMessages}
                                hasMoreMessages={hasMoreMessages}
                            />

                            <div ref={bottomRef} />
                        </div>

                        <div className="border-t border-border p-4">
                            <ChatInput
                                value={messageText}
                                onChange={(e) => setMessageText(e.target.value)}
                                onSend={handleSendMessage}
                                onFileSelect={addFile}
                                disabled={false}
                                selectedFiles={selectedFiles}
                                onFileRemove={removeFile}
                            />
                            {/*{isPolling && (*/}
                            {/*    <div className="text-xs text-yellow-500 mt-1">*/}
                            {/*        Using backup connection - messages may be slightly delayed*/}
                            {/*    </div>*/}
                            {/*)}*/}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default LiveChat;

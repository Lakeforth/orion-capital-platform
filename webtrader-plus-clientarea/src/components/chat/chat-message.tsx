import { Avatar } from "@/components/ui/avatar";
import { FileText, Copy } from "lucide-react";
import { cn } from "@/lib/utils";

interface Message {
  id: string;
  content: string;
  role: "agent" | "user";
  timestamp: string;
  attachment?: {
    type: string;
    name: string;
  };
}

interface ChatMessageProps {
  message: Message;
}

export function ChatMessage({ message }: ChatMessageProps) {
  const isUser = message.role === "user";

  return (
    <div className={cn("flex items-start gap-3", isUser && "flex-row-reverse")}>
      <Avatar className="h-10 w-10 bg-muted">
        <div className="h-full w-full rounded-full bg-muted flex items-center justify-center">
          <span className="text-muted-foreground text-xs">
            {isUser ? "U" : "A"}
          </span>
        </div>
      </Avatar>

      <div className={cn("max-w-[80%]", isUser ? "items-end" : "items-start")}>
        <div
          className={cn(
            "rounded-lg p-3",
            isUser
              ? "bg-primary/30 text-primary-foreground"
              : "bg-muted/50 text-foreground border border-border/40"
          )}
        >
          {message.attachment ? (
            <div className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              <a href="#" className="underline">
                {message.attachment.name}
              </a>
            </div>
          ) : (
            <p>{message.content}</p>
          )}
        </div>

        <div className="flex items-center mt-1 text-xs text-muted-foreground">
          <span>{message.timestamp}</span>
          {!isUser && (
            <button className="ml-2 opacity-50 hover:opacity-100">
              <Copy className="h-3 w-3" />
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

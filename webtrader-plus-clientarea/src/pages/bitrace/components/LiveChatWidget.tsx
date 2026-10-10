import { useEffect, useMemo, useRef, useState } from "react"
import {
  Download,
  Loader2,
  Lock,
  MessageCircle,
  Minus,
  Paperclip,
  Send,
  ShieldCheck,
  Smile,
  X,
} from "lucide-react"
import { format, isToday, parseISO } from "date-fns"
import { toast } from "@/components/ui/sonner"
import { useChat, type ChatMessage, type MessageAttachment } from "@/hooks/useChat"
import useUserStore from "@/store/userStore"

type Props = {
  onClose: () => void
  onMinimize: () => void
  isMinimized: boolean
}

const formatMessageTime = (iso: string) => {
  try {
    return format(parseISO(iso), "hh:mm a")
  } catch {
    return ""
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const formatDateLabel = (iso: string) => {
  try {
    const date = parseISO(iso)
    return isToday(date) ? "TODAY" : format(date, "MMM d, yyyy").toUpperCase()
  } catch {
    return "TODAY"
  }
}

const AttachmentCard = ({ attachment }: { attachment: MessageAttachment }) => {
  const isPdf = attachment.file_type === "application/pdf"
  const isImage = attachment.file_type.startsWith("image/")

  if (isImage) {
    return (
      <a
        href={attachment.download_url}
        target="_blank"
        rel="noopener noreferrer"
        className="mt-2 block rounded-lg overflow-hidden border border-[#E2E8F0]"
      >
        <img
          src={attachment.download_url}
          alt={attachment.file_name}
          className="max-h-32 w-full object-cover"
        />
      </a>
    )
  }

  return (
    <div className="mt-2 bg-white border border-[#E2E8F0] rounded-lg p-2.5 flex items-center gap-2.5">
      <div
        className={`w-9 h-9 rounded-md flex items-center justify-center text-[10px] font-bold shrink-0 ${
          isPdf ? "bg-red-100 text-red-600" : "bg-[#EFF6FF] text-[#2563EB]"
        }`}
      >
        {isPdf ? "PDF" : "FILE"}
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-[#0A1628] text-[12px] font-semibold truncate">
          {attachment.file_name}
        </p>
        <p className="text-[#94A3B8] text-[11px]">
          {formatFileSize(attachment.file_size)} • {isPdf ? "PDF" : "File"}
        </p>
      </div>
      <a
        href={attachment.download_url}
        download={attachment.file_name}
        target="_blank"
        rel="noopener noreferrer"
        className="w-8 h-8 rounded-lg bg-[#EFF6FF] flex items-center justify-center text-[#2563EB] hover:bg-[#DBEAFE] transition-colors shrink-0"
        aria-label={`Download ${attachment.file_name}`}
      >
        <Download className="w-4 h-4" />
      </a>
    </div>
  )
}

const MessageBubble = ({
  message,
  isClient,
}: {
  message: ChatMessage
  isClient: boolean
}) => (
  <div className={`flex gap-2 ${isClient ? "flex-row-reverse" : ""}`}>
    {!isClient && (
      <div className="w-7 h-7 rounded-full bg-[#0A1628] flex items-center justify-center shrink-0 mt-1">
        {message.sender?.avatar ? (
          <img
            src={message.sender.avatar}
            alt=""
            className="w-7 h-7 rounded-full object-cover"
          />
        ) : (
          <ShieldCheck className="w-3.5 h-3.5 text-[#2563EB]" />
        )}
      </div>
    )}
    <div className={`max-w-[78%] space-y-1 ${isClient ? "items-end" : ""}`}>
      {message.message && (
        <div
          className={`px-3.5 py-2.5 rounded-2xl text-[13px] leading-[1.55] ${
            isClient
              ? "bg-[#2563EB] text-white rounded-tr-sm"
              : "bg-white text-[#0A1628] border border-[#E2E8F0] rounded-tl-sm"
          }`}
        >
          <p className="whitespace-pre-wrap break-words">{message.message}</p>
          {message.attachments?.map((attachment) => (
            <AttachmentCard key={attachment.id} attachment={attachment} />
          ))}
        </div>
      )}
      {!message.message && message.attachments?.length > 0 && (
        <div className="space-y-1">
          {message.attachments.map((attachment) => (
            <AttachmentCard key={attachment.id} attachment={attachment} />
          ))}
        </div>
      )}
      <p
        className={`text-[10px] text-[#94A3B8] flex items-center gap-1 ${
          isClient ? "justify-end" : ""
        }`}
      >
        {formatMessageTime(message.created_at)}
        {isClient && (
          <span className={message.read_at ? "text-[#2563EB]" : "text-[#94A3B8]"}>
            {message.read_at ? "✓✓" : "✓"}
          </span>
        )}
      </p>
    </div>
  </div>
)

const LiveChatWidget = ({ onClose, onMinimize, isMinimized }: Props) => {
  const user = useUserStore((state) => state.user)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const bottomRef = useRef<HTMLDivElement>(null)
  const [input, setInput] = useState("")
  const [isSending, setIsSending] = useState(false)

  const {
    messages,
    isLoading,
    error,
    connectionStatus,
    sendMessage,
    loadMoreMessages,
    hasMoreMessages,
    selectedFiles,
    addFile,
    removeFile,
    isPolling,
  } = useChat()

  const agent = useMemo(() => {
    const adminMessage = [...messages].reverse().find((msg) => msg.is_admin)
    if (adminMessage?.sender) {
      return {
        name: adminMessage.sender.name ||
          `${adminMessage.sender.first_name} ${adminMessage.sender.last_name}`.trim(),
        title: "Senior Case Analyst",
        avatar: adminMessage.sender.avatar,
      }
    }
    return {
      name: "Case Manager",
      title: "Senior Case Analyst",
      avatar: null as string | null,
    }
  }, [messages])

  const groupedMessages = useMemo(() => {
    const groups: { label: string; items: ChatMessage[] }[] = []
    messages.forEach((message) => {
      const label = formatDateLabel(message.created_at)
      const last = groups[groups.length - 1]
      if (last?.label === label) {
        last.items.push(message)
      } else {
        groups.push({ label, items: [message] })
      }
    })
    return groups
  }, [messages])

  const isOnline =
    connectionStatus === "connected" || isPolling || connectionStatus === "connecting"

  useEffect(() => {
    if (!isMinimized) {
      bottomRef.current?.scrollIntoView({ behavior: "smooth" })
    }
  }, [messages, isMinimized, selectedFiles.length])

  const handleSend = async () => {
    const text = input.trim()
    if (!text && selectedFiles.length === 0) return
    if (!user) {
      toast.error("Please log in to send messages")
      return
    }

    setIsSending(true)
    try {
      await sendMessage(text)
      setInput("")
    } catch {
      toast.error("Failed to send message")
    } finally {
      setIsSending(false)
    }
  }

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) addFile(file)
    e.target.value = ""
  }

  if (isMinimized) {
    return (
      <button
        type="button"
        onClick={onMinimize}
        className="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-[#0A1628] text-white px-4 py-3 rounded-full shadow-2xl hover:bg-[#1E293B] transition-colors"
        aria-label="Restore live chat"
      >
        <span className="relative">
          <MessageCircle className="w-5 h-5" />
          <span className="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-[#22C55E] border-2 border-[#0A1628]" />
        </span>
        <span className="text-[13px] font-semibold pr-1">Live Chat</span>
      </button>
    )
  }

  return (
    <div
      className="fixed bottom-6 right-6 z-50 w-[min(100vw-2rem,380px)] rounded-2xl shadow-2xl overflow-hidden border border-[#E2E8F0] flex flex-col"
      style={{ maxHeight: "min(640px, calc(100vh - 3rem))" }}
      role="dialog"
      aria-label="Live support chat"
    >
      <div className="bg-[#0A1628] px-4 py-3 flex items-center gap-3 shrink-0">
        <div className="relative">
          {agent.avatar ? (
            <img
              src={agent.avatar}
              alt=""
              className="w-10 h-10 rounded-full object-cover"
            />
          ) : (
            <div className="w-10 h-10 rounded-full bg-[#2563EB] flex items-center justify-center text-white text-[14px] font-bold">
              {agent.name[0]}
            </div>
          )}
          <span
            className={`absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-[#0A1628] ${
              isOnline ? "bg-[#22C55E]" : "bg-[#94A3B8]"
            }`}
          />
        </div>
        <div className="flex-1 min-w-0">
          <p className="text-white text-[13px] font-semibold leading-tight truncate">
            {agent.name}
          </p>
          <p className="text-[#94A3B8] text-[11px]">
            {agent.title} • {isOnline ? "Online" : "Reconnecting"}
          </p>
        </div>
        <button
          type="button"
          onClick={onMinimize}
          className="text-[#94A3B8] hover:text-white transition-colors p-1"
          aria-label="Minimise chat"
        >
          <Minus className="w-4 h-4" />
        </button>
        <button
          type="button"
          onClick={onClose}
          className="text-[#94A3B8] hover:text-white transition-colors p-1"
          aria-label="Close chat"
        >
          <X className="w-4 h-4" />
        </button>
      </div>

      <div className="bg-[#F8FAFC] flex-1 overflow-y-auto px-4 py-4 space-y-4 min-h-[320px]">
        {isLoading && messages.length === 0 && (
          <div className="flex items-center justify-center h-full">
            <Loader2 className="w-6 h-6 animate-spin text-[#2563EB]" />
          </div>
        )}

        {!isLoading && hasMoreMessages && messages.length > 0 && (
          <div className="flex justify-center">
            <button
              type="button"
              onClick={() => loadMoreMessages()}
              className="text-[#2563EB] text-[11px] font-semibold hover:underline"
            >
              Load earlier messages
            </button>
          </div>
        )}

        {error && (
          <div className="bg-[#FEF2F2] border border-[#FECACA] text-[#991B1B] text-[12px] rounded-lg px-3 py-2">
            {error}
          </div>
        )}

        {!isLoading && messages.length === 0 && !error && (
          <div className="text-center py-8">
            <p className="text-[#0A1628] text-[14px] font-semibold mb-1">
              Start a secure conversation
            </p>
            <p className="text-[#6B7A8D] text-[12px]">
              Your case manager will respond as soon as possible.
            </p>
          </div>
        )}

        {groupedMessages.map((group) => (
          <div key={group.label} className="space-y-4">
            <div className="flex justify-center">
              <span className="bg-[#E2E8F0] text-[#64748B] text-[11px] font-medium px-3 py-1 rounded-full">
                {group.label}
              </span>
            </div>
            {group.items.map((message) => (
              <MessageBubble
                key={message.id}
                message={message}
                isClient={message.sender_id === user?.id}
              />
            ))}
          </div>
        ))}

        {connectionStatus === "connecting" && (
          <p className="text-[#94A3B8] text-[11px] flex items-center gap-1.5">
            <span className="flex gap-0.5">
              <span className="w-1.5 h-1.5 rounded-full bg-[#94A3B8] animate-bounce" />
              <span className="w-1.5 h-1.5 rounded-full bg-[#94A3B8] animate-bounce [animation-delay:0.15s]" />
              <span className="w-1.5 h-1.5 rounded-full bg-[#94A3B8] animate-bounce [animation-delay:0.3s]" />
            </span>
            Connecting to support...
          </p>
        )}

        <div ref={bottomRef} />
      </div>

      <div className="bg-white border-t border-[#E2E8F0] px-3 py-2.5 shrink-0">
        {selectedFiles.length > 0 && (
          <div className="mb-2 flex flex-wrap gap-2">
            {selectedFiles.map((entry, index) => (
              <div
                key={`${entry.file.name}-${index}`}
                className="flex items-center gap-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg px-2 py-1 text-[11px]"
              >
                <span className="text-[#0A1628] max-w-[120px] truncate">
                  {entry.file.name}
                </span>
                <button
                  type="button"
                  onClick={() => removeFile(index)}
                  className="text-[#94A3B8] hover:text-[#64748B]"
                  aria-label="Remove attachment"
                >
                  <X className="w-3 h-3" />
                </button>
              </div>
            ))}
          </div>
        )}

        <div className="flex items-end gap-2 border border-[#E2E8F0] rounded-xl px-2 py-1.5 bg-white focus-within:ring-2 focus-within:ring-[#2563EB]/20 focus-within:border-[#2563EB]">
          <input
            ref={fileInputRef}
            type="file"
            className="hidden"
            accept="image/*,.pdf,.doc,.docx"
            onChange={handleFileChange}
          />
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            className="text-[#94A3B8] hover:text-[#64748B] p-1.5 shrink-0"
            aria-label="Attach file"
          >
            <Paperclip className="w-4 h-4" />
          </button>
          <textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Type a secure message..."
            rows={1}
            className="flex-1 text-[13px] text-[#0A1628] placeholder:text-[#94A3B8] outline-none bg-transparent resize-none max-h-24 py-1.5"
          />
          <button
            type="button"
            className="text-[#94A3B8] hover:text-[#64748B] p-1.5 shrink-0"
            aria-label="Emoji picker"
            tabIndex={-1}
          >
            <Smile className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={handleSend}
            disabled={isSending || (!input.trim() && selectedFiles.length === 0)}
            className="w-9 h-9 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-50 disabled:cursor-not-allowed rounded-lg flex items-center justify-center transition-colors shrink-0"
            aria-label="Send message"
          >
            {isSending ? (
              <Loader2 className="w-4 h-4 text-white animate-spin" />
            ) : (
              <Send className="w-4 h-4 text-white" />
            )}
          </button>
        </div>

        <p className="text-[#94A3B8] text-[10px] text-center mt-2 flex items-center justify-center gap-1">
          <Lock className="w-3 h-3" />
          {connectionStatus === "connected"
            ? "End-to-end encrypted connection"
            : isPolling
              ? "Secure connection (backup mode)"
              : "Establishing secure connection"}
        </p>
      </div>
    </div>
  )
}

export default LiveChatWidget

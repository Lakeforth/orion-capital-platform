import { MessageCircle } from "lucide-react"
import useUserStore from "@/store/userStore"
import useBitraceSupportWidgetStore from "@/store/bitraceSupportWidgetStore"
import LiveChatWidget from "./LiveChatWidget"

const BitraceSupportWidget = () => {
  const token = useUserStore((state) => state.token)
  const { isOpen, isMinimized, open, close, minimize, restore } =
    useBitraceSupportWidgetStore()

  if (!token) {
    return null
  }

  if (isOpen) {
    return (
      <LiveChatWidget
        isMinimized={isMinimized}
        onClose={close}
        onMinimize={isMinimized ? restore : minimize}
      />
    )
  }

  return (
    <button
      type="button"
      onClick={open}
      className="fixed bottom-6 right-6 z-50 w-14 h-14 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-full shadow-lg shadow-[#2563EB]/30 flex items-center justify-center transition-all hover:scale-105"
      aria-label="Open live support chat"
    >
      <MessageCircle className="w-6 h-6" />
    </button>
  )
}

export default BitraceSupportWidget

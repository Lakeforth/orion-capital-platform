import { MessageCircle } from "lucide-react"

type Props = {
  onOpenChat: () => void
}

const CaseCommunicationsTab = ({ onOpenChat }: Props) => {
  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center max-w-lg">
      <div className="w-14 h-14 rounded-full bg-[#EBF2FF] flex items-center justify-center mx-auto mb-4">
        <MessageCircle className="w-7 h-7 text-[#2563EB]" />
      </div>
      <h2 className="text-[#0A1628] text-[17px] font-bold mb-2">Communications</h2>
      <p className="text-[#6B7A8D] text-[14px] mb-6 leading-relaxed">
        Chat with your case manager through our secure live chat. All messages are
        handled confidentially.
      </p>
      <button
        type="button"
        onClick={onOpenChat}
        className="px-5 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-[13px] font-semibold rounded-lg transition-colors"
      >
        Open Live Chat
      </button>
    </div>
  )
}

export default CaseCommunicationsTab

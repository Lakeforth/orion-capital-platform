import { FileText, MessageSquare } from "lucide-react"
import { openBitraceSupportChat } from "@/store/bitraceSupportWidgetStore"

interface Props {
  onLiveChat?: () => void
}

const UnderInvestigationPhase = ({ onLiveChat }: Props) => (
  <div className="space-y-4">
    <div className="bg-[#EBF2FF] border border-[#BFDBFE] rounded-xl p-5 flex items-start gap-4">
      <div className="w-10 h-10 rounded-full bg-[#2563EB] flex items-center justify-center shrink-0">
        <FileText className="w-5 h-5 text-white" />
      </div>
      <div className="flex-1">
        <h3 className="text-[#0A1628] text-[14px] font-bold mb-1">
          Tracing Your Assets
        </h3>
        <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
          Our specialists are currently tracing your funds through the blockchain
          network to identify the recipient wallets.
        </p>
      </div>
      <button
        type="button"
        onClick={onLiveChat ?? openBitraceSupportChat}
        className="shrink-0 inline-flex items-center gap-2 px-4 py-2 border border-[#2563EB] text-[#2563EB] text-[13px] font-semibold rounded-lg hover:bg-[#EBF2FF] transition-colors"
        aria-label="Open live chat"
      >
        <MessageSquare className="w-4 h-4" />
        Live Chat
      </button>
    </div>

    <div className="bg-white border border-[#EEF1F5] rounded-xl p-5">
      <h4 className="text-[#0A1628] text-[13px] font-bold mb-1">
        Need to share more information?
      </h4>
      <p className="text-[#6B7A8D] text-[12px] leading-[1.6] mb-3">
        If you have additional transaction IDs or wallet screenshots, contact your
        case manager through live chat or support.
      </p>
      <button
        type="button"
        onClick={openBitraceSupportChat}
        className="text-[#2563EB] text-[13px] font-semibold hover:underline"
      >
        Open live chat →
      </button>
    </div>
  </div>
)

export default UnderInvestigationPhase

import { ShieldCheck } from "lucide-react"

const RecoveryPhase = () => (
  <div className="space-y-4">
    <div className="bg-[#EBF2FF] border border-[#BFDBFE] rounded-xl p-5 flex items-start gap-4">
      <div className="w-10 h-10 rounded-full bg-[#2563EB] flex items-center justify-center shrink-0">
        <ShieldCheck className="w-5 h-5 text-white" />
      </div>
      <div>
        <h3 className="text-[#0A1628] text-[14px] font-bold mb-1">
          Recovery Phase
        </h3>
        <p className="text-[#6B7A8D] text-[12px] leading-[1.6]">
          Funds have been successfully traced. Your case manager will coordinate
          the recovery and transfer process on your behalf.
        </p>
      </div>
    </div>

    <div className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5">
      <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1">
        Managed by your case manager
      </p>
      <p className="text-[#6B7A8D] text-[12px] leading-relaxed">
        Fund transfers are handled by our recovery team. You will be notified when
        the next step requires your action.
      </p>
    </div>
  </div>
)

export default RecoveryPhase

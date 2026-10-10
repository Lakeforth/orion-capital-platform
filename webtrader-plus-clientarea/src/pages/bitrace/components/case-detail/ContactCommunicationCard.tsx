import { CheckCircle2, Info } from "lucide-react"
import useUserStore from "@/store/userStore"
import { BITRACE_PROFILE_PATH } from "../../bitrace-routes"
import BitraceLink from "../BitraceLink"

const ContactCommunicationCard = () => {
  const user = useUserStore((state) => state.user)
  const email = user?.email
  const phone = user?.phone

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-5">
      <h2 className="text-[#0A1628] text-[15px] font-bold mb-1">
        Contact &amp; Communication
      </h2>
      <p className="text-[#94A3B8] text-[12px] mb-4">
        We may contact you for more information
      </p>

      <div className="space-y-4 mb-4">
        <div>
          <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1">
            Primary Email
          </p>
          <div className="flex items-center justify-between gap-2">
            <span className="text-[#0A1628] text-[13px] font-semibold truncate">
              {email ?? "Not set"}
            </span>
            {email && (
              <span className="inline-flex items-center gap-1 text-[#22C55E] text-[11px] font-medium shrink-0">
                <CheckCircle2 className="w-3.5 h-3.5" />
                Verified
              </span>
            )}
          </div>
        </div>

        <div>
          <p className="text-[#94A3B8] text-[10px] font-semibold tracking-[0.08em] uppercase mb-1">
            Phone Number
          </p>
          <div className="flex items-center justify-between gap-2">
            <span className="text-[#0A1628] text-[13px] font-semibold">
              {phone ?? "Not set"}
            </span>
            {phone && (
              <span className="inline-flex items-center gap-1 text-[#22C55E] text-[11px] font-medium shrink-0">
                <CheckCircle2 className="w-3.5 h-3.5" />
                Verified
              </span>
            )}
          </div>
        </div>
      </div>

      <BitraceLink
        to={BITRACE_PROFILE_PATH}
        className="text-[#2563EB] text-[12px] font-semibold hover:underline mb-4 inline-block"
      >
        Update profile →
      </BitraceLink>

      <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-lg p-3 flex gap-2.5">
        <Info className="w-4 h-4 text-[#2563EB] shrink-0 mt-0.5" />
        <p className="text-[#6B7A8D] text-[11px] leading-[1.5]">
          Please keep your contact details up to date so our team can reach you
          regarding your case progress.
        </p>
      </div>
    </div>
  )
}

export default ContactCommunicationCard

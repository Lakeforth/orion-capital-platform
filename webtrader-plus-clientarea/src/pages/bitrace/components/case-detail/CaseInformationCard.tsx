import { BitraceCaseDetail } from "@/services/bitrace/bitrace-types"
import {
  formatBitraceDate,
  getBitraceStatusBadgeClass,
  getBitraceStatusLabel,
} from "../../utils/bitrace-mappers"

type CaseInformationCardProps = {
  caseData: BitraceCaseDetail
  currentWallet?: string
}

const CaseInformationCard = ({ caseData, currentWallet }: CaseInformationCardProps) => {
  const walletAddress = currentWallet || caseData.eth_wallet_address

  const fields = [
    { label: "Case ID", value: caseData.case_number },
    { label: "Category", value: caseData.title },
    { label: "Submitted On", value: formatBitraceDate(caseData.created_at) },
    { label: "Status", value: caseData.status, badge: true },
    { label: "Last Updated", value: formatBitraceDate(caseData.updated_at) },
    { label: "Current Stage", value: caseData.current_stage ?? "Case Opened" },
  ]

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-5">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-[#0A1628] text-[15px] font-bold">
          Case Information
        </h2>
      </div>

      <div className="space-y-3.5">
        {fields.map((field) => (
          <div key={field.label} className="flex items-center justify-between gap-3">
            <span className="text-[#94A3B8] text-[12px]">{field.label}</span>
            {field.badge ? (
              <span
                className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${getBitraceStatusBadgeClass(caseData.status)}`}
              >
                {getBitraceStatusLabel(caseData.status)}
              </span>
            ) : (
              <span className="text-[#0A1628] text-[12px] font-semibold text-right">
                {field.value}
              </span>
            )}
          </div>
        ))}

        {walletAddress && (
          <div className="pt-3 border-t border-[#EEF1F5]">
            <p className="text-[#94A3B8] text-[12px] mb-1">ETH Wallet</p>
            <p className="text-[#0A1628] text-[11px] font-semibold break-all">
              {walletAddress}
            </p>
          </div>
        )}
      </div>
    </div>
  )
}

export default CaseInformationCard

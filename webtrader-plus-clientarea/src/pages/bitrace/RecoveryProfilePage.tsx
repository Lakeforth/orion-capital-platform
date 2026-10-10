import BitraceAppLayout from "./layout/BitraceAppLayout"
import WalletAddressSection from "./components/WalletAddressSection"
import ProfileWithdrawSection from "./components/ProfileWithdrawSection"
import ProfilePersonalDetailsCard from "./components/ProfilePersonalDetailsCard"
import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"

const RecoveryProfilePage = () => {
  const { data: cases = [] } = useBitraceCasesQuery()
  const primaryOpenCaseId = cases.find(
    (c) => c.status === "open" || c.status === "in_progress",
  )?.id

  return (
    <BitraceAppLayout activeSidebarLabel="Profile">
      <div className="max-w-5xl space-y-6">
        <div>
          <h1 className="text-[#0A1628] text-[28px] font-bold mb-2">
            Account Settings
          </h1>
          <p className="text-[#6B7A8D] text-[14px] max-w-2xl">
            Manage your recovery profile, security protocols, and communication
            preferences.
          </p>
        </div>

        <ProfilePersonalDetailsCard />

        <WalletAddressSection caseId={primaryOpenCaseId} />

        <ProfileWithdrawSection />
      </div>
    </BitraceAppLayout>
  )
}

export default RecoveryProfilePage

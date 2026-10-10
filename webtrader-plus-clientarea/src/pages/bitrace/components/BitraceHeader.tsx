import { Bell, ChevronDown, Menu, Search } from "lucide-react"
import ReactCountryFlag from "react-country-flag"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import useUserStore from "@/store/userStore"

type BitraceHeaderProps = {
  onMenuClick?: () => void
}

const BitraceHeader = ({ onMenuClick }: BitraceHeaderProps) => {
  const user = useUserStore((state) => state.user)
  const firstName = user?.first_name || "John"
  const lastName = user?.last_name || "Doe"
  const fullName = `${firstName} ${lastName}`
  const email = user?.email || "johndoe@example.com"

  return (
    <header className="bg-white border-b border-[#E8ECF1] sticky top-0 z-30">
      <div className="px-4 md:px-6 lg:px-8">
        <div className="flex items-center justify-between md:justify-end h-[64px] gap-6">
          <button
            type="button"
            onClick={onMenuClick}
            className="w-9 h-9 flex items-center justify-center rounded-full text-[#5A6577] hover:bg-[#F4F6F9] transition-colors md:hidden"
            aria-label="Toggle sidebar"
          >
            <Menu className="w-[18px] h-[18px]" />
          </button>

          <div className="flex items-center gap-3 shrink-0 ml-auto">
            <button
              type="button"
              className="w-9 h-9 flex items-center justify-center rounded-full text-[#5A6577] hover:bg-[#F4F6F9] transition-colors"
              aria-label="Search"
            >
              <Search className="w-[18px] h-[18px]" />
            </button>

            <button
              type="button"
              className="w-9 h-9 flex items-center justify-center rounded-full text-[#5A6577] hover:bg-[#F4F6F9] transition-colors relative"
              aria-label="Notifications"
            >
              <Bell className="w-[18px] h-[18px]" />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-[#EF4444] rounded-full border border-white" />
            </button>

            <button
              type="button"
              className="w-9 h-9 flex items-center justify-center rounded-full overflow-hidden hover:opacity-80 transition-opacity"
              aria-label="Change language"
            >
              <ReactCountryFlag
                countryCode="GB"
                svg
                style={{ width: "22px", height: "22px", borderRadius: "50%" }}
              />
            </button>

            <div className="flex items-center gap-2.5 pl-2 border-l border-[#E8ECF1]">
              <Avatar className="w-9 h-9">
                <AvatarFallback className="bg-[#D1D5DB] text-[#4B5563] text-xs font-medium">
                  {firstName.charAt(0)}
                  {lastName.charAt(0)}
                </AvatarFallback>
              </Avatar>
              <div className="hidden md:block">
                <p className="text-[#0A1628] text-[13px] font-semibold leading-tight">
                  {fullName}
                </p>
                <p className="text-[#8B95A5] text-[11px] leading-tight">
                  {email}
                </p>
              </div>
              <ChevronDown className="w-4 h-4 text-[#8B95A5] hidden md:block" />
            </div>
          </div>
        </div>
      </div>
    </header>
  )
}

export default BitraceHeader

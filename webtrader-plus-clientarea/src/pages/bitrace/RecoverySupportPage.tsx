import { MessageCircle, Mail, Phone } from "lucide-react"
import BitraceAppLayout from "./layout/BitraceAppLayout"
import useSiteSettingsStore from "@/store/siteSettingStore"
import { openBitraceSupportChat } from "@/store/bitraceSupportWidgetStore"
import WhatsAppButton from "@/components/chat/whatsapp-button"

const RecoverySupportPage = () => {
  const settings = useSiteSettingsStore((state) => state.settings)

  const contactEmail = settings?.contactEmail
  const contactPhone = settings?.contact_phone
  const hasWhatsApp = settings?.enable_whatsapp_chat && settings?.whatsapp_number

  return (
    <BitraceAppLayout activeSidebarLabel="Support">
      <div className="max-w-3xl space-y-6">
        <div>
          <h1 className="text-[#0A1628] text-[22px] font-bold mb-1">Support</h1>
          <p className="text-[#6B7A8D] text-[14px]">
            Our recovery team is here to help you through every step of your case.
          </p>
        </div>

        <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)] p-6 space-y-4">
          <button
            type="button"
            onClick={openBitraceSupportChat}
            className="w-full flex items-center justify-center gap-2 px-5 py-3 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-[14px] font-semibold rounded-lg transition-colors"
          >
            <MessageCircle className="w-5 h-5" />
            Open Live Chat
          </button>

          <div className="grid gap-3 sm:grid-cols-2">
            {contactEmail && (
              <a
                href={`mailto:${contactEmail}`}
                className="flex items-center gap-3 p-4 border border-[#EEF1F5] rounded-xl hover:bg-[#F8FAFC] transition-colors"
              >
                <Mail className="w-5 h-5 text-[#2563EB]" />
                <div>
                  <p className="text-[#94A3B8] text-[10px] font-semibold uppercase">Email</p>
                  <p className="text-[#0A1628] text-[13px] font-semibold break-all">
                    {contactEmail}
                  </p>
                </div>
              </a>
            )}
            {contactPhone && (
              <a
                href={`tel:${contactPhone}`}
                className="flex items-center gap-3 p-4 border border-[#EEF1F5] rounded-xl hover:bg-[#F8FAFC] transition-colors"
              >
                <Phone className="w-5 h-5 text-[#2563EB]" />
                <div>
                  <p className="text-[#94A3B8] text-[10px] font-semibold uppercase">Phone</p>
                  <p className="text-[#0A1628] text-[13px] font-semibold">{contactPhone}</p>
                </div>
              </a>
            )}
          </div>

          {hasWhatsApp && (
            <div className="pt-2">
              <WhatsAppButton isFixed={false} />
            </div>
          )}
        </div>

        <div className="bg-[#F0F7FF] border border-[#BFDBFE] rounded-xl p-5">
          <h2 className="text-[#0A1628] text-[14px] font-bold mb-2">Frequently asked</h2>
          <ul className="space-y-3 text-[#6B7A8D] text-[13px] leading-relaxed">
            <li>
              <strong className="text-[#0A1628]">How long does recovery take?</strong>
              <br />
              Timelines vary by case complexity. Your case manager will update you at each phase.
            </li>
            <li>
              <strong className="text-[#0A1628]">Why do you need my wallet address?</strong>
              <br />
              We use your Ethereum address to route recovered funds securely to you.
            </li>
            <li>
              <strong className="text-[#0A1628]">What is a test transfer?</strong>
              <br />
              A small transfer to confirm your bank details work before sending remaining funds.
            </li>
          </ul>
        </div>
      </div>
    </BitraceAppLayout>
  )
}

export default RecoverySupportPage

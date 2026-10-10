import { ArrowLeft } from "lucide-react";
import { Link } from "react-router-dom";
import useSiteSettingsStore from "@/store/siteSettingStore";

const sections = [
  {
    title: "1. Information we collect",
    body: (
      <>
        <p>We may collect information you provide when you create or use an account, make a deposit or withdrawal, verify your identity, contact support, or otherwise use our services. This may include:</p>
        <ul>
          <li>Identity and contact details, such as your name, email address, telephone number, date of birth, address, nationality, and country of residence.</li>
          <li>Account and profile details, including login credentials, preferences, trading-account status, and communications with us.</li>
          <li>Identity-verification information, such as identification documents, photographs, proof of address, and verification results where required.</li>
          <li>Transaction information, including purchases, payment status, wallet details, payout requests, and related records. Complete payment-card details may be collected directly by our payment providers rather than by us.</li>
          <li>Trading and platform activity, including orders, account performance, risk metrics, device events, and actions taken within our services.</li>
          <li>Technical and usage information, such as IP address, browser and device type, operating system, referring pages, timestamps, cookies, and diagnostic data.</li>
        </ul>
      </>
    ),
  },
  {
    title: "2. How we use information",
    body: <p>We use personal information to provide and administer our services; create, secure, and support accounts; process purchases and payouts; perform identity, fraud, sanctions, eligibility, and compliance checks; enforce challenge rules and our agreements; communicate service messages and respond to support requests; improve performance and user experience; prevent misuse and security incidents; maintain records; comply with legal obligations; establish or defend legal claims; and, where permitted, send marketing communications that you can opt out of.</p>,
  },
  {
    title: "3. Legal grounds for processing",
    body: <p>Depending on your location and the circumstances, we process information because it is necessary to perform a contract with you, comply with a legal obligation, protect vital interests, pursue our legitimate interests or those of another party, or because you have given consent. Where we rely on consent, you may withdraw it at any time, without affecting processing already carried out.</p>,
  },
  {
    title: "4. How we share information",
    body: <p>We may share information with companies and contractors that help us operate the service, including hosting, analytics, communications, customer-support, identity-verification, fraud-prevention, payment, banking, trading-platform, and professional-advisory providers. We may also share information with regulators, courts, law-enforcement bodies, counterparties, or other persons when required by law, necessary to protect rights or safety, or connected with a merger, financing, reorganisation, sale, or transfer of all or part of our business. We do not sell personal information for money.</p>,
  },
  {
    title: "5. International transfers",
    body: <p>Our providers and operations may process information in countries other than your own. Where applicable law requires it, we use recognised safeguards for international transfers, such as contractual protections, adequacy decisions, or another lawful transfer mechanism.</p>,
  },
  {
    title: "6. Cookies and similar technologies",
    body: <p>We may use cookies and similar technologies to keep you signed in, remember preferences, protect accounts, understand service usage, diagnose problems, and measure communications or campaigns. You can control many cookies through your browser, although disabling essential cookies may prevent parts of the service from working.</p>,
  },
  {
    title: "7. Data retention",
    body: <p>We retain personal information for as long as reasonably necessary for the purposes described in this policy, including providing services, maintaining transaction and compliance records, resolving disputes, preventing fraud, and meeting legal, accounting, tax, or reporting requirements. Retention periods vary according to the type of information and applicable obligations. We securely delete or anonymise information when it is no longer required.</p>,
  },
  {
    title: "8. Security",
    body: <p>We use reasonable administrative, organisational, and technical safeguards designed to protect personal information. No internet transmission or storage system is completely secure, so we cannot guarantee absolute security. You are responsible for keeping your credentials confidential and notifying us promptly if you suspect unauthorised account access.</p>,
  },
  {
    title: "9. Your privacy rights",
    body: <p>Depending on where you live, you may have rights to request access to, correction of, deletion of, restriction on, or portability of your personal information; object to certain processing; withdraw consent; opt out of marketing; or lodge a complaint with a data-protection authority. Some rights are subject to legal exceptions. We may need to verify your identity before acting on a request.</p>,
  },
  {
    title: "10. Children",
    body: <p>Our services are not directed to anyone under 18, and we do not knowingly collect personal information from children. If you believe a child has provided information to us, please contact us so that we can review and delete it where appropriate.</p>,
  },
  {
    title: "11. Third-party services",
    body: <p>Our services may link to third-party websites, payment services, or platforms. Their privacy practices are governed by their own policies, and we are not responsible for those practices.</p>,
  },
  {
    title: "12. Changes to this policy",
    body: <p>We may update this policy to reflect changes to our services, practices, or legal obligations. We will post the revised policy on this page and update the effective date. Where required, we will provide additional notice.</p>,
  },
];

export default function PrivacyPolicyPage() {
  const settings = useSiteSettingsStore((state) => state.settings);
  const companyName = settings?.name || "Trivon Funded";
  const contactEmail = settings?.contactEmail;
  const contactPhone = settings?.contact_phone;

  return (
    <main className="min-h-screen bg-[#F8FAFC] text-[#334155] dark:bg-[#0A1728] dark:text-[#CBD5E1]">
      <header className="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-[#0E1E35]">
        <div className="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-5">
          <Link to="/" className="inline-flex items-center gap-2 text-sm font-medium text-[#1583F0] hover:underline">
            <ArrowLeft size={16} /> Back to {companyName}
          </Link>
          {settings?.logo && <img src={settings.logo} alt={companyName} className="h-9 w-auto" />}
        </div>
      </header>

      <article className="mx-auto max-w-4xl px-6 py-12 sm:py-16">
        <p className="mb-3 text-sm font-semibold uppercase tracking-wider text-[#1583F0]">Legal</p>
        <h1 className="text-3xl font-extrabold tracking-tight text-[#0F172A] dark:text-white sm:text-4xl">Privacy Policy</h1>
        <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">Effective date: September 8, 2026</p>

        <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-6 leading-7 shadow-sm dark:border-white/10 dark:bg-[#0E1E35] sm:p-8">
          <p>{companyName} (referred to as “{companyName},” “we,” “us,” or “our”) respects your privacy. This policy explains how we collect, use, disclose, retain, and protect personal information when you visit our websites, create an account, use a trading account or related platform feature, communicate with us, or otherwise use our services.</p>
          <p className="mt-4">By using our services, you acknowledge the practices described in this policy. This policy should be read together with any terms and notices presented when information is collected.</p>

          <div className="mt-10 space-y-9 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-[#0F172A] dark:[&_h2]:text-white [&_p]:leading-7 [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-6">
            {sections.map((section) => (
              <section key={section.title}>
                <h2>{section.title}</h2>
                {section.body}
              </section>
            ))}

            <section>
              <h2>13. Contact us</h2>
              <p>If you have questions, wish to exercise a privacy right, or want to raise a concern about this policy, contact {companyName} through the platform’s support channel{contactEmail ? <> or by email at <a className="text-[#1583F0] hover:underline" href={`mailto:${contactEmail}`}>{contactEmail}</a></> : null}{contactPhone ? <> or by telephone at <a className="text-[#1583F0] hover:underline" href={`tel:${contactPhone}`}>{contactPhone}</a></> : null}.</p>
            </section>
          </div>
        </div>
      </article>
    </main>
  );
}

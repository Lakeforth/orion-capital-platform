import { useState } from "react";
import { Copy, Check } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { cn } from "@/lib/utils";

function ClientCard({
  email,
  password,
  isLight,
  onFillEmail,
  onFillPassword,
}: {
  email: string;
  password: string;
  isLight: boolean;
  onFillEmail?: (value: string) => void;
  onFillPassword?: (value: string) => void;
}) {
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    onFillEmail?.(email);
    onFillPassword?.(password);

    const fullText = `Email: ${email}\nPassword: ${password}`;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(fullText).then(() => {
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      });
    } else {
      const textArea = document.createElement("textarea");
      textArea.value = fullText;
      document.body.appendChild(textArea);
      textArea.select();
      try {
        document.execCommand("copy");
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      } catch (err) {
        console.error("Fallback: Oops, unable to copy", err);
      }
      document.body.removeChild(textArea);
    }
  };

  return (
    <motion.div
      whileHover={{ y: -1 }}
      onClick={handleCopy}
      className={cn(
        "relative flex items-center justify-between p-4 rounded-2xl border transition-all shadow-sm cursor-pointer group",
        isLight
          ? "bg-white border-[#F1F5F9] hover:border-blue-100 shadow-slate-200/50"
          : "bg-[#1A1A1A] border-white/5 hover:border-white/10 shadow-black/20",
      )}
    >
      <div className="flex flex-col gap-2 w-full">
        <div className="flex items-center gap-2">
          <span
            className={cn(
              "px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider w-12 text-center",
              isLight ? "bg-[#0F172A] text-white" : "bg-white text-black",
            )}
          >
            Email
          </span>
          <span
            className={cn(
              "text-[12px] font-semibold tracking-tight truncate",
              isLight ? "text-slate-600" : "text-white/80",
            )}
          >
            {email}
          </span>
        </div>
        <div className="flex items-center gap-2">
          <span
            className={cn(
              "px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider w-12 text-center",
              isLight ? "bg-[#0F172A] text-white" : "bg-white text-black",
            )}
          >
            Pass
          </span>
          <span
            className={cn(
              "text-[12px] font-semibold tracking-tight truncate",
              isLight ? "text-slate-600" : "text-white/80",
            )}
          >
            {password}
          </span>
        </div>
      </div>

      <div
        className={cn(
          "p-2.5 rounded-xl transition-all flex-shrink-0 ml-4",
          isLight
            ? "bg-slate-50 text-slate-400 group-hover:bg-slate-100 group-hover:text-slate-600"
            : "bg-white/5 text-white/30 group-hover:bg-white/10 group-hover:text-white/60",
        )}
      >
        <AnimatePresence mode="wait">
          {copied ? (
            <motion.div
              key="check"
              initial={{ scale: 0.5, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.5, opacity: 0 }}
            >
              <Check className="w-4 h-4 text-green-500" />
            </motion.div>
          ) : (
            <motion.div
              key="copy"
              initial={{ scale: 0.5, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.5, opacity: 0 }}
            >
              <Copy className="w-4 h-4" />
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </motion.div>
  );
}

export default function DemoCredentialsBanner({
  onFillEmail,
  onFillPassword,
}: {
  onFillEmail?: (value: string) => void;
  onFillPassword?: (value: string) => void;
}) {
  const settings = useSiteSettingsStore((state) => state.settings);

  if (!settings?.is_demo || !settings.demo?.frontend_login) return null;

  const { email, password } = settings.demo.frontend_login;
  const loginType = settings?.l_page ?? "ic";
  const isLight = loginType === "ic" || loginType === "propfirm_light";

  return (
    <div className="w-full mt-8 font-sans">
      <div className="flex items-center justify-between mb-4 px-1">
        <div className="flex flex-col gap-1">
          <h3
            className={cn(
              "text-[11px] font-black uppercase tracking-[0.2em]",
              isLight ? "text-slate-500" : "text-white/40",
            )}
          >
            Demo Access
          </h3>
          <p
            className={cn(
              "text-[13px] font-medium",
              isLight ? "text-slate-400" : "text-white/20",
            )}
          >
            Click to auto-fill credentials.
          </p>
        </div>

        <div
          className={cn(
            "px-3 py-1 rounded-lg",
            isLight ? "bg-red-50" : "bg-red-500/10",
          )}
        >
          <span
            className={cn(
              "text-[10px] font-black uppercase tracking-wider",
              isLight ? "text-red-600" : "text-red-400",
            )}
          >
            Demo
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1">
        <ClientCard
          email={email}
          password={password}
          isLight={isLight}
          onFillEmail={onFillEmail}
          onFillPassword={onFillPassword}
        />
      </div>
    </div>
  );
}

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Link, useNavigate } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { AxiosError } from "axios";
import { toast } from "@/components/ui/sonner";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { Link2, Users, Eye, EyeOff } from "lucide-react";
import terminalDarkBg from "@/assets/webtrader-login-terminal-dark-bg.jpg";

const formSchema = z.object({
    trading_account_id: z.string().min(1, { message: "Enter your trading account ID" }),
    password: z.string().min(1, { message: "Password is required" }),
});

/**
 * "Trading Terminal (Dark)" webtrader login theme -- same terminal-style
 * "Connect to account" card as WebtraderLoginTerminal.tsx, just a moodier
 * dark background overlay + footer instead of the light v1. Selected via
 * wt_l_page === "terminal_dark".
 */
export default function WebtraderLoginTerminalDark() {
    const [isLoading, setIsLoading] = useState(false);
    const [savePassword, setSavePassword] = useState(true);
    const [showPassword, setShowPassword] = useState(false);
    const [tab, setTab] = useState<"connect" | "open">("connect");
    const navigate = useNavigate();
    const settings = useSiteSettingsStore((state) => state.settings);
    const tenantName = settings?.name || "Trading Terminal";
    const year = new Date().getFullYear();
    const contactHref = settings?.help_url || (settings?.contactEmail ? `mailto:${settings.contactEmail}` : "#");

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: { trading_account_id: "", password: "" },
    });

    async function onSubmit(values: z.infer<typeof formSchema>) {
        setIsLoading(true);
        try {
            const response = await axiosInstance.post("/auth/trading-account/login", values);
            const { user, token, trading_account } = response.data.data;
            // Direct login on the webtrader form itself, not launched from
            // the client area -- no dashboard to go back to (see
            // viaClientAreaLaunch on useUserStore).
            replaceAuthenticatedUser(user, token, trading_account, false);
            navigate("/");
        } catch (error) {
            if (error instanceof AxiosError) {
                toast.error(error.response?.data?.message || "Invalid trading account ID or password.");
            } else {
                toast.error("An unexpected error occurred. Please try again.");
            }
        } finally {
            setIsLoading(false);
        }
    }

    const tabBase = "flex items-center gap-2 px-4 py-2.5 text-sm text-left w-full";

    return (
        <div className="relative w-full min-h-screen overflow-hidden font-sans text-[#2b2b2b]">
            <div
                className="absolute inset-0 bg-cover bg-center scale-105"
                style={{ backgroundImage: `url(${terminalDarkBg})`, filter: "blur(2.5px) brightness(0.9)" }}
                aria-hidden="true"
            />
            <div className="absolute inset-0 bg-[rgba(10,14,22,0.55)]" aria-hidden="true" />

            <div className="absolute inset-0 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <div className="w-full max-w-[592px] bg-white rounded shadow-[0_24px_70px_rgba(0,0,0,0.5)] overflow-hidden my-auto">
                    <div className="bg-[#3a7ff2] text-white text-[13px] px-[18px] py-[11px] flex flex-wrap items-center justify-between gap-3">
                        <span>Trading accounts: {tenantName}</span>
                        {settings?.logo && (
                            <img src={settings.logo} alt={tenantName} className="h-7 w-[118px] object-contain shrink-0" />
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-[208px_1fr]">
                        <div className="flex flex-col border-b sm:border-b-0 sm:border-r border-[#ececec] sm:min-h-[218px]">
                            <button
                                type="button"
                                onClick={() => setTab("connect")}
                                className={tabBase + (tab === "connect" ? " text-[#2a6fd6] bg-[#eaf2fe]" : " text-[#444]")}
                            >
                                <Link2 className="h-4 w-4" />
                                <span>Connect to account</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setTab("open")}
                                className={tabBase + (tab === "open" ? " text-[#2a6fd6] bg-[#eaf2fe]" : " text-[#444]")}
                            >
                                <Users className="h-4 w-4" />
                                <span>Open Real account</span>
                            </button>
                            <div className="mt-auto px-4 pt-4 pb-[18px] text-[11px] text-[#9a9a9a] leading-relaxed">
                                <div>© {year} {tenantName}</div>
                                {settings?.terms_url && (
                                    <a href={settings.terms_url} className="text-[11px] text-[#2a6fd6] hover:underline">
                                        Terms of Service
                                    </a>
                                )}
                            </div>
                        </div>

                        <div className="px-[22px] pt-4 pb-[22px] min-w-0">
                            {tab === "connect" ? (
                                <>
                                    <div className="text-[15px] text-[#333] mb-[18px]">Connect to account</div>
                                    <form onSubmit={form.handleSubmit(onSubmit)}>
                                        <div className="grid grid-cols-1 sm:grid-cols-[74px_1fr] gap-y-1.5 sm:gap-y-3.5 gap-x-2 items-start sm:items-center">
                                            <label className="text-[13px] text-[#555]">Account ID</label>
                                            <input
                                                type="text"
                                                inputMode="numeric"
                                                placeholder="Enter Account ID"
                                                {...form.register("trading_account_id")}
                                                className="w-full min-w-0 text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                            />

                                            <label className="text-[13px] text-[#555] mt-2 sm:mt-0">Password</label>
                                            <div className="flex flex-wrap items-center gap-x-3.5 gap-y-2 min-w-0">
                                                <div className="relative flex-1 min-w-0">
                                                    <input
                                                        type={showPassword ? "text" : "password"}
                                                        placeholder="Enter Password"
                                                        {...form.register("password")}
                                                        className="w-full min-w-0 text-[13px] text-[#333] pl-2 pr-8 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => setShowPassword((v) => !v)}
                                                        tabIndex={-1}
                                                        aria-label={showPassword ? "Hide password" : "Show password"}
                                                        className="absolute right-1.5 top-1/2 -translate-y-1/2 text-[#9a9a9a] hover:text-[#555]"
                                                    >
                                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                    </button>
                                                </div>
                                                <label className="flex items-center gap-1.5 text-[13px] text-[#555] cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={savePassword}
                                                        onChange={(e) => setSavePassword(e.target.checked)}
                                                        className="h-3.5 w-3.5 accent-[#3a7ff2] cursor-pointer shrink-0"
                                                    />
                                                    Save password
                                                </label>
                                            </div>

                                            <div className="hidden sm:block" />
                                            <div className="text-xs text-[#8a8a8a] -mt-1">
                                                Forgot Password?{" "}
                                                <Link to="/forgot-password" className="text-[#2a6fd6] hover:underline">
                                                    Reset it
                                                </Link>
                                            </div>
                                        </div>

                                        {(form.formState.errors.trading_account_id || form.formState.errors.password) && (
                                            <p className="text-xs text-red-500 mt-2">
                                                {form.formState.errors.trading_account_id?.message || form.formState.errors.password?.message}
                                            </p>
                                        )}

                                        <div className="flex justify-end mt-[26px]">
                                            <button
                                                type="submit"
                                                disabled={isLoading}
                                                className="bg-[#33b25d] hover:bg-[#2ea355] text-white rounded-[3px] px-[18px] py-[9px] text-[13px] disabled:opacity-60"
                                            >
                                                {isLoading ? "Connecting…" : "Connect to account"}
                                            </button>
                                        </div>
                                    </form>
                                </>
                            ) : (
                                <>
                                    <div className="text-[15px] text-[#333] mb-[18px]">Open Real account</div>
                                    <p className="text-[13px] text-[#555] leading-relaxed">
                                        New trading accounts are set up by {tenantName}. Please{" "}
                                        <a href={contactHref} className="text-[#2a6fd6] hover:underline">
                                            contact your account manager
                                        </a>{" "}
                                        to open a new live or demo account.
                                    </p>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="absolute left-0 right-0 bottom-0 bg-[rgba(15,20,30,0.85)] border-t border-[rgba(255,255,255,0.08)] text-[#aeb6c2] text-[11px] font-semibold px-3.5 py-2.5 tracking-wide">
                © {year} {tenantName.toUpperCase()}
            </div>
        </div>
    );
}

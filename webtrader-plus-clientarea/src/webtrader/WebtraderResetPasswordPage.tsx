import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { AxiosError } from "axios";
import axiosInstance from "@/lib/axios";
import { toast } from "@/components/ui/sonner";
import useSiteSettingsStore from "@/store/siteSettingStore";
import terminalDarkBg from "@/assets/webtrader-login-terminal-dark-bg.jpg";

const formSchema = z
    .object({
        email: z.string().email({ message: "Enter a valid email address" }),
        token: z.string().min(1, { message: "Enter the code from your email" }),
        trading_account_id: z
            .string()
            .min(1, { message: "Enter the account ID you log into webtrader with" })
            .regex(/^\d+$/, { message: "Account ID is numbers only" }),
        password: z.string().min(8, { message: "Password must be at least 8 characters" }),
        password_confirmation: z.string(),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: "Passwords do not match",
        path: ["password_confirmation"],
    });

/**
 * Step 2 of the webtrader password-recovery flow. Calls the existing
 * POST /auth/reset-password endpoint -- the same platform-wide Laravel
 * password broker used everywhere else, just previously missing any
 * frontend page to actually reach it.
 *
 * Requires the trading account ID being recovered, not just email: a user
 * can own several TradingAccount rows, each with its own independently
 * generated password (see TradingAccount::booted()). Resetting by email
 * alone would have no way to know which one they meant, and the backend
 * used to silently reset every account they own to the same new value --
 * fixed to only touch the one account named here (see
 * AuthController::resetPassword()).
 */
export default function WebtraderResetPasswordPage() {
    const [isLoading, setIsLoading] = useState(false);
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const settings = useSiteSettingsStore((state) => state.settings);
    const tenantName = settings?.name || "Trading Terminal";

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            email: searchParams.get("email") ?? "",
            token: searchParams.get("token") ?? "",
            trading_account_id: searchParams.get("account_id") ?? "",
            password: "",
            password_confirmation: "",
        },
    });

    async function onSubmit(values: z.infer<typeof formSchema>) {
        setIsLoading(true);
        try {
            const response = await axiosInstance.post("/auth/reset-password", values);
            toast.success(response.data.message || "Password reset successfully.");
            navigate("/login");
        } catch (error) {
            if (error instanceof AxiosError) {
                toast.error(error.response?.data?.message || "Could not reset your password.");
            } else {
                toast.error("An unexpected error occurred. Please try again.");
            }
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="relative w-full min-h-screen overflow-hidden font-sans text-[#2b2b2b]">
            <div
                className="absolute inset-0 bg-cover bg-center scale-105"
                style={{ backgroundImage: `url(${terminalDarkBg})`, filter: "blur(2.5px) brightness(0.9)" }}
                aria-hidden="true"
            />
            <div className="absolute inset-0 bg-[rgba(10,14,22,0.55)]" aria-hidden="true" />

            <div className="absolute inset-0 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <div className="w-full max-w-[420px] bg-white rounded shadow-[0_24px_70px_rgba(0,0,0,0.5)] overflow-hidden my-auto">
                    <div className="bg-[#3a7ff2] text-white text-[13px] px-[18px] py-[11px]">
                        <span>{tenantName} — Reset Password</span>
                    </div>

                    <div className="px-[22px] pt-5 pb-[22px]">
                        <div className="text-[15px] text-[#333] mb-3">Set a new password</div>
                        <p className="text-[13px] text-[#555] leading-relaxed mb-5">
                            Enter the code we emailed you, the trading account you log into webtrader with, and
                            your new password. Only that one account's password changes — any other accounts you
                            have keep working with their current password.
                        </p>

                        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-3.5">
                            <div>
                                <label className="text-[13px] text-[#555] block mb-1.5">Email</label>
                                <input
                                    type="email"
                                    placeholder="name@example.com"
                                    {...form.register("email")}
                                    className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                />
                                {form.formState.errors.email && (
                                    <p className="text-xs text-red-500 mt-1">{form.formState.errors.email.message}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-[13px] text-[#555] block mb-1.5">Trading account ID</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    placeholder="The account ID you log into webtrader with"
                                    {...form.register("trading_account_id")}
                                    className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                />
                                {form.formState.errors.trading_account_id && (
                                    <p className="text-xs text-red-500 mt-1">
                                        {form.formState.errors.trading_account_id.message}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="text-[13px] text-[#555] block mb-1.5">Reset code</label>
                                <input
                                    type="text"
                                    placeholder="Enter the code from your email"
                                    {...form.register("token")}
                                    className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                />
                                {form.formState.errors.token && (
                                    <p className="text-xs text-red-500 mt-1">{form.formState.errors.token.message}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-[13px] text-[#555] block mb-1.5">New password</label>
                                <input
                                    type="password"
                                    placeholder="At least 8 characters"
                                    {...form.register("password")}
                                    className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                />
                                {form.formState.errors.password && (
                                    <p className="text-xs text-red-500 mt-1">{form.formState.errors.password.message}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-[13px] text-[#555] block mb-1.5">Confirm new password</label>
                                <input
                                    type="password"
                                    placeholder="Re-enter your new password"
                                    {...form.register("password_confirmation")}
                                    className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                />
                                {form.formState.errors.password_confirmation && (
                                    <p className="text-xs text-red-500 mt-1">
                                        {form.formState.errors.password_confirmation.message}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={isLoading}
                                className="w-full mt-1.5 bg-[#33b25d] hover:bg-[#2ea355] text-white rounded-[3px] px-[18px] py-[9px] text-[13px] disabled:opacity-60"
                            >
                                {isLoading ? "Resetting…" : "Reset password"}
                            </button>
                        </form>

                        <div className="text-xs text-[#8a8a8a] mt-4">
                            <Link to="/login" className="text-[#2a6fd6] hover:underline">
                                Back to login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

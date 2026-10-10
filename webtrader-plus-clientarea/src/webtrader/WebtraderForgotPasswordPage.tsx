import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Link, useNavigate } from "react-router-dom";
import { AxiosError } from "axios";
import axiosInstance from "@/lib/axios";
import { toast } from "@/components/ui/sonner";
import useSiteSettingsStore from "@/store/siteSettingStore";
import terminalDarkBg from "@/assets/webtrader-login-terminal-dark-bg.jpg";

const formSchema = z.object({
    email: z.string().email({ message: "Enter a valid email address" }),
});

/**
 * Step 1 of the webtrader password-recovery flow: request a reset code by
 * email. Reuses the platform's existing POST /auth/forgot-password endpoint
 * (already sends a code + link via TenantResetPassword) -- no new backend
 * request-step needed. See WebtraderResetPasswordPage for step 2.
 */
export default function WebtraderForgotPasswordPage() {
    const [isLoading, setIsLoading] = useState(false);
    const [sent, setSent] = useState(false);
    const navigate = useNavigate();
    const settings = useSiteSettingsStore((state) => state.settings);
    const tenantName = settings?.name || "Trading Terminal";

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: { email: "" },
    });

    async function onSubmit(values: z.infer<typeof formSchema>) {
        setIsLoading(true);
        try {
            const response = await axiosInstance.post("/auth/forgot-password", values);
            toast.success(response.data.message || "Check your email for a reset code");
            setSent(true);
        } catch (error) {
            if (error instanceof AxiosError) {
                toast.error(error.response?.data?.message || "Could not process that request.");
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
                        <span>{tenantName} — Forgot Password</span>
                    </div>

                    <div className="px-[22px] pt-5 pb-[22px]">
                        {sent ? (
                            <>
                                <div className="text-[15px] text-[#333] mb-3">Check your email</div>
                                <p className="text-[13px] text-[#555] leading-relaxed mb-5">
                                    If an account exists for that address, we've sent a reset code and link. Enter the code on the
                                    next screen to set a new password.
                                </p>
                                <button
                                    type="button"
                                    onClick={() => navigate("/reset-password")}
                                    className="w-full bg-[#33b25d] hover:bg-[#2ea355] text-white rounded-[3px] px-[18px] py-[9px] text-[13px]"
                                >
                                    I have my code
                                </button>
                            </>
                        ) : (
                            <>
                                <div className="text-[15px] text-[#333] mb-3">Reset your password</div>
                                <p className="text-[13px] text-[#555] leading-relaxed mb-5">
                                    Enter the email on your account and we'll send you a code to reset your trading password.
                                </p>
                                <form onSubmit={form.handleSubmit(onSubmit)}>
                                    <label className="text-[13px] text-[#555] block mb-1.5">Email</label>
                                    <input
                                        type="email"
                                        placeholder="name@example.com"
                                        {...form.register("email")}
                                        className="w-full text-[13px] text-[#333] px-2 py-1.5 border border-[#d6d9de] rounded-[3px] outline-none bg-white focus:border-[#3a7ff2]"
                                    />
                                    {form.formState.errors.email && (
                                        <p className="text-xs text-red-500 mt-1.5">{form.formState.errors.email.message}</p>
                                    )}

                                    <button
                                        type="submit"
                                        disabled={isLoading}
                                        className="w-full mt-5 bg-[#33b25d] hover:bg-[#2ea355] text-white rounded-[3px] px-[18px] py-[9px] text-[13px] disabled:opacity-60"
                                    >
                                        {isLoading ? "Sending…" : "Send reset code"}
                                    </button>
                                </form>
                            </>
                        )}

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

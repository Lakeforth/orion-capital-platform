import { useState } from "react";
import { Eye, EyeOff, Globe, Mail, Lock } from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useNavigate } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { AxiosError } from "axios";
import { toast } from "@/components/ui/sonner";
import { OTPVerification } from "@/components/auth/OTPVerification";

import { Link } from "react-router-dom";
import Logo from "@/components/Logo.tsx";
import useSiteSettingsStore from "@/store/siteSettingStore.ts";
import DemoCredentialsBanner from "@/components/auth/DemoCredentialsBanner";

const formSchema = z.object({
    email: z.string().email({ message: "Please enter a valid email address" }),
    password: z.string().min(1, { message: "Password is required" }),
});

export default function ICMarketLogin() {
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [requireOTP, setRequireOTP] = useState(false);
    const [loginCredentials, setLoginCredentials] = useState<{
        email: string;
        password: string;
    } | null>(null);

    const navigate = useNavigate();

    const settings = useSiteSettingsStore((state) => state.settings);


    // Initialize form
    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            email: "",
            password: "",
        },
    });

    const handleOTPVerification = async (code: string) => {
        if (!loginCredentials) return;

        try {
            const response = await axiosInstance.post("/auth/login", {
                ...loginCredentials,
                otp: code,
            });

            toast.success("Login successful! Redirecting...");

            const { user, token } = response.data.data;

            if (user.notification_msg) {
                toast.error(user.notification_msg, {
                    duration: 10000,
                    position: "top-right",
                    className: "bg-red-500 text-white border-red-600",
                    style: {
                        backgroundColor: "rgb(239, 68, 68)",
                        color: "white",
                        border: "1px solid rgb(220, 38, 38)"
                    }
                });
            }

            replaceAuthenticatedUser(user, token);

            setTimeout(() => {
                navigate("/main");
            }, 1000);
        } catch (error) {
            console.error("OTP verification failed:", error);
            if (error instanceof AxiosError) {
                toast.error(error.response?.data?.message || "Invalid security code");
            } else {
                toast.error("An unexpected error occurred. Please try again.");
            }
            throw error;
        }
    };

    // Form submission handler
    async function onSubmit(values: z.infer<typeof formSchema>) {
        setIsLoading(true);
        try {
            const response = await axiosInstance.post("/auth/login", values);

            if (response.data.data.require_otp) {
                setRequireOTP(true);
                setLoginCredentials(values);
                toast.success("Security code sent to your email");
                return;
            }

            // Show success message
            toast.success("Login successful! Redirecting...");

            // Store the user object and token in Zustand store
            const { user, token } = response.data.data;
            replaceAuthenticatedUser(user, token);

            // Short delay before redirect to show the success message
            setTimeout(() => {
                navigate("/main");
            }, 1000);
        } catch (error) {
            console.error("Login failed:", error);

            if (error instanceof AxiosError) {
                if (error.response?.data?.message) {
                    toast.error(error.response.data.message);
                } else if (error.message === "Network Error") {
                    toast.error(
                        "Unable to connect to the server. Please check your internet connection."
                    );
                } else {
                    toast.error(error.message);
                }
            } else if (error instanceof Error) {
                toast.error(error.message);
            } else {
                toast.error("An unexpected error occurred. Please try again.");
            }
        } finally {
            setIsLoading(false);
        }
    }

    if (requireOTP && loginCredentials) {
        return (
            <div className="min-h-screen bg-black flex items-center justify-center p-4">
                <OTPVerification
                    email={loginCredentials.email}
                    onVerify={handleOTPVerification}
                />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-black flex flex-col items-center justify-center p-4">
            {/* Top green border */}
            <div className="absolute top-0 left-0 right-0 h-1 bg-green-500"></div>

            <div className="w-full max-w-md bg-[#151515] rounded p-8 relative">
                {/* Language selector */}
                <div className="absolute top-4 right-4 text-white flex items-center space-x-1">
                    <Globe className="h-4 w-4" />
                    <span className="text-sm">EN</span>
                    <span>▼</span>
                </div>

                {/* Logo and title */}
                <div className="text-center mb-8 py-10">
                    <div className="flex items-center justify-center mb-4">
                        <Logo variant="login" />
                    </div>

                    <p className="text-white text-sm">Sign in to Secure Client Area</p>
                </div>

                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                    {/* Email field */}
                    <div className="relative">
                        <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 h-5 w-5" />
                        <input
                            type="email"
                            placeholder="Email"
                            {...form.register("email")}
                            className="w-full bg-[#222222] border-0 rounded px-10 py-3 text-white placeholder-gray-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        />
                        {form.formState.errors.email && (
                            <p className="text-red-500 text-xs mt-1">{form.formState.errors.email.message}</p>
                        )}
                    </div>

                    {/* Password field */}
                    <div className="relative">
                        <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 h-5 w-5" />
                        <input
                            type={showPassword ? "text" : "password"}
                            placeholder="Password"
                            {...form.register("password")}
                            className="w-full bg-[#222222] border-0 rounded px-10 py-3 text-white placeholder-gray-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                        >
                            {showPassword ? (
                                <EyeOff className="h-5 w-5" />
                            ) : (
                                <Eye className="h-5 w-5" />
                            )}
                        </button>
                        {form.formState.errors.password && (
                            <p className="text-red-500 text-xs mt-1">{form.formState.errors.password.message}</p>
                        )}
                    </div>

                    {/* Forgot password */}
                    <div className="text-right">
                        <Link to="/forgot-password" className="text-green-500 text-sm hover:underline">
                            Forgot Password?
                        </Link>
                    </div>

                    {/* Login button */}
                    <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full bg-green-500 hover:bg-green-600 text-black font-bold py-3 rounded transition-colors"
                    >
                        {isLoading ? "LOGGING IN..." : "LOGIN"}
                    </button>
                </form>

                <DemoCredentialsBanner
                    onFillEmail={(v) => form.setValue("email", v)}
                    onFillPassword={(v) => form.setValue("password", v)}
                />

                {/* Account links */}
                <div className="mt-8 text-center">
                    <p className="text-white text-sm mb-2">Don't have an account?</p>
                    <div className="text-sm">
                        <Link to="/signup" className="text-white hover:text-green-500">Create Account</Link>
                        {/*<span className="text-white mx-2">|</span>*/}
                        {/*<Link to="/signup" className="text-white hover:text-green-500">Open a Real Account</Link>*/}
                    </div>
                </div>
            </div>

            {/* Trustpilot rating */}
            <div className="mt-8 text-white text-center">
                <div className="flex items-center justify-center gap-2">
                    <span className="text-sm">Our customers say</span>
                    <span className="font-bold mr-2">Excellent</span>
                    <div className="flex">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <div key={star} className="w-5 h-5 bg-green-500 flex items-center justify-center text-white text-xs mx-0.5">★</div>
                        ))}
                    </div>
                    <span className="text-sm">4.8 out of 5 based on 44,986 reviews</span>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 0L11.4 5.73L17.5 6.75L13.25 11.27L14.4 17.5L9 14.53L3.6 17.5L4.75 11.27L0.5 6.75L6.6 5.73L9 0Z" fill="#00B67A"/>
                    </svg>
                    <span className="font-bold">Trustpilot</span>
                </div>
            </div>

            {/* Risk warning */}
            { settings?.login_page_footer ? (
                <div className="mt-4 text-gray-500 text-xs max-w-5xl text-center">
                    <p>
                        { settings?.login_page_footer }
                    </p>
                </div>
            ) : (
                <div className="mt-4 text-gray-500 text-xs max-w-5xl text-center">
                    <p>
                        Risk warning: Trading Derivatives carries a high level of risk to your capital and you should only trade with money you can afford to lose. Trading Derivatives may not be suitable for all investors, so please ensure that you fully understand the risks involved and seek independent advice if necessary. Raw Spread accounts offer spreads from 0.0 pips with a commission charge of USD $3.50 per 100k traded. Standard account offers spreads from 0.8 pips with no additional commission charges. Spreads on CFD indices start at 0.4 points.
                    </p>
                </div>
            ) }

        </div>
    );
}

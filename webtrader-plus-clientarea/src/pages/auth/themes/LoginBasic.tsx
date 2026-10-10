import { useState } from "react";
import { Eye, EyeOff, User } from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useNavigate } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { AxiosError } from "axios";
import { toast } from "@/components/ui/sonner";
import { OTPVerification } from "@/components/auth/OTPVerification";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from "@/components/ui/card";
import AnimatedBackground from "@/pages/auth/animated-background";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Link } from "react-router-dom";
import DemoCredentialsBanner from "@/components/auth/DemoCredentialsBanner";

// Define the form schema with validation
const formSchema = z.object({
    email: z.string().email({ message: "Please enter a valid email address" }),
    password: z.string().min(1, { message: "Password is required" }),
});

export default function LoginBasic() {
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [requireOTP, setRequireOTP] = useState(false);
    const [loginCredentials, setLoginCredentials] = useState<{
        email: string;
        password: string;
    } | null>(null);

    const navigate = useNavigate();

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
            <div className="relative min-h-screen flex items-center justify-center p-4">
                <AnimatedBackground />
                <OTPVerification
                    email={loginCredentials.email}
                    onVerify={handleOTPVerification}
                />
            </div>
        );
    }

    return (
        <div className="relative min-h-screen flex items-center justify-center p-4">
            <AnimatedBackground />

            <Card className="w-full max-w-md bg-card/90 backdrop-blur-md border-border shadow-xl z-10 relative">
                <CardHeader className="space-y-1 text-center pb-2">
                    <div className="flex items-center justify-center gap-2">
                        <User className="h-6 w-6 text-card-foreground" />
                        <h1 className="text-xl font-bold tracking-wider text-card-foreground">
                            LOGIN TO TRADEROOM
                        </h1>
                    </div>
                    <div className="text-3xl font-bold text-primary tracking-tight">
                        $ 402.54
                    </div>
                </CardHeader>
                <CardContent className="space-y-4 pt-4">
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                            <FormField
                                control={form.control}
                                name="email"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">
                                            Email address
                                        </FormLabel>
                                        <FormControl>
                                            <Input
                                                type="email"
                                                placeholder="name@example.com"
                                                {...field}
                                                className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-primary"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <FormField
                                control={form.control}
                                name="password"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">
                                            Password
                                        </FormLabel>
                                        <div className="relative">
                                            <FormControl>
                                                <Input
                                                    type={showPassword ? "text" : "password"}
                                                    placeholder="••••••••"
                                                    {...field}
                                                    className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-primary pr-10"
                                                />
                                            </FormControl>
                                            <button
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-card-foreground"
                                                aria-label={
                                                    showPassword ? "Hide password" : "Show password"
                                                }
                                            >
                                                {showPassword ? (
                                                    <EyeOff className="h-5 w-5" />
                                                ) : (
                                                    <Eye className="h-5 w-5" />
                                                )}
                                            </button>
                                        </div>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <Button
                                type="submit"
                                className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-6"
                                disabled={isLoading}
                            >
                                {isLoading ? (
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                        Signing in...
                                    </div>
                                ) : (
                                    "SIGN IN"
                                )}
                            </Button>
                        </form>
                    </Form>
                    <DemoCredentialsBanner
                        onFillEmail={(v) => form.setValue("email", v)}
                        onFillPassword={(v) => form.setValue("password", v)}
                    />
                </CardContent>
                <CardFooter className="flex flex-col space-y-3 pt-0">
                    <div className="flex items-center justify-center space-x-2 text-sm text-card-foreground/80">
                        <span>Forgot Password?</span>
                        <Link
                            to="/forgot-password"
                            className="text-primary hover:text-primary/90 font-medium"
                        >
                            Restore
                        </Link>
                    </div>
                    <div className="flex items-center justify-center space-x-2 text-sm text-card-foreground/80">
                        <span>Don&apos;t have an account?</span>
                        <Link
                            to="/register"
                            className="text-primary hover:text-primary/90 font-medium"
                        >
                            Register
                        </Link>
                    </div>
                </CardFooter>
            </Card>
        </div>
    );
}

import { useState, useEffect } from "react";
import { Eye, EyeOff, Pencil } from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useNavigate, Link, useSearchParams } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { toast } from "@/components/ui/sonner";
import { AxiosError } from "axios";
import AnimatedBackground from "@/pages/auth/animated-background.tsx";
import { countries } from "@/constants/countries";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";

const formSchema = z.object({
    first_name: z.string().min(2, { message: "First name must be at least 2 characters" }),
    last_name: z.string().min(2, { message: "Last name must be at least 2 characters" }),
    username: z.string().min(3, { message: "Username must be at least 3 characters" }),
    phone: z.string().min(6, { message: "Please enter a valid phone number" }),
    country: z.string().min(1, { message: "Please select a country" }),
    password: z.string().min(8, { message: "Password must be at least 8 characters" }),
});

interface RegisterBasicProps {
    postRegisterPath?: string;
}

export default function RegisterBasic({ postRegisterPath = "/main" }: RegisterBasicProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();

    // Handle potential non-standard URL format: ?campaign=xyz?source=telegram
    const getParam = (name: string) => {
        const val = searchParams.get(name);
        if (val && val.includes("?")) {
            return val.split("?")[0];
        }
        return val;
    };

    const campaign = getParam("campaign") || "";
    const source = getParam("source") || "";

    useEffect(() => {
        const data = localStorage.getItem('registration_data');
        if (!data) {
            toast.error("Please complete the registration process first");
            navigate('/signup');
        }
    }, [navigate]);

    const stored = (() => {
        try { return JSON.parse(localStorage.getItem('registration_data') ?? '{}'); }
        catch { return {}; }
    })();

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            first_name: "",
            last_name: "",
            username: "",
            phone: "",
            country: stored.country ?? "",
            password: stored.password ?? "",
        },
    });

    async function onSubmit(values: z.infer<typeof formSchema>) {
        const raw = localStorage.getItem('registration_data');
        if (!raw) {
            toast.error("Missing registration data. Please start over.");
            navigate('/signup');
            return;
        }

        const savedData = JSON.parse(raw);

        if (!savedData.email) {
            toast.error("Missing registration data. Please start over.");
            navigate('/signup');
            return;
        }

        setIsLoading(true);
        try {
            const response = await axiosInstance.post("/auth/register", {
                ...values,
                email: savedData.email.trim(),
                confirm_password: values.password,
                nationality: savedData.nationality ?? values.country,
                country_code: "+1",
                campaign: campaign,
                source: source,
            });

            localStorage.removeItem('registration_data');

            const { user: newUser, token: newToken } = response.data.data;
            replaceAuthenticatedUser(newUser, newToken);
            toast.success("Account created successfully! Welcome aboard.");
            navigate(postRegisterPath);
        } catch (error) {
            if (error instanceof AxiosError) {
                const msg = error.response?.data?.message || "Registration failed. Please try again.";
                toast.error(msg);
            } else if (error instanceof Error) {
                toast.error(error.message);
            } else {
                toast.error("Registration failed. Please try again.");
            }
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="relative min-h-screen flex items-center justify-center p-4">
            <AnimatedBackground />

            <Card className="w-full max-w-md bg-card/90 backdrop-blur-md border-border shadow-xl z-10 relative">
                <CardHeader className="space-y-1 pb-2">
                    <div className="flex items-center gap-2">
                        <Pencil className="h-5 w-5 text-primary" />
                        <h1 className="text-xl font-bold tracking-wider text-card-foreground">
                            CREATE A NEW ACCOUNT
                        </h1>
                    </div>
                    {stored.email && (
                        <p className="text-sm text-muted-foreground pt-1">
                            Completing registration for <span className="text-card-foreground font-medium">{stored.email}</span>
                        </p>
                    )}
                </CardHeader>

                <CardContent className="space-y-4 pt-4">
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">

                            <div className="grid grid-cols-2 gap-4">
                                <FormField
                                    control={form.control}
                                    name="first_name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm font-medium text-card-foreground">First Name</FormLabel>
                                            <FormControl>
                                                <Input placeholder="First name" {...field} className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground" />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="last_name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm font-medium text-card-foreground">Last Name</FormLabel>
                                            <FormControl>
                                                <Input placeholder="Last name" {...field} className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground" />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </div>

                            <FormField
                                control={form.control}
                                name="username"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">Username</FormLabel>
                                        <FormControl>
                                            <Input placeholder="Username" {...field} className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <FormField
                                control={form.control}
                                name="phone"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">Phone</FormLabel>
                                        <FormControl>
                                            <Input placeholder="Phone number" {...field} className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <FormField
                                control={form.control}
                                name="country"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">Country</FormLabel>
                                        <Select onValueChange={field.onChange} value={field.value}>
                                            <FormControl>
                                                <SelectTrigger className="bg-muted/70 border-border text-card-foreground focus:ring-primary">
                                                    <SelectValue placeholder="Select your country">
                                                        {field.value && (() => {
                                                            const c = countries.find(c => c.value === field.value);
                                                            return c ? (
                                                                <span className="flex items-center gap-2">
                                                                    <span>{c.flag}</span>
                                                                    <span>{c.label}</span>
                                                                </span>
                                                            ) : null;
                                                        })()}
                                                    </SelectValue>
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent className="bg-card border-border text-card-foreground max-h-60">
                                                {countries.map((country) => (
                                                    <SelectItem key={country.value} value={country.value}>
                                                        <span className="flex items-center gap-2">
                                                            <span>{country.flag}</span>
                                                            <span>{country.label}</span>
                                                        </span>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <FormField
                                control={form.control}
                                name="password"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="text-sm font-medium text-card-foreground">Password</FormLabel>
                                        <div className="relative">
                                            <FormControl>
                                                <Input
                                                    type={showPassword ? "text" : "password"}
                                                    placeholder="••••••••"
                                                    {...field}
                                                    className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground pr-10"
                                                />
                                            </FormControl>
                                            <button
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-card-foreground"
                                            >
                                                {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                            </button>
                                        </div>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <Button
                                type="submit"
                                className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-6 mt-2"
                                disabled={isLoading}
                            >
                                {isLoading ? (
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                        Creating account...
                                    </div>
                                ) : "CREATE ACCOUNT"}
                            </Button>
                        </form>
                    </Form>
                </CardContent>

                <CardFooter className="flex flex-col space-y-3 pt-0">
                    <div className="flex items-center justify-center space-x-2 text-sm text-card-foreground/80">
                        <span>Already have an account?</span>
                        <Link to="/" className="text-primary hover:text-primary/90 font-medium">Login</Link>
                    </div>
                </CardFooter>
            </Card>
        </div>
    );
}

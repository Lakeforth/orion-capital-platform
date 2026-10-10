import { useState } from "react";
import { X } from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useNavigate } from "react-router-dom";
import { AxiosError } from "axios";
import Logo from "@/components/Logo";
import { toast } from "@/components/ui/sonner";
import axiosInstance from "@/lib/axios";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { Link } from "react-router-dom";

const formSchema = z.object({
  email: z.string().email({ message: "Please enter a valid email address" }),
});

export default function ForgotPasswordBasic() {
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: "",
    },
  });

  async function onSubmit(values: z.infer<typeof formSchema>) {
    setIsLoading(true);
    try {
      const response = await axiosInstance.post(
        "/auth/forgot-password",
        values
      );

      toast.success(
        response.data.message ||
          "Password reset instructions sent to your email"
      );
      navigate("/");
    } catch (error) {
      console.error("Forgot password request failed:", error);

      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message ||
            "Failed to process password reset request"
        );
      } else {
        toast.error("An unexpected error occurred. Please try again.");
      }
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <div className="w-full bg-[#0C1E32] text-white/80 py-4 px-4 text-center text-sm">
        Trading CFDs carries a high level of risk to your capital, and you
        should only trade with money you can afford to lose.
      </div>

      {/* Navigation */}
      <div className="w-full border-t-gray-400 border-t bg-[#0C1E32] px-4 md:px-8 py-3 md:py-3 flex justify-between items-center">
        <Logo />
        <Link to="/" className="text-white hover:text-white/80">
          <X className="h-5 w-5" />
        </Link>
      </div>

      {/* Main content */}
      <div className="flex-1 bg-white flex flex-col items-center  px-4 md:px-4 py-8">
        <div className="w-full max-w-xl bg-white rounded-3xl p-8">
          <div className="flex mb-6">
            <div className="w-20 h-20 rounded-full bg-gray-100 flex items-center  justify-center">
              {/* Lock icon */}
              <svg
                width="40"
                height="40"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="text-gray-400"
              >
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
          </div>

          <h1 className="text-2xl font-bold text-gray-900 mb-4 text-center flex">
            Lost your password?
          </h1>
          <p className="text-gray-600 mb-8  flex">
            No stress, it happens to the best of us! Just drop in the email you
            signed up with, and we'll shoot you a reset link to get you back in
            action.
          </p>

          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormControl>
                      <Input
                        type="email"
                        placeholder="name@example.com"
                        {...field}
                        className="w-full bg-white border-gray-200 rounded-lg py-6 px-4"
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="space-y-4 flex justify-between">
                <Button
                  type="button"
                  onClick={() => navigate("/")}
                  className="w-[20%] bg-slate-300 hover:bg-slate-300/50 text-background/70 rounded-full py-6 text-md font-semibold"
                >
                  Back
                </Button>

                <Button
                  type="submit"
                  disabled={isLoading}
                  className="w-[30%] border-none bg-muted hover:bg-muted/90 text-white rounded-full py-6 text-md font-semibold"
                >
                  {isLoading ? (
                    <div className="flex items-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                      Sending...
                    </div>
                  ) : (
                    "Continue"
                  )}
                </Button>
              </div>
            </form>
          </Form>

          <div className="mt-6 text-sm text-gray-600 bg-primary/10 p-4 rounded-lg">
            <p>
              If you registered through SSO (Google, Apple, or LinkedIn), we are
              unable to modify your password. Please utilise the reset password
              feature on your chosen platform.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

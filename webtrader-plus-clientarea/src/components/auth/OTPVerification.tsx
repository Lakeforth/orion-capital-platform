import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";

// Define the form schema with validation
const otpFormSchema = z.object({
  code: z
    .string()
    .min(1, "Security code is required")
    .refine((val) => val.match(/^\d+$/), "Code must contain only numbers"),
});

type OTPFormValues = z.infer<typeof otpFormSchema>;

interface OTPVerificationProps {
  email: string;
  onVerify: (code: string) => Promise<void>;
}

export function OTPVerification({ email, onVerify }: OTPVerificationProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);

  const form = useForm<OTPFormValues>({
    resolver: zodResolver(otpFormSchema),
    defaultValues: {
      code: "",
    },
  });

  const onSubmit = async (values: OTPFormValues) => {
    setIsSubmitting(true);
    try {
      await onVerify(values.code);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="w-full max-w-[500px] bg-card/90 backdrop-blur-md border-border shadow-xl">
      <CardHeader className="space-y-1 text-center pb-2">
        <h1 className="text-xl font-bold tracking-wider text-card-foreground">
          VERIFY LOGIN
        </h1>
      </CardHeader>
      <CardContent className="space-y-4 pt-4">
        <p className="text-sm text-card-foreground">
          Your login security code has been sent to{" "}
          <span className="font-semibold">{email}</span>.
        </p>
        <div className="text-sm text-[#ff3e3e]">
          <span>Attention:</span> If you didn't get an email in your inbox,
          verify both your spam or junk folder. If the email is present in your
          spam folder, select "Report as not spam" to assist your email service
          provider in enhancing and developing its filtering capabilities.
        </div>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-sm font-medium text-card-foreground">
                    Security code
                  </FormLabel>
                  <FormControl>
                    <Input
                      {...field}
                      type="text"
                      inputMode="numeric"
                      pattern="[0-9]*"
                      placeholder="Your Security code"
                      className="bg-muted/70 border-border text-card-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-primary"
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="space-y-4">
              <Button
                type="submit"
                className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-6"
                disabled={isSubmitting}
              >
                {isSubmitting ? "Verifying..." : "Login"}
              </Button>
              <Button
                type="button"
                variant="destructive"
                className="w-full py-6"
                onClick={() => window.location.reload()}
              >
                Exit
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}

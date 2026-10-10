import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useSurveyMutation } from "@/services/survey/survey-queries";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { motion } from "framer-motion";
import ReactCountryFlag from "react-country-flag";
import countriesData from "@/config/countries.json";
import {
  Form,
  FormField,
  FormItem,
  FormLabel,
  FormControl,
  FormMessage,
  FormDescription,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import Logo from "@/components/Logo";
import { toast } from "@/components/ui/sonner";

const dateRegex = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;

const formSchema = z.object({
  firstName: z.string().min(1, "First name is required"),
  lastName: z.string().min(1, "Last name is required"),
  email: z.string().min(1, "Email is required").email("Invalid email address"),
  phoneDialCode: z.string().min(1, "Please select a country code"),
  phoneNumber: z.string().min(5, "Phone number is required"),
  lossAmount: z.string().min(1, "Please select an amount"),
  scamType: z.string().min(1, "Please select a scam type"),
  firstPaymentDate: z
    .string()
    .optional()
    .refine((val) => !val || dateRegex.test(val), "Please select a valid date"),
  paymentMethod: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

const step1Fields = ["firstName", "lastName", "email", "phoneDialCode", "phoneNumber"] as const;

export default function ScamSurveyPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const campaign = searchParams.get("campaign") ?? "";
  const { mutate: submitSurvey, isPending } = useSurveyMutation();
  const [step, setStep] = useState<1 | 2>(1);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      firstName: "",
      lastName: "",
      email: "",
      phoneDialCode: "+44",
      phoneNumber: "",
      lossAmount: "",
      scamType: "",
      firstPaymentDate: "",
      paymentMethod: "",
    },
  });

  const selectedDialCode = form.watch("phoneDialCode");
  const selectedCountry = countriesData.find(
    (c) => c.dial_code === selectedDialCode,
  );

  async function handleNext() {
    const valid = await form.trigger(step1Fields);
    if (valid) setStep(2);
  }

  function onSubmit(values: FormValues) {
    const amountMap: Record<string, number> = {
      "less-than-5000": 4999,
      "5000-10000": 5000,
      "10000-25000": 10000,
      "25000-100000": 25000,
      "above-100000": 100001,
    };
    submitSurvey(
      {
        campaign,
        first_name: values.firstName,
        last_name: values.lastName,
        email: values.email,
        phone: `${values.phoneDialCode}${values.phoneNumber}`,
        scam_type: values.scamType,
        amount_lost: amountMap[values.lossAmount] ?? 0,
        scam_payment_method: values.paymentMethod ?? "",
        scam_first_payment: values.firstPaymentDate ?? "",
      },
      {
        onSuccess: () => navigate("/landing"),
        onError: (err: unknown) => {
          const message =
            (err as { response?: { data?: { message?: string; error?: string } } })
              ?.response?.data?.message ??
            (err as { response?: { data?: { error?: string } } })
              ?.response?.data?.error ??
            "Something went wrong. Please try again.";
          toast.error(message);
        },
      },
    );
  }

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: { staggerChildren: 0.07, delayChildren: 0.15 },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 16 },
    visible: { opacity: 1, y: 0 },
  };

  return (
    <>
      <style>{`
        .survey-glass-card {
          background: #ffffff;
          border: 1px solid rgba(0, 0, 0, 0.05);
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .form-input-light {
          background: #f8fafc !important;
          border: 1px solid #e2e8f0 !important;
          color: #0f172a !important;
        }
        .form-input-light::placeholder {
          color: rgba(0, 0, 0, 0.4) !important;
        }
        .form-input-light:focus {
          border-color: #10b981 !important;
          background: #ffffff !important;
          box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1) !important;
        }
        .form-label-dark {
          color: #1e293b !important;
          font-weight: 600;
          margin-bottom: 0.5rem;
        }
        .form-description-dark {
          color: #64748b !important;
        }
      `}</style>

      <div className="relative min-h-screen w-full flex items-center justify-center p-4 py-10 overflow-hidden bg-[#0a0f1e]">
        {/* Background Image */}
        <div
          className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: `url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1781118612/photo_2026-06-10_20-08-57_canth0.jpg')`,
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-br from-[#0a0f1e]/85 via-[#0a0f1e]/50 to-[#0a0f1e]/85" />
        </div>

        {/* Ambient blobs */}
        <div className="absolute inset-0 z-0 pointer-events-none overflow-hidden">
          <motion.div
            animate={{ scale: [1, 1.2, 1], opacity: [0.1, 0.2, 0.1] }}
            transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
            className="absolute -top-1/4 -left-1/4 w-1/2 h-1/2 bg-emerald-500/20 rounded-full blur-[120px]"
          />
          <motion.div
            animate={{ scale: [1, 1.3, 1], opacity: [0.1, 0.15, 0.1] }}
            transition={{
              duration: 10,
              repeat: Infinity,
              ease: "easeInOut",
              delay: 1,
            }}
            className="absolute -bottom-1/4 -right-1/4 w-1/2 h-1/2 bg-blue-500/20 rounded-full blur-[120px]"
          />
        </div>

        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="survey-glass-card relative z-10 rounded-3xl px-6 py-10 sm:px-10 sm:py-14 w-full max-w-xl"
        >
          {/* Header */}
          <motion.div variants={itemVariants} className="text-center mb-8">
            <h1 className="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight mb-3">
              Start Your Recovery Claim
            </h1>
            <p className="text-gray-500 text-sm sm:text-base max-w-md mx-auto leading-relaxed">
              Take the first step towards getting your money back. Fill in the
              details below for a free case assessment.
            </p>
          </motion.div>

          {/* Step indicator */}
          <motion.div variants={itemVariants} className="flex items-center gap-3 mb-8">
            <div className="flex-1 flex items-center gap-2">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors duration-300 ${step === 1 ? "bg-emerald-600 text-white" : "bg-emerald-100 text-emerald-700"}`}>
                1
              </div>
              <span className={`text-sm font-medium transition-colors duration-300 ${step === 1 ? "text-gray-900" : "text-gray-400"}`}>
                Your Details
              </span>
            </div>
            <div className="flex-1 h-px bg-gray-200" />
            <div className="flex-1 flex items-center gap-2">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors duration-300 ${step === 2 ? "bg-emerald-600 text-white" : "bg-gray-100 text-gray-400"}`}>
                2
              </div>
              <span className={`text-sm font-medium transition-colors duration-300 ${step === 2 ? "text-gray-900" : "text-gray-400"}`}>
                Case Details
              </span>
            </div>
          </motion.div>

          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">

              {step === 1 && (
                <>
                  {/* First Name + Last Name */}
                  <motion.div
                    variants={itemVariants}
                    className="grid grid-cols-1 sm:grid-cols-2 gap-5"
                  >
                    <FormField
                      control={form.control}
                      name="firstName"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            First Name
                          </FormLabel>
                          <FormControl>
                            <Input
                              placeholder="John"
                              className="form-input-light h-12 rounded-xl border-none focus-visible:ring-emerald-500/30"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name="lastName"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            Last Name
                          </FormLabel>
                          <FormControl>
                            <Input
                              placeholder="Smith"
                              className="form-input-light h-12 rounded-xl border-none focus-visible:ring-emerald-500/30"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* Email */}
                  <motion.div variants={itemVariants}>
                    <FormField
                      control={form.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            Email Address
                          </FormLabel>
                          <FormControl>
                            <Input
                              type="email"
                              placeholder="john.smith@example.com"
                              className="form-input-light h-12 rounded-xl border-none focus-visible:ring-emerald-500/30"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* Phone Number */}
                  <motion.div variants={itemVariants}>
                    <FormItem>
                      <FormLabel className="form-label-dark">
                        Phone Number
                      </FormLabel>
                      <div className="flex gap-3">
                        <FormField
                          control={form.control}
                          name="phoneDialCode"
                          render={({ field }) => (
                            <FormItem className="w-[130px] shrink-0">
                              <FormControl>
                                <Select
                                  onValueChange={field.onChange}
                                  defaultValue={field.value}
                                >
                                  <SelectTrigger className="form-input-light h-12 rounded-xl border-none focus:ring-emerald-500/30">
                                    <SelectValue>
                                      <div className="flex items-center gap-2">
                                        {selectedCountry && (
                                          <ReactCountryFlag
                                            countryCode={selectedCountry.code}
                                            svg
                                            style={{ fontSize: "1.2em" }}
                                          />
                                        )}
                                        <span>{field.value}</span>
                                      </div>
                                    </SelectValue>
                                  </SelectTrigger>
                                  <SelectContent className="bg-white border-black/10 text-[#0f172a] max-h-[300px]">
                                    {countriesData.map((country) => (
                                      <SelectItem
                                        key={`${country.code}-${country.dial_code}`}
                                        value={country.dial_code}
                                        className="focus:bg-black/5 focus:text-[#0f172a]"
                                      >
                                        <div className="flex items-center gap-2">
                                          <ReactCountryFlag
                                            countryCode={country.code}
                                            svg
                                            style={{ fontSize: "1.2em" }}
                                          />
                                          <span className="text-sm">
                                            {country.name} ({country.dial_code})
                                          </span>
                                        </div>
                                      </SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                              </FormControl>
                              <FormMessage className="text-rose-400" />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name="phoneNumber"
                          render={({ field }) => (
                            <FormItem className="flex-1">
                              <FormControl>
                                <Input
                                  type="tel"
                                  placeholder="123456789"
                                  className="form-input-light h-12 rounded-xl border-none focus-visible:ring-emerald-500/30"
                                  {...field}
                                />
                              </FormControl>
                              <FormMessage className="text-rose-400" />
                            </FormItem>
                          )}
                        />
                      </div>
                    </FormItem>
                  </motion.div>

                  {/* Next button */}
                  <motion.div variants={itemVariants} className="pt-4">
                    <Button
                      type="button"
                      onClick={handleNext}
                      className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-6 rounded-2xl text-lg shadow-[0_10px_20px_rgba(16,185,129,0.2)] transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                    >
                      Next
                    </Button>
                    <p className="text-center text-xs text-gray-400 mt-4">
                      Your information is kept strictly confidential.
                    </p>
                  </motion.div>
                </>
              )}

              {step === 2 && (
                <>
                  {/* Loss Amount */}
                  <motion.div variants={itemVariants}>
                    <FormField
                      control={form.control}
                      name="lossAmount"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            How much did you lose in the scam?
                          </FormLabel>
                          <FormControl>
                            <Select
                              onValueChange={field.onChange}
                              defaultValue={field.value}
                            >
                              <SelectTrigger className="form-input-light h-12 rounded-xl border-none focus:ring-emerald-500/30">
                                <SelectValue placeholder="Select amount (USD)" />
                              </SelectTrigger>
                              <SelectContent className="bg-white border-black/10 text-[#0f172a]">
                                <SelectItem
                                  value="less-than-5000"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Less than $5,000
                                </SelectItem>
                                <SelectItem
                                  value="5000-10000"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  $5,000 to $10,000
                                </SelectItem>
                                <SelectItem
                                  value="10000-25000"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  $10,000 to $25,000
                                </SelectItem>
                                <SelectItem
                                  value="25000-100000"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  $25,000 to $100,000
                                </SelectItem>
                                <SelectItem
                                  value="above-100000"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  $100K+
                                </SelectItem>
                              </SelectContent>
                            </Select>
                          </FormControl>
                          <FormDescription className="form-description-dark">
                            Exclude any profits if applicable.
                          </FormDescription>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* Scam Type */}
                  <motion.div variants={itemVariants}>
                    <FormField
                      control={form.control}
                      name="scamType"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            What type of scam was it?
                          </FormLabel>
                          <FormControl>
                            <Select
                              onValueChange={field.onChange}
                              defaultValue={field.value}
                            >
                              <SelectTrigger className="form-input-light h-12 rounded-xl border-none focus:ring-emerald-500/30">
                                <SelectValue placeholder="Select scam type" />
                              </SelectTrigger>
                              <SelectContent className="bg-white border-black/10 text-[#0f172a]">
                                <SelectItem
                                  value="crypto-scam"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Crypto Scam
                                </SelectItem>
                                <SelectItem
                                  value="investment-scam"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Investment Scam
                                </SelectItem>
                                <SelectItem
                                  value="job-task-scam"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Job/Task Scam
                                </SelectItem>
                                <SelectItem
                                  value="safe-account"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Safe account (fake bank / HMRC impersonation)
                                </SelectItem>
                                <SelectItem
                                  value="purchase-scam"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Purchase scam (goods or services)
                                </SelectItem>
                                <SelectItem
                                  value="romance-friendship"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Romance/friendship
                                </SelectItem>
                                <SelectItem
                                  value="invoice-scam"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Invoice scam
                                </SelectItem>
                                <SelectItem
                                  value="other"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Other
                                </SelectItem>
                              </SelectContent>
                            </Select>
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* First Payment Date (optional) */}
                  <motion.div variants={itemVariants}>
                    <FormField
                      control={form.control}
                      name="firstPaymentDate"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            When did you make the first payment?{" "}
                            <span className="text-gray-400 font-normal ml-1">
                              (Optional)
                            </span>
                          </FormLabel>
                          <FormControl>
                            <Input
                              type="date"
                              className="form-input-light h-12 rounded-xl border-none focus-visible:ring-emerald-500/30 cursor-pointer"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* How did they make payment? (UI only — backend pending) */}
                  <motion.div variants={itemVariants}>
                    <FormField
                      control={form.control}
                      name="paymentMethod"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel className="form-label-dark">
                            How did they make payment?
                          </FormLabel>
                          <FormControl>
                            <Select
                              onValueChange={field.onChange}
                              defaultValue={field.value}
                            >
                              <SelectTrigger className="form-input-light h-12 rounded-xl border-none focus:ring-emerald-500/30">
                                <SelectValue placeholder="Select payment method" />
                              </SelectTrigger>
                              <SelectContent className="bg-white border-black/10 text-[#0f172a]">
                                <SelectItem
                                  value="crypto"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Crypto
                                </SelectItem>
                                <SelectItem
                                  value="bank-transfer"
                                  className="focus:bg-black/5 focus:text-[#0f172a]"
                                >
                                  Bank Transfer
                                </SelectItem>
                              </SelectContent>
                            </Select>
                          </FormControl>
                          <FormMessage className="text-rose-400" />
                        </FormItem>
                      )}
                    />
                  </motion.div>

                  {/* Back + Submit */}
                  <motion.div variants={itemVariants} className="pt-4 flex flex-col gap-3">
                    <Button
                      type="submit"
                      disabled={isPending}
                      className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-6 rounded-2xl text-lg shadow-[0_10px_20px_rgba(16,185,129,0.2)] transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed disabled:scale-100"
                    >
                      {isPending ? "Submitting..." : "Open a Case"}
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      onClick={() => setStep(1)}
                      className="w-full text-gray-500 hover:text-gray-700 font-medium rounded-2xl"
                    >
                      ← Back
                    </Button>
                    <p className="text-center text-xs text-gray-400">
                      By submitting, you agree to our privacy policy and terms of
                      service.
                    </p>
                  </motion.div>
                </>
              )}

            </form>
          </Form>

          <motion.div
            variants={itemVariants}
            className="mt-12 flex justify-center opacity-40 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-300"
          >
            <Logo />
          </motion.div>
        </motion.div>
      </div>
    </>
  );
}

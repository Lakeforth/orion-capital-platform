import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { useCreatePartnerMutation } from "@/services/partners/partner-queries";
import type { PartnerPayload } from "@/services/partners/partner-types";

const schema = z.object({
  name: z.string().min(1, "Full name is required"),
  email: z.string().email("Enter a valid email"),
  phone: z.string().min(1, "Phone is required"),
  address: z.string().min(1, "Address is required"),
  country: z.string().min(1, "Country is required"),
});

type FormValues = z.infer<typeof schema>;

interface AddPartnerDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function AddPartnerDialog({ open, onOpenChange }: AddPartnerDialogProps) {
  const { mutate, isPending } = useCreatePartnerMutation();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  const onSubmit = (values: FormValues) => {
    mutate(values as PartnerPayload, {
      onSuccess: () => {
        toast.success("Partner added successfully");
        reset();
        onOpenChange(false);
      },
      onError: (err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data
            ?.message ??
          (err as { response?: { data?: { error?: string } } })?.response?.data
            ?.error ??
          "Something went wrong. Please try again.";
        toast.error(message);
      },
    });
  };

  const handleClose = () => {
    reset();
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-lg p-0 overflow-hidden bg-white border border-gray-200 rounded-2xl">
        <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-100">
          <DialogTitle className="text-xl font-bold text-gray-900">
            Add a Partner
          </DialogTitle>
          <DialogDescription className="text-sm text-gray-500 mt-0.5">
            Convert your account to a Joint Account.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="px-6 py-5 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Name */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Partner Full Name
              </label>
              <input
                {...register("name")}
                placeholder="e.g. Alexandra Sterling"
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.name && (
                <p className="text-xs text-red-500">{errors.name.message}</p>
              )}
            </div>

            {/* Email */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Partner Email Address
              </label>
              <input
                {...register("email")}
                type="email"
                placeholder="partner@example.com"
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.email && (
                <p className="text-xs text-red-500">{errors.email.message}</p>
              )}
            </div>

            {/* Phone */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Phone
              </label>
              <input
                {...register("phone")}
                placeholder="+1 234 567 8900"
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.phone && (
                <p className="text-xs text-red-500">{errors.phone.message}</p>
              )}
            </div>

            {/* Country */}
            <div className="space-y-1">
              <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                Country
              </label>
              <input
                {...register("country")}
                placeholder="e.g. United States"
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.country && (
                <p className="text-xs text-red-500">{errors.country.message}</p>
              )}
            </div>
          </div>

          {/* Address — full width */}
          <div className="space-y-1">
            <label className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
              Address
            </label>
            <input
              {...register("address")}
              placeholder="12 Partner Street"
              className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
            />
            {errors.address && (
              <p className="text-xs text-red-500">{errors.address.message}</p>
            )}
          </div>

          {/* Info box */}
          <div className="flex gap-3 rounded-xl bg-blue-50 border border-blue-100 p-4">
            <svg
              className="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"
              />
            </svg>
            <p className="text-xs text-blue-700 leading-relaxed">
              Adding a partner initiates a formal KYC verification process. Your
              partner will receive an invitation link to complete their profile and
              provide necessary documentation.
            </p>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-1">
            <button
              type="button"
              onClick={handleClose}
              className="flex-1 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="flex-1 rounded-xl bg-[#FF7700] py-3 text-sm font-semibold text-white hover:bg-[#e56d00] transition-colors disabled:opacity-60"
            >
              {isPending ? "Sending..." : "Invite Partner"}
            </button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import { Pencil, Trash2, UserPlus, Loader2, UsersRound } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { AddPartnerDialog } from "@/components/AddPartnerDialog";
import {
  usePartnersQuery,
  useUpdatePartnerMutation,
  useDeletePartnerMutation,
} from "@/services/partners/partner-queries";
import type {
  Partner,
  PartnerPayload,
} from "@/services/partners/partner-types";

// ── Edit dialog ──────────────────────────────────────────────────────────────

const schema = z.object({
  name: z.string().min(1, "Full name is required"),
  email: z.string().email("Enter a valid email"),
  phone: z.string().min(1, "Phone is required"),
  address: z.string().min(1, "Address is required"),
  country: z.string().min(1, "Country is required"),
});

type FormValues = z.infer<typeof schema>;

function EditPartnerDialog({
  partner,
  open,
  onOpenChange,
}: {
  partner: Partner;
  open: boolean;
  onOpenChange: (v: boolean) => void;
}) {
  const { mutate, isPending } = useUpdatePartnerMutation();
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: partner.name,
      email: partner.email,
      phone: partner.phone,
      address: partner.address,
      country: partner.country,
    },
  });

  const onSubmit = (values: FormValues) => {
    mutate(
      { id: partner.id, ...(values as PartnerPayload) },
      {
        onSuccess: () => {
          toast.success("Partner updated");
          onOpenChange(false);
        },
        onError: (err: unknown) => {
          const message =
            (err as { response?: { data?: { message?: string } } })?.response
              ?.data?.message ??
            (err as { response?: { data?: { error?: string } } })?.response
              ?.data?.error ??
            "Something went wrong. Please try again.";
          toast.error(message);
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg p-0 overflow-hidden bg-white border border-gray-300 rounded-2xl shadow-xl">
        <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-200">
          <DialogTitle className="text-xl font-bold text-gray-900">
            Edit Partner
          </DialogTitle>
          <DialogDescription className="text-sm text-gray-700 font-medium mt-0.5">
            Update the partner's details below.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="px-6 py-5 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1">
              <label className="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                Full Name
              </label>
              <input
                {...register("name")}
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.name && (
                <p className="text-xs text-red-500">{errors.name.message}</p>
              )}
            </div>

            <div className="space-y-1">
              <label className="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                Email Address
              </label>
              <input
                {...register("email")}
                type="email"
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.email && (
                <p className="text-xs text-red-500">{errors.email.message}</p>
              )}
            </div>

            <div className="space-y-1">
              <label className="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                Phone
              </label>
              <input
                {...register("phone")}
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.phone && (
                <p className="text-xs text-red-500">{errors.phone.message}</p>
              )}
            </div>

            <div className="space-y-1">
              <label className="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                Country
              </label>
              <input
                {...register("country")}
                className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
              />
              {errors.country && (
                <p className="text-xs text-red-500">{errors.country.message}</p>
              )}
            </div>
          </div>

          <div className="space-y-1">
            <label className="text-[11px] font-bold uppercase tracking-wider text-gray-600">
              Address
            </label>
            <input
              {...register("address")}
              className="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#FF7700]/40 focus:border-[#FF7700]"
            />
            {errors.address && (
              <p className="text-xs text-red-500">{errors.address.message}</p>
            )}
          </div>

          <div className="flex gap-3 pt-1">
            <button
              type="button"
              onClick={() => onOpenChange(false)}
              className="flex-1 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="flex-1 rounded-xl bg-[#FF7700] py-3 text-sm font-semibold text-white hover:bg-[#e56d00] transition-colors disabled:opacity-60"
            >
              {isPending ? "Saving..." : "Save Changes"}
            </button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// ── Delete confirmation ───────────────────────────────────────────────────────

function DeleteConfirmDialog({
  partner,
  open,
  onOpenChange,
}: {
  partner: Partner;
  open: boolean;
  onOpenChange: (v: boolean) => void;
}) {
  const { mutate, isPending } = useDeletePartnerMutation();

  const handleDelete = () => {
    mutate(partner.id, {
      onSuccess: () => {
        toast.success("Partner removed");
        onOpenChange(false);
      },
      onError: () => toast.error("Failed to remove partner. Please try again."),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm bg-white border border-gray-200 rounded-2xl">
        <DialogHeader>
          <DialogTitle className="text-gray-900">Remove Partner</DialogTitle>
          <DialogDescription>
            Are you sure you want to remove{" "}
            <span className="font-semibold text-gray-800">{partner.name}</span>?
            This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div className="flex gap-3 mt-2">
          <button
            onClick={() => onOpenChange(false)}
            className="flex-1 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleDelete}
            disabled={isPending}
            className="flex-1 rounded-xl bg-red-500 py-3 text-sm font-semibold text-white hover:bg-red-600 transition-colors disabled:opacity-60"
          >
            {isPending ? "Removing..." : "Remove"}
          </button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

// ── Partner card ─────────────────────────────────────────────────────────────

function PartnerCard({ partner }: { partner: Partner }) {
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);

  const rows = [
    { label: "Full Name", value: partner.name },
    { label: "Email", value: partner.email },
    { label: "Phone", value: partner.phone },
    { label: "Address", value: partner.address },
    { label: "Country", value: partner.country },
  ];

  return (
    <>
      <div className="bg-white rounded-xl border border-[#00234B]/10 overflow-hidden flex flex-col">
        {/* Card header */}
        <div className="flex items-center justify-between px-5 pt-5 pb-3">
          <h3 className="text-base font-bold text-[#00234B]">
            Personal Details
          </h3>
          <div className="flex items-center gap-1">
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">
              <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
              Partner
            </span>
          </div>
        </div>

        {/* Detail rows */}
        <div className="flex-1 divide-y divide-[#00234B]/15 px-5">
          {rows.map(({ label, value }) => (
            <div
              key={label}
              className="flex items-center justify-between py-3 gap-4"
            >
              <span className="text-sm text-[#00234B]/75 font-medium flex-shrink-0">
                {label}
              </span>
              <span className="text-sm font-semibold text-[#00234B] text-right break-all">
                {value}
              </span>
            </div>
          ))}
        </div>

        {/* Actions */}
        <div className="flex gap-2 px-5 py-4 border-t border-[#00234B]/15">
          <button
            onClick={() => setEditOpen(true)}
            className="flex-1 flex items-center justify-center gap-1.5 rounded-lg border border-[#00234B]/15 py-2 text-xs font-semibold text-[#00234B] hover:bg-[#00234B]/5 transition-colors"
          >
            <Pencil className="h-3.5 w-3.5" />
            Edit
          </button>
          <button
            onClick={() => setDeleteOpen(true)}
            className="flex-1 flex items-center justify-center gap-1.5 rounded-lg border border-red-200 py-2 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors"
          >
            <Trash2 className="h-3.5 w-3.5" />
            Remove
          </button>
        </div>
      </div>

      <EditPartnerDialog
        partner={partner}
        open={editOpen}
        onOpenChange={setEditOpen}
      />
      <DeleteConfirmDialog
        partner={partner}
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
      />
    </>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function PartnersPage() {
  const [addOpen, setAddOpen] = useState(false);
  const { data: partners, isLoading } = usePartnersQuery();

  return (
    <div className="bg-[#121A33] p-5 pt-10 min-h-screen font-sans">
      <div
        className="max-w-[1600px] mx-auto bg-[#EDF0F4] rounded-xl shadow-2xl p-6 md:p-8 flex flex-col gap-6"
        style={{ minHeight: "calc(100vh - 5rem)" }}
      >
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-[#00234B]">Partners</h1>
            <p className="text-sm text-[#00234B]/85 font-medium mt-0.5">
              Manage your joint account partners
            </p>
          </div>
          <button
            onClick={() => setAddOpen(true)}
            className="flex items-center gap-2 rounded-xl bg-[#FF7700] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#e56d00] transition-colors"
          >
            <UserPlus className="h-4 w-4" />
            Add Partner
          </button>
        </div>

        {/* Content */}
        {isLoading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="h-6 w-6 animate-spin text-[#FF7700]" />
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            {(partners ?? []).map((partner) => (
              <PartnerCard key={partner.id} partner={partner} />
            ))}

            {/* Add a Partner card */}
            <div className="bg-white rounded-xl border border-[#00234B]/10 flex flex-col items-start justify-between p-6 gap-4 min-h-[220px]">
              <div className="flex flex-col gap-2">
                <div className="flex items-center gap-2">
                  <UsersRound className="h-5 w-5 text-[#00234B]" />
                  <h3 className="text-base font-bold text-[#00234B]">
                    Add a Partner
                  </h3>
                </div>
                <p className="text-sm text-[#00234B]/80 font-medium leading-relaxed">
                  Share management, increase leverage limits, and simplify
                  household financial planning by adding a trusted partner.
                </p>
              </div>
              <button
                onClick={() => setAddOpen(true)}
                className="flex items-center gap-2 rounded-xl bg-[#00234B] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#001a38] transition-colors"
              >
                Add a Partner →
              </button>
            </div>
          </div>
        )}

        <AddPartnerDialog open={addOpen} onOpenChange={setAddOpen} />
      </div>
    </div>
  );
}

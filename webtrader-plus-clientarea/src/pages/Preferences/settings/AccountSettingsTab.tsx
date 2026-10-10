// Account Settings — the client's own profile, as a full page rather than the
// dialog the web trader used to open.
//
// Two cards, matching the design: the details that identify the account, and
// the address and contact details that fill it out. Everything here saves to
// /update; the fields with no column of their own (apartment, tax number,
// emergency contact, communication preference) are stored by the API in
// users.settings -- see App\Support\ClientProfile.

import * as React from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import { Lock, Upload, Loader2 } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { countries } from "@/data/countries";

const schema = z.object({
  first_name: z.string().min(2, "First name must be at least 2 characters"),
  last_name: z.string().min(2, "Last name must be at least 2 characters"),
  email: z.string().email("Enter a valid email address"),
  phone: z.string().optional(),
  address: z.string().optional(),
  apartment: z.string().optional(),
  city: z.string().optional(),
  zip_code: z.string().optional(),
  country: z.string().optional(),
  birth_date: z.string().optional(),
  tax_id: z.string().optional(),
  emergency_contact: z.string().optional(),
  emergency_contact_phone: z.string().optional(),
  preferred_communication: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

const COMMUNICATION = [
  { value: "email", label: "Email" },
  { value: "phone", label: "Phone call" },
  { value: "sms", label: "SMS" },
  { value: "whatsapp", label: "WhatsApp" },
];

/** Stored country values vary in case; match them to an option either way. */
const countryOptionValue = (stored: string) => {
  const wanted = stored.toLowerCase();
  return (
    countries.find((c) => c.value === wanted || c.label.toLowerCase() === wanted)?.value ?? stored
  );
};

function Field({
  label,
  htmlFor,
  hint,
  error,
  children,
}: {
  label: string;
  htmlFor?: string;
  hint?: string;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1.5">
      <label htmlFor={htmlFor} className="block text-sm font-medium text-foreground">
        {label}
        {hint && <span className="ml-1 font-normal text-muted-foreground">{hint}</span>}
      </label>
      {children}
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  );
}

function Card({
  title,
  description,
  action,
  children,
}: {
  title: string;
  description: string;
  action?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <section className="rounded-lg border border-border bg-card p-5 sm:p-6 dark:[&_[data-slot=input]]:border-white/15 dark:[&_[data-slot=input]]:bg-background dark:[&_[data-slot=input]:hover]:border-white/30 dark:[&_[data-slot=select-trigger]]:border-white/15 dark:[&_[data-slot=select-trigger]]:bg-background dark:[&_[data-slot=select-trigger]:hover]:border-white/30">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h2 className="text-base font-semibold text-foreground">{title}</h2>
          <p className="mt-0.5 text-sm text-muted-foreground">{description}</p>
        </div>
        {action}
      </div>
      <div className="mt-5">{children}</div>
    </section>
  );
}

export default function AccountSettingsTab() {
  const user = useUserStore((state) => state.user);
  const [isSaving, setIsSaving] = React.useState(false);
  const [isUploading, setIsUploading] = React.useState(false);
  const photoInput = React.useRef<HTMLInputElement>(null);

  const valuesFromUser = React.useCallback(
    (): FormValues => ({
      first_name: user?.first_name || "",
      last_name: user?.last_name || "",
      email: user?.email || "",
      phone: user?.phone || "",
      address: user?.address || "",
      apartment: user?.apartment || "",
      city: user?.city || "",
      zip_code: user?.zip_code || "",
      country: user?.country || "",
      birth_date: user?.birth_date || "",
      tax_id: user?.tax_id || "",
      emergency_contact: user?.emergency_contact || "",
      emergency_contact_phone: user?.emergency_contact_phone || "",
      preferred_communication: user?.preferred_communication || "",
    }),
    [user]
  );

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors, isDirty },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: valuesFromUser() });

  // Seed once the user lands in the store; the page can render before the
  // session has been fetched.
  const seeded = React.useRef(false);
  React.useEffect(() => {
    if (user && !seeded.current) {
      seeded.current = true;
      reset(valuesFromUser());
    }
  }, [user, reset, valuesFromUser]);

  const storeUser = (patch: Record<string, unknown>) => {
    const token = useUserStore.getState().token;
    const current = useUserStore.getState().user;
    if (current && token) {
      useUserStore.getState().setUser({ ...current, ...patch } as typeof current, token);
    }
  };

  const apiError = (error: unknown, fallback: string) => {
    const response = (
      error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
    ).response;
    return Object.values(response?.data?.errors || {})[0]?.[0] || response?.data?.message || fallback;
  };

  const onSubmit = async (data: FormValues) => {
    try {
      setIsSaving(true);
      // No avatar key: the endpoint mass-assigns what it validates, so sending
      // an empty one here would clear the photo.
      await axiosInstance.post("/update", data);
      storeUser(data);
      reset(data);
      toast.success("Your details have been saved");
    } catch (error) {
      toast.error(apiError(error, "Could not save your details. Please try again."));
    } finally {
      setIsSaving(false);
    }
  };

  const uploadPhoto = async (file: File) => {
    if (!/^image\/(jpe?g|png)$/.test(file.type)) {
      toast.error("Please choose a JPG or PNG image.");
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      toast.error("That image is larger than 2 MB.");
      return;
    }

    const body = new FormData();
    body.append("avatar", file);

    try {
      setIsUploading(true);
      const response = await axiosInstance.post("/update", body, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      const avatar = response.data?.data?.avatar;
      if (avatar) storeUser({ avatar });
      toast.success("Profile photo updated");
    } catch (error) {
      toast.error(apiError(error, "Could not upload that photo."));
    } finally {
      setIsUploading(false);
      if (photoInput.current) photoInput.current.value = "";
    }
  };

  const fullName = [user?.first_name, user?.last_name].filter(Boolean).join(" ") || "Your account";
  const initials =
    [user?.first_name?.[0], user?.last_name?.[0]].filter(Boolean).join("").toUpperCase() || "–";
  const countryValue = countryOptionValue(watch("country") || "");

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
      <div>
        <h2 className="text-lg font-semibold text-foreground">Account Settings</h2>
        <p className="text-sm text-muted-foreground">
          Manage your personal profile, sign-in details and account security.
        </p>
      </div>

      <Card
        title="Personal Details"
        description="Update the information associated with your account."
        action={
          <>
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="shrink-0 gap-2"
              disabled={isUploading}
              onClick={() => photoInput.current?.click()}
            >
              {isUploading ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Upload className="h-4 w-4" />
              )}
              Upload Photo
            </Button>
            <input
              ref={photoInput}
              type="file"
              accept="image/jpeg,image/png"
              className="hidden"
              onChange={(event) => {
                const file = event.target.files?.[0];
                if (file) uploadPhoto(file);
              }}
            />
          </>
        }
      >
        <div className="flex items-center gap-4">
          {user?.avatar ? (
            <img
              src={user.avatar}
              alt=""
              className="h-14 w-14 shrink-0 rounded-full object-cover"
            />
          ) : (
            <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-muted text-lg font-semibold text-muted-foreground">
              {initials}
            </span>
          )}
          <div className="min-w-0">
            <div className="truncate font-medium text-foreground">{fullName}</div>
            <div className="text-sm text-muted-foreground">Client</div>
          </div>
          <p className="ml-auto hidden text-right text-xs text-muted-foreground sm:block">
            JPG or PNG. Max 2 MB.
          </p>
        </div>

        <div className="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
          <Field label="First Name" htmlFor="first_name" error={errors.first_name?.message}>
            <Input id="first_name" {...register("first_name")} />
          </Field>
          <Field label="Last Name" htmlFor="last_name" error={errors.last_name?.message}>
            <Input id="last_name" {...register("last_name")} />
          </Field>
          <Field label="Account Role" hint="(read-only)">
            <div className="relative">
              {/* Read-only on purpose: a client cannot give themselves a role. */}
              <Input value="Client" readOnly disabled className="pr-9" />
              <Lock className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            </div>
          </Field>
        </div>

        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <Field label="Email Address" htmlFor="email" error={errors.email?.message}>
            <Input id="email" type="email" placeholder="Enter your email address" {...register("email")} />
          </Field>
          <Field label="Phone Number" htmlFor="phone" error={errors.phone?.message}>
            <Input id="phone" type="tel" placeholder="Enter phone number with country code" {...register("phone")} />
          </Field>
        </div>
      </Card>

      <Card
        title="Additional Profile Information"
        description="Add your address and other relevant details to keep your profile complete."
      >
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <Field label="Street Address" htmlFor="address">
            <Input id="address" placeholder="Enter street address" {...register("address")} />
          </Field>
          <Field label="Apartment / Suite" htmlFor="apartment">
            <Input id="apartment" placeholder="Optional" {...register("apartment")} />
          </Field>

          <Field label="City" htmlFor="city">
            <Input id="city" placeholder="Enter city" {...register("city")} />
          </Field>
          <Field label="Postal Code" htmlFor="zip_code">
            <Input id="zip_code" placeholder="Enter postal code" {...register("zip_code")} />
          </Field>

          <Field label="Country / Region">
            <Select value={countryValue} onValueChange={(value) => setValue("country", value, { shouldDirty: true })}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Select country or region" />
              </SelectTrigger>
              <SelectContent>
                {countries.map((country) => (
                  <SelectItem key={country.value} value={country.value}>
                    {country.label}
                  </SelectItem>
                ))}
                {countryValue && !countries.some((c) => c.value === countryValue) && (
                  <SelectItem value={countryValue}>{countryValue}</SelectItem>
                )}
              </SelectContent>
            </Select>
          </Field>
          <Field label="Date of Birth" htmlFor="birth_date">
            <Input id="birth_date" type="date" className="[color-scheme:dark]" {...register("birth_date")} />
          </Field>

          <Field label="Tax ID / VAT Number" htmlFor="tax_id">
            <Input id="tax_id" placeholder="Optional" {...register("tax_id")} />
          </Field>
          <Field label="Emergency Contact" htmlFor="emergency_contact">
            <Input id="emergency_contact" placeholder="Name and relationship" {...register("emergency_contact")} />
          </Field>

          <Field label="Emergency Contact Phone" htmlFor="emergency_contact_phone">
            <Input id="emergency_contact_phone" type="tel" placeholder="Enter phone number" {...register("emergency_contact_phone")} />
          </Field>
          <Field label="Preferred Communication">
            <Select
              value={watch("preferred_communication") || ""}
              onValueChange={(value) => setValue("preferred_communication", value, { shouldDirty: true })}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Select preference" />
              </SelectTrigger>
              <SelectContent>
                {COMMUNICATION.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>
        </div>
      </Card>

      <div className="flex flex-col-reverse items-center justify-between gap-3 sm:flex-row">
        <p className="text-sm text-muted-foreground">Changes apply only to your account.</p>
        <div className="flex w-full gap-2 sm:w-auto">
          <Button
            type="button"
            variant="outline"
            className="flex-1 sm:flex-none"
            disabled={isSaving || !isDirty}
            onClick={() => reset(valuesFromUser())}
          >
            Cancel
          </Button>
          <Button
            type="submit"
            disabled={isSaving}
            className="flex-1 bg-[#FF7700] text-white hover:bg-[#e66b00] sm:flex-none"
          >
            {isSaving ? "Saving..." : "Save Changes"}
          </Button>
        </div>
      </div>
    </form>
  );
}

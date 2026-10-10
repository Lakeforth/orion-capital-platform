// Personal details, editable from inside the webtrader.
//
// The webtrader runs on its own origin and never loads the client-area app, so
// the avatar menu there used to offer nothing but "Back to Client Area" and
// "Logout" -- a client who logged straight into the terminal, or who was
// launched into it by an agent from the CRM, had no way to correct their own
// details.
//
// Laid out as the client asked for it: two tabs, and a Personal Information
// panel that reads as a record until "Edit Profile" is pressed, rather than a
// wall of input boxes. Read-first is the point -- most opens are someone
// checking what is on file, not changing it.
//
// Controlled rather than wrapped in a DialogTrigger: it is opened from a
// DropdownMenuItem, and the menu unmounts its items as it closes, which would
// take an embedded trigger (and the dialog with it) away mid-click.

import * as React from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import { Pencil } from "lucide-react";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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
import VerificationsPanel from "@/components/profile/VerificationsPanel";
import { languages, timezones } from "@/data/preferences";

// Only the name and the email are required. Everything else is left optional
// on purpose: an account launched from the CRM is often a half-filled lead,
// and refusing to save a corrected phone number because the address is still
// blank is exactly the dead end this dialog exists to remove.
const formSchema = z.object({
  first_name: z.string().min(2, "First name must be at least 2 characters"),
  last_name: z.string().min(2, "Last name must be at least 2 characters"),
  email: z.string().email("Please enter a valid email address"),
  phone: z.string().optional(),
  birth_date: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  zip_code: z.string().optional(),
  country: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

interface Props {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Which tab to land on. Lets a KYC menu item open straight at Verifications. */
  initialTab?: "profile" | "verifications";
}

/** One row of the read-only record: a small caption over the value. */
function Detail({
  label,
  children,
  className,
}: {
  label: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div className={className}>
      <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {label}
      </div>
      <div className="mt-1 text-[15px] font-medium text-foreground whitespace-pre-line break-words">
        {children}
      </div>
    </div>
  );
}

const countryLabel = (value?: string | null) =>
  countries.find((c) => c.value === value)?.label ?? value ?? "";

const countryOptionValue = (stored: string) => {
  const wanted = stored.toLowerCase();
  return (
    countries.find((c) => c.value === wanted || c.label.toLowerCase() === wanted)?.value ?? stored
  );
};

export default function PersonalInformationDialog({
  open,
  onOpenChange,
  initialTab = "profile",
}: Props) {
  const user = useUserStore((state) => state.user);
  const [isSaving, setIsSaving] = React.useState(false);
  const [isEditing, setIsEditing] = React.useState(false);
  const [tab, setTab] = React.useState<string>(initialTab);

  const valuesFromUser = React.useCallback(
    (): FormValues => ({
      first_name: user?.first_name || "",
      last_name: user?.last_name || "",
      email: user?.email || "",
      phone: user?.phone || "",
      birth_date: user?.birth_date || "",
      address: user?.address || "",
      city: user?.city || "",
      zip_code: user?.zip_code || "",
      country: user?.country || "",
    }),
    [user]
  );

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: valuesFromUser(),
  });

  const countryValue = countryOptionValue(watch("country") || "");

  // Re-seed each time it opens, so it never shows what was typed and abandoned
  // on a previous visit, and picks up anything changed elsewhere meanwhile.
  // Always lands on the record, never mid-edit.
  //
  // Guarded on the transition rather than on `open` alone: valuesFromUser
  // changes whenever the stored user does, and submitting KYC documents from
  // the Verifications tab does exactly that -- which threw the client back to
  // the Profile tab at the moment they wanted to see the row they had just
  // created.
  const wasOpen = React.useRef(false);
  React.useEffect(() => {
    if (open && !wasOpen.current) {
      reset(valuesFromUser());
      setIsEditing(false);
      setTab(initialTab);
    }
    wasOpen.current = open;
  }, [open, reset, valuesFromUser, initialTab]);

  // ---------------------------------------------------------------- preferences
  // Language and timezone live on the user row but are not part of the profile
  // payload, so they load and save on their own. They stay editable while the
  // record above is read-only -- picking a timezone is not "editing your
  // identity", and making it wait behind the Edit button only adds a step.
  const [locale, setLocale] = React.useState("");
  const [timezone, setTimezone] = React.useState("");
  // null until the first fetch lands, so the boxes do not flash unchecked.
  const [emailNotifications, setEmailNotifications] = React.useState<boolean | null>(null);
  const [inAppNotifications, setInAppNotifications] = React.useState<boolean | null>(null);

  React.useEffect(() => {
    if (!open) return;
    let cancelled = false;

    (async () => {
      try {
        const [prefs, notify] = await Promise.all([
          axiosInstance.get("/settings/preferences"),
          axiosInstance.get("/notifications/preferences"),
        ]);
        if (cancelled) return;

        setLocale(prefs.data?.data?.locale || "");
        setTimezone(prefs.data?.data?.timezone || "");

        // The table stores a row per event. The dialog offers one switch per
        // channel, so a channel reads as on when any event still uses it.
        const rows: Array<{ email: boolean; in_app: boolean }> =
          notify.data?.data?.preferences || [];
        setEmailNotifications(rows.some((row) => row.email));
        setInAppNotifications(rows.some((row) => row.in_app));
      } catch {
        // Nothing to say here: the profile above is the reason the dialog is
        // open, and a toast about preferences would bury it.
        if (!cancelled) {
          setEmailNotifications(false);
          setInAppNotifications(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [open]);

  const savePreference = async (payload: Record<string, string>) => {
    try {
      await axiosInstance.post("/settings/preferences", payload);
    } catch {
      toast.error("Could not save that preference.");
    }
  };

  /** Flip one channel across every event, which is what the single box means. */
  const saveNotificationChannel = async (channel: "email" | "in_app", enabled: boolean) => {
    try {
      const current = await axiosInstance.get("/notifications/preferences");
      const rows: Array<{
        event_key: string;
        in_app: boolean;
        email: boolean;
        telegram: boolean;
      }> = current.data?.data?.preferences || [];

      await axiosInstance.post("/notifications/preferences", {
        preferences: rows.map((row) => ({
          event_key: row.event_key,
          in_app: channel === "in_app" ? enabled : !!row.in_app,
          email: channel === "email" ? enabled : !!row.email,
          telegram: !!row.telegram,
        })),
      });
    } catch {
      toast.error("Could not save that preference.");
      // Put the box back where it was rather than leaving it lying about the
      // state on the server.
      if (channel === "email") setEmailNotifications(!enabled);
      else setInAppNotifications(!enabled);
    }
  };

  // ---------------------------------------------------------------- saving
  const onSubmit = async (data: FormValues) => {
    try {
      setIsSaving(true);
      // No avatar key: the endpoint mass-assigns whatever it validates, so
      // sending an empty one here would clear the client's profile picture.
      await axiosInstance.post("/update", data);

      const token = useUserStore.getState().token;
      if (user && token) {
        useUserStore.getState().setUser({ ...user, ...data }, token);
      }

      toast.success("Profile updated successfully");
      setIsEditing(false);
    } catch (error) {
      const response = (
        error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
      ).response;
      // 422s carry the useful part in `errors` -- the top-level message is the
      // generic "The given data was invalid."
      const firstFieldError = Object.values(response?.data?.errors || {})[0]?.[0];
      toast.error(
        firstFieldError ||
          response?.data?.message ||
          "Failed to update profile. Please try again."
      );
    } finally {
      setIsSaving(false);
    }
  };

  const field = (
    name: keyof FormValues,
    label: string,
    type: React.HTMLInputTypeAttribute = "text"
  ) => (
    <div className="space-y-1.5">
      <label
        htmlFor={`pi-${name}`}
        className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
      >
        {label}
      </label>
      <Input
        id={`pi-${name}`}
        type={type}
        {...register(name)}
        className={type === "date" ? "[color-scheme:dark]" : undefined}
      />
      {errors[name] && (
        <p className="text-xs text-red-500">{errors[name]?.message}</p>
      )}
    </div>
  );

  const fullName = [user?.first_name, user?.last_name].filter(Boolean).join(" ");
  // Street on its own line, then the locality line, the way an address is
  // written. Blank parts drop out instead of leaving stray commas.
  const localityLine = [user?.city, user?.zip_code, countryLabel(user?.country)]
    .filter(Boolean)
    .join(", ");
  const residentialAddress = [user?.address, localityLine].filter(Boolean).join("\n");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {/* Fixed size, taken from what the Profile tab needs. Left to size
          itself the panel jumped every time the tabs were switched, because
          Verifications is a third of the height. The body scrolls inside
          instead. */}
      <DialogContent
        closeOutside
        className="sm:max-w-3xl h-[min(660px,82vh)] [&_[data-slot=dialog-body]]:h-full [&_[data-slot=dialog-body]]:max-h-none"
      >
        <DialogHeader className="sr-only">
          <DialogTitle>Profile</DialogTitle>
          <DialogDescription>
            View and update the details held on this account.
          </DialogDescription>
        </DialogHeader>

        <Tabs value={tab} onValueChange={setTab} className="flex h-full w-full flex-col">
          {/* Full-width bar, tabs sized to their own text and pushed left --
              flex-none undoes the default flex-1, which would otherwise split
              the bar in half and leave two enormous pills. */}
          <TabsList className="h-10 w-full justify-start gap-1">
            <TabsTrigger value="profile" className="flex-none px-5">
              Profile
            </TabsTrigger>
            <TabsTrigger value="verifications" className="flex-none px-5">
              Verifications
            </TabsTrigger>
          </TabsList>

          {/* ------------------------------------------------------ Profile */}
          <TabsContent value="profile" className="mt-5 min-h-0 flex-1 space-y-6 overflow-y-auto">
            <section>
              {/* Sticky while editing: the form is taller than the panel now
                  that the panel has a fixed height, and Save scrolling out of
                  reach is how a filled-in form gets abandoned. */}
              <div className="sticky top-0 z-10 flex items-start justify-between gap-4 bg-background pb-3">
                <h2 className="text-2xl font-bold tracking-tight text-foreground">
                  Personal Information
                </h2>

                {isEditing ? (
                  <div className="flex shrink-0 gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={isSaving}
                      onClick={() => {
                        reset(valuesFromUser());
                        setIsEditing(false);
                      }}
                    >
                      Cancel
                    </Button>
                    <Button
                      type="submit"
                      form="personal-information-form"
                      size="sm"
                      disabled={isSaving}
                      className="bg-[#FF7700] text-white hover:bg-[#e66b00]"
                    >
                      {isSaving ? "Saving..." : "Save Changes"}
                    </Button>
                  </div>
                ) : (
                  <Button
                    type="button"
                    size="sm"
                    onClick={() => setIsEditing(true)}
                    className="shrink-0 gap-1.5 bg-[#FF7700] text-white hover:bg-[#e66b00]"
                  >
                    <Pencil className="h-3.5 w-3.5" />
                    Edit Profile
                  </Button>
                )}
              </div>

              {isEditing ? (
                <form
                  id="personal-information-form"
                  onSubmit={handleSubmit(onSubmit)}
                  className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2"
                >
                  {field("first_name", "First Name")}
                  {field("last_name", "Last Name")}
                  {field("email", "Email Address", "email")}
                  {field("phone", "Phone Number", "tel")}
                  {field("birth_date", "Date of Birth", "date")}

                  <div className="space-y-1.5">
                    <label
                      htmlFor="pi-country"
                      className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                      Nationality
                    </label>
                    <Select
                      value={countryValue}
                      onValueChange={(value) => setValue("country", value)}
                    >
                      <SelectTrigger id="pi-country" className="w-full">
                        <SelectValue placeholder="Select a country" />
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
                  </div>

                  <div className="sm:col-span-2">
                    {field("address", "Residential Address")}
                  </div>
                  {field("city", "City")}
                  {field("zip_code", "Postal Code")}
                </form>
              ) : (
                <div className="mt-5 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                  <Detail label="Full Name">{fullName || "—"}</Detail>
                  <Detail label="Email Address">{user?.email || "—"}</Detail>
                  <Detail label="Phone Number">{user?.phone || "—"}</Detail>
                  <Detail label="Nationality">
                    {countryLabel(user?.country) || "—"}
                  </Detail>
                  <Detail label="Date of Birth">{user?.birth_date || "—"}</Detail>
                  <Detail label="Residential Address" className="sm:col-span-2">
                    {residentialAddress || "—"}
                  </Detail>
                </div>
              )}
            </section>

            <div className="border-t border-border" />

            {/* ------------------------------------- Regional & Preferences */}
            <section>
              <h3 className="text-lg font-semibold text-foreground">
                Regional &amp; Preferences
              </h3>

              <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Language
                  </label>
                  <Select
                    value={locale}
                    onValueChange={(value) => {
                      setLocale(value);
                      savePreference({ locale: value });
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Select a language" />
                    </SelectTrigger>
                    <SelectContent>
                      {languages.map((language) => (
                        <SelectItem key={language.value} value={language.value}>
                          {language.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-1.5">
                  <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Timezone
                  </label>
                  <Select
                    value={timezone}
                    onValueChange={(value) => {
                      setTimezone(value);
                      savePreference({ timezone: value });
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Select a timezone" />
                    </SelectTrigger>
                    <SelectContent>
                      {timezones.map((zone) => (
                        <SelectItem key={zone.value} value={zone.value}>
                          {zone.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="mt-5">
                <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                  Notifications
                </div>
                <div className="mt-3 max-w-sm space-y-3">
                  <label
                    htmlFor="pi-notify-email"
                    className="flex cursor-pointer items-center justify-between gap-4"
                  >
                    <span className="text-sm text-foreground">Email Notifications</span>
                    <Checkbox
                      id="pi-notify-email"
                      checked={!!emailNotifications}
                      onCheckedChange={(checked) => {
                        const next = checked === true;
                        setEmailNotifications(next);
                        saveNotificationChannel("email", next);
                      }}
                    />
                  </label>

                  <label
                    htmlFor="pi-notify-in-app"
                    className="flex cursor-pointer items-center justify-between gap-4"
                  >
                    <span className="text-sm text-foreground">In-App Alerts</span>
                    <Checkbox
                      id="pi-notify-in-app"
                      checked={!!inAppNotifications}
                      onCheckedChange={(checked) => {
                        const next = checked === true;
                        setInAppNotifications(next);
                        saveNotificationChannel("in_app", next);
                      }}
                    />
                  </label>
                </div>
              </div>
            </section>
          </TabsContent>

          {/* ------------------------------------------------- Verifications */}
          <TabsContent value="verifications" className="mt-5 min-h-0 flex-1">
            <VerificationsPanel />
          </TabsContent>

        </Tabs>
      </DialogContent>
    </Dialog>
  );
}

// The client's KYC wizard, shown as a page in Settings > KYC (?kyc=new):
// Personal Info -> Identity -> Proof of Address ->
// Review, the same four steps the back office's Add KYC uses, against the same
// rules. Each step is saved as a draft on the server (POST /user/kyc/draft),
// so a client can close the window and pick up where they left off; Continue
// on the address step submits the whole request for review
// (POST /user/kyc/submit). A client submission always waits for an agent --
// nothing here can approve it.

import * as React from "react";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import { Check, ChevronLeft, ChevronRight, FileText, Loader2, Upload } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import axiosInstance from "@/lib/axios";
import { cn } from "@/lib/utils";
import countriesJson from "@/config/countries.json";

type Step = "personal" | "identity" | "address" | "review";

interface Option {
  value: string;
  label: string;
  needs_back?: boolean;
}

interface Country {
  name: string;
  code: string;
}

interface Personal {
  first_name: string;
  last_name: string;
  single_name: boolean;
  birthdate: string;
  nationality: string;
  dial_code: string;
  phone: string;
}

interface Address {
  street: string;
  city: string;
  postcode: string;
  country: string;
  document_type: string;
  document_type_other: string;
}

interface Metadata {
  document_number: string;
  document_expiry: string;
  issuing_country: string;
}

interface DraftResponse {
  blocked?: boolean;
  step?: Step;
  version?: number;
  id_type?: string | null;
  personal?: Partial<Personal>;
  address?: Partial<Address>;
  metadata?: {
    document_number_last4?: string | null;
    document_expiry?: string | null;
    issuing_country?: string | null;
  };
  files?: Record<string, string | null | undefined>;
  options?: {
    countries?: Country[];
    id_types?: Option[];
    address_document_types?: Option[];
    max_file_kb?: number;
  };
}

type Errors = Record<string, string>;

// The back office's own progress points for each step.
const PROGRESS: Record<Step, number> = { personal: 0, identity: 25, address: 50, review: 100 };

const STEPS: { key: Step; label: string }[] = [
  { key: "personal", label: "Personal Info" },
  { key: "identity", label: "Identity" },
  { key: "address", label: "Address" },
  { key: "review", label: "Review" },
];

const FALLBACK_ID_TYPES: Option[] = [
  { value: "passport", label: "Passport", needs_back: false },
  { value: "drivers_license", label: "Driver's License", needs_back: true },
  { value: "national_id", label: "National ID", needs_back: true },
];

const FALLBACK_ADDRESS_TYPES: Option[] = [
  { value: "utility_bill", label: "Utility Bill" },
  { value: "bank_statement", label: "Bank Statement" },
  { value: "government_letter", label: "Government Letter" },
  { value: "others", label: "Others" },
];

// Dial codes are not in the server's country list, so they come from ours,
// matched on the ISO code.
const DIAL_CODES = (countriesJson as { name: string; code: string; dial_code: string }[]).map(
  (country) => ({ code: country.code.toLowerCase(), dial: country.dial_code, name: country.name })
);

const EMPTY_PERSONAL: Personal = {
  first_name: "",
  last_name: "",
  single_name: false,
  birthdate: "",
  nationality: "",
  dial_code: "",
  phone: "",
};

const EMPTY_ADDRESS: Address = {
  street: "",
  city: "",
  postcode: "",
  country: "",
  document_type: "",
  document_type_other: "",
};

const EMPTY_METADATA: Metadata = { document_number: "", document_expiry: "", issuing_country: "" };

const TITLES: Record<Step, { title: string; intro: string }> = {
  personal: {
    title: "Personal Information",
    intro: "Verify your personal information matches your government-issued ID.",
  },
  identity: {
    title: "Identity Verification",
    intro: "Please upload a valid government-issued identification document.",
  },
  address: {
    title: "Proof of Address",
    intro:
      "Please provide your current permanent address and a supporting document issued within the last 3 months.",
  },
  review: {
    title: "Review",
    intro: "This is the receipt of what was submitted.",
  },
};

const firstErrors = (errors?: Record<string, string[]>): Errors =>
  Object.fromEntries(Object.entries(errors ?? {}).map(([key, value]) => [key, value?.[0] ?? ""]));

function stripNulls<T extends object>(value?: Partial<T>): Partial<T> {
  return Object.fromEntries(
    Object.entries(value ?? {}).filter(([, entry]) => entry !== null && entry !== undefined)
  ) as Partial<T>;
}

function Field({
  label,
  error,
  className,
  children,
}: {
  label: string;
  error?: string;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <div className={cn("space-y-1.5", className)}>
      <span className="block text-sm font-medium text-foreground">{label}</span>
      {children}
      {error && <span className="block text-xs text-red-400">{error}</span>}
    </div>
  );
}

function DropZone({
  label,
  file,
  existing,
  maxKb,
  error,
  onPick,
}: {
  label: string;
  file: File | null;
  existing?: string | null;
  maxKb: number;
  error?: string;
  onPick: (file: File | null) => void;
}) {
  const input = React.useRef<HTMLInputElement>(null);
  const shown = file?.name ?? existing ?? null;
  const maxMb = Math.round(maxKb / 1024);

  const pick = (picked: File | null) => {
    if (picked && picked.size > maxKb * 1024) {
      toast.error(`${picked.name} is larger than ${maxMb} MB.`);
      return;
    }
    onPick(picked);
  };

  return (
    <div className="space-y-1.5">
      <button
        type="button"
        onClick={() => input.current?.click()}
        onDragOver={(event) => event.preventDefault()}
        onDrop={(event) => {
          event.preventDefault();
          pick(event.dataTransfer.files?.[0] ?? null);
        }}
        className={cn(
          "flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed bg-muted/30 px-4 py-6 text-center transition-colors",
          error ? "border-red-400/60" : "border-border hover:border-[#FF7700]/60"
        )}
      >
        {shown ? (
          <FileText className="h-6 w-6 text-emerald-400" />
        ) : (
          <Upload className="h-6 w-6 text-muted-foreground" />
        )}
        <span className="max-w-full truncate text-sm text-foreground">
          {shown ?? `Drag and drop your file here (${label})`}
        </span>
        <span className="text-xs text-muted-foreground">
          {shown ? "Click to replace" : "or click to browse from your computer"}
        </span>
        <span className="rounded-full bg-muted px-2.5 py-0.5 text-[11px] text-muted-foreground">
          PDF, JPG, or PNG (Max {maxMb}MB)
        </span>
      </button>
      {error && <span className="block text-xs text-red-400">{error}</span>}
      <input
        ref={input}
        type="file"
        accept=".jpg,.jpeg,.png,.pdf"
        className="hidden"
        onChange={(event) => {
          pick(event.target.files?.[0] ?? null);
          event.target.value = "";
        }}
      />
    </div>
  );
}

function CountrySelect({
  value,
  placeholder,
  countries,
  onChange,
}: {
  value: string;
  placeholder: string;
  countries: Country[];
  onChange: (value: string) => void;
}) {
  return (
    <Select value={value || undefined} onValueChange={onChange}>
      <SelectTrigger className="w-full">
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent className="max-h-72">
        {countries.map((country) => (
          <SelectItem key={country.name} value={country.name}>
            {country.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}

export default function KycWizard({
  onClose,
  onSubmitted,
}: {
  onClose: () => void;
  onSubmitted: () => void;
}) {
  const [loading, setLoading] = React.useState(false);
  const [saving, setSaving] = React.useState(false);
  const [step, setStep] = React.useState<Step>("personal");
  const [version, setVersion] = React.useState<number | undefined>();
  const [errors, setErrors] = React.useState<Errors>({});
  const [reference, setReference] = React.useState<string | null>(null);
  const [submittedAt, setSubmittedAt] = React.useState<Date | null>(null);
  const [metadata, setMetadata] = React.useState<Metadata>(EMPTY_METADATA);
  const [numberLast4, setNumberLast4] = React.useState<string | null>(null);
  const [blocked, setBlocked] = React.useState(false);

  const [personal, setPersonal] = React.useState<Personal>(EMPTY_PERSONAL);
  const [address, setAddress] = React.useState<Address>(EMPTY_ADDRESS);
  const [idType, setIdType] = React.useState("passport");
  const [files, setFiles] = React.useState<Record<string, File | null>>({});
  const [existing, setExisting] = React.useState<Record<string, string | null | undefined>>({});

  const [countries, setCountries] = React.useState<Country[]>([]);
  const [idTypes, setIdTypes] = React.useState<Option[]>(FALLBACK_ID_TYPES);
  const [addressTypes, setAddressTypes] = React.useState<Option[]>(FALLBACK_ADDRESS_TYPES);
  const [maxKb, setMaxKb] = React.useState(5120);

  React.useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setErrors({});
    setFiles({});
    setReference(null);
    setBlocked(false);
    axiosInstance
      .get("/user/kyc/draft")
      .then((response) => {
        if (cancelled) return;
        const draft: DraftResponse = response.data?.data ?? response.data ?? {};
        setBlocked(Boolean(draft.blocked));
        setPersonal({ ...EMPTY_PERSONAL, ...stripNulls(draft.personal) });
        setAddress({ ...EMPTY_ADDRESS, ...stripNulls(draft.address) });
        setMetadata({
          document_number: "",
          document_expiry: draft.metadata?.document_expiry ?? "",
          issuing_country: draft.metadata?.issuing_country ?? "",
        });
        setNumberLast4(draft.metadata?.document_number_last4 ?? null);
        setIdType(draft.id_type || "passport");
        setVersion(draft.version);
        setExisting(draft.files ?? {});
        setStep(draft.step && draft.step !== "review" ? draft.step : "personal");
        if (draft.options?.countries?.length) setCountries(draft.options.countries);
        if (draft.options?.id_types?.length) setIdTypes(draft.options.id_types);
        if (draft.options?.address_document_types?.length)
          setAddressTypes(draft.options.address_document_types);
        if (draft.options?.max_file_kb) setMaxKb(draft.options.max_file_kb);
      })
      .catch(() => {
        if (!cancelled) setStep("personal");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  // The server's own list, because it validates against it; ours only until
  // it has loaded.
  const countryOptions: Country[] = countries.length
    ? countries
    : DIAL_CODES.map((country) => ({ name: country.name, code: country.code }));

  const dialOptions = React.useMemo(() => {
    const seen = new Set<string>();
    return DIAL_CODES.filter((country) => {
      if (!country.dial || seen.has(country.dial)) return false;
      seen.add(country.dial);
      return true;
    }).sort((a, b) => a.name.localeCompare(b.name));
  }, []);

  const selectedIdType = idTypes.find((type) => type.value === idType);
  const frontIsPdf = files.id_front?.type === "application/pdf";
  const needsBack = Boolean(selectedIdType?.needs_back) && !frontIsPdf;

  const setFile = (key: string) => (file: File | null) =>
    setFiles((current) => ({ ...current, [key]: file }));

  const failed = (error: unknown) => {
    const response = (
      error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
    ).response;
    if (response?.status === 422 && response.data?.errors) {
      setErrors(firstErrors(response.data.errors));
      toast.error("Please check the highlighted fields.");
      return;
    }
    toast.error(response?.data?.message || "Something went wrong. Please try again.");
  };

  const saveStep = async (name: Exclude<Step, "review">, body: FormData) => {
    body.append("step", name);
    const response = await axiosInstance.post("/user/kyc/draft", body, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    const data = response.data?.data ?? response.data;
    if (data?.version !== undefined) setVersion(data.version);
    if (data?.files) setExisting(data.files);
    return data;
  };

  const next = async () => {
    setErrors({});

    if (step === "identity") {
      const missing: Errors = {};
      if (!files.id_front && !existing.id_front) missing.id_front = "Please upload the front of your document.";
      if (needsBack && !files.id_back && !existing.id_back)
        missing.id_back = "This document needs its back side too.";
      if (Object.keys(missing).length) return setErrors(missing);
    }
    if (step === "address" && !files.address_front && !existing.address_front) {
      return setErrors({ address_front: "Please upload a supporting document." });
    }

    setSaving(true);
    try {
      if (step === "personal") {
        const body = new FormData();
        body.append("first_name", personal.first_name.trim());
        body.append("single_name", personal.single_name ? "1" : "0");
        if (!personal.single_name) body.append("last_name", personal.last_name.trim());
        body.append("birthdate", personal.birthdate);
        body.append("nationality", personal.nationality);
        body.append("dial_code", personal.dial_code);
        body.append("phone", personal.phone.trim());
        await saveStep("personal", body);
        setStep("identity");
      } else if (step === "identity") {
        const body = new FormData();
        body.append("id_type", idType);
        if (metadata.document_number.trim()) body.append("document_number", metadata.document_number.trim());
        if (metadata.document_expiry) body.append("document_expiry", metadata.document_expiry);
        if (metadata.issuing_country) body.append("issuing_country", metadata.issuing_country);
        if (files.id_front) body.append("id_front", files.id_front);
        if (files.id_back) body.append("id_back", files.id_back);
        await saveStep("identity", body);
        setStep("address");
      } else if (step === "address") {
        const body = new FormData();
        body.append("street", address.street.trim());
        body.append("city", address.city.trim());
        body.append("postcode", address.postcode.trim());
        body.append("country", address.country);
        body.append("document_type", address.document_type);
        if (address.document_type === "others")
          body.append("document_type_other", address.document_type_other.trim());
        if (files.address_front) body.append("address_front", files.address_front);
        if (files.address_back) body.append("address_back", files.address_back);
        const saved = await saveStep("address", body);

        const submitted = await axiosInstance.post("/user/kyc/submit", {
          version: saved?.version ?? version,
        });
        setReference(submitted.data?.data?.attempt?.reference ?? null);
        setSubmittedAt(new Date());
        setStep("review");
        onSubmitted();
      }
    } catch (error) {
      failed(error);
    } finally {
      setSaving(false);
    }
  };

  const back = () => {
    setErrors({});
    if (step === "identity") setStep("personal");
    else if (step === "address") setStep("identity");
  };

  const index = STEPS.findIndex((item) => item.key === step);
  const percent = PROGRESS[step];
  // A stored issuing country only shows if it is one of the options.
  const issuingKnown = countryOptions.some((country) => country.name === metadata.issuing_country);

  return (
    <section className="overflow-hidden rounded-lg border border-border bg-card">
        <div className="border-b border-border px-6 pb-5 pt-6">
          <div className="flex items-center justify-between gap-3">
            <h2 className="text-lg font-bold text-foreground">New KYC</h2>
            {step !== "review" && (
              <Button type="button" variant="ghost" size="sm" onClick={onClose} disabled={saving} className="gap-1">
                <ChevronLeft className="h-4 w-4" />
                Back to KYC
              </Button>
            )}
          </div>

          <div className="mt-5 space-y-2">
            <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              {percent}% of the process complete
            </div>
            <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
              <div
                className="h-full rounded-full bg-emerald-500 transition-all"
                style={{ width: `${percent}%` }}
              />
            </div>
            <div className="grid grid-cols-4 gap-2 text-[11px] font-semibold uppercase tracking-wider">
              {STEPS.map((item, position) => {
                const done = step === "review" ? position < STEPS.length - 1 : position < index;
                return (
                  <span
                    key={item.key}
                    className={cn(
                      "flex items-center gap-1",
                      done
                        ? "text-emerald-400"
                        : item.key === step
                          ? "text-foreground"
                          : "text-muted-foreground"
                    )}
                  >
                    {done && <Check className="h-3 w-3" />}
                    {item.label}
                  </span>
                );
              })}
            </div>
          </div>
        </div>

        <div className="px-6 py-5">
          {!loading && !blocked && step !== "review" && (
            <div className="mb-4">
              <h3 className="text-base font-semibold text-foreground">{TITLES[step].title}</h3>
              <p className="mt-1 text-sm text-muted-foreground">{TITLES[step].intro}</p>
            </div>
          )}
          {loading ? (
            <div className="flex items-center justify-center py-16">
              <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
            </div>
          ) : blocked ? (
            <div className="py-10 text-center text-sm text-muted-foreground">
              A KYC submission is already awaiting review. You can start a new one once it has been
              reviewed.
            </div>
          ) : step === "personal" ? (
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Legal First Name" error={errors.first_name}>
                <Input
                  value={personal.first_name}
                  placeholder="Enter first name"
                  onChange={(event) => setPersonal({ ...personal, first_name: event.target.value })}
                />
              </Field>
              <Field label="Legal Last Name" error={errors.last_name}>
                <Input
                  value={personal.last_name}
                  placeholder="Enter last name"
                  disabled={personal.single_name}
                  onChange={(event) => setPersonal({ ...personal, last_name: event.target.value })}
                />
              </Field>
              <label className="flex items-center gap-2 text-sm text-muted-foreground sm:col-span-2">
                <Checkbox
                  checked={personal.single_name}
                  onCheckedChange={(checked) =>
                    setPersonal({
                      ...personal,
                      single_name: checked === true,
                      last_name: checked === true ? "" : personal.last_name,
                    })
                  }
                />
                I have a single legal name (no last name)
              </label>
              <Field label="Date of Birth" error={errors.birthdate}>
                <Input
                  type="date"
                  value={personal.birthdate}
                  max={new Date().toISOString().slice(0, 10)}
                  onChange={(event) => setPersonal({ ...personal, birthdate: event.target.value })}
                />
              </Field>
              <Field label="Nationality" error={errors.nationality}>
                <CountrySelect
                  value={personal.nationality}
                  placeholder="Select nationality"
                  countries={countryOptions}
                  onChange={(value) => setPersonal({ ...personal, nationality: value })}
                />
              </Field>
              <Field
                label="Phone Number"
                error={errors.phone || errors.dial_code}
                className="sm:col-span-2"
              >
                <div className="flex gap-2">
                  <Select
                    value={personal.dial_code || undefined}
                    onValueChange={(value) => setPersonal({ ...personal, dial_code: value })}
                  >
                    <SelectTrigger className="w-40 shrink-0">
                      <SelectValue placeholder="Code" />
                    </SelectTrigger>
                    <SelectContent className="max-h-72">
                      {dialOptions.map((country) => (
                        <SelectItem key={country.dial} value={country.dial}>
                          {country.dial} ({country.code.toUpperCase()})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Input
                    value={personal.phone}
                    inputMode="tel"
                    placeholder="555-0123"
                    onChange={(event) => setPersonal({ ...personal, phone: event.target.value })}
                  />
                </div>
              </Field>
              <div className="rounded-md border border-[#FF7700]/30 bg-[#FF7700]/10 px-4 py-3 text-sm text-foreground sm:col-span-2">
                Please ensure the information matches your government-issued ID. Discrepancies may delay
                the verification process.
              </div>
            </div>
          ) : step === "identity" ? (
            <div className="space-y-5">
              <div className="space-y-2">
                <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                  Select ID type
                </div>
                <div className="grid gap-3 sm:grid-cols-3">
                  {idTypes.map((type) => (
                    <button
                      key={type.value}
                      type="button"
                      onClick={() => setIdType(type.value)}
                      className={cn(
                        "rounded-lg border-2 px-4 py-4 text-sm font-medium transition-colors",
                        idType === type.value
                          ? "border-[#FF7700] bg-[#FF7700]/10 text-foreground"
                          : "border-border text-muted-foreground hover:border-[#FF7700]/50"
                      )}
                    >
                      {type.label}
                    </button>
                  ))}
                </div>
                {errors.id_type && <span className="block text-xs text-red-400">{errors.id_type}</span>}
              </div>
              <div className="space-y-2">
                <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                  Upload document
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <DropZone
                    label="Front"
                    file={files.id_front ?? null}
                    existing={existing.id_front}
                    maxKb={maxKb}
                    error={errors.id_front}
                    onPick={setFile("id_front")}
                  />
                  <DropZone
                    label={needsBack ? "Back" : "Back, if any"}
                    file={files.id_back ?? null}
                    existing={existing.id_back}
                    maxKb={maxKb}
                    error={errors.id_back}
                    onPick={setFile("id_back")}
                  />
                </div>
              </div>
              <div className="grid gap-4 sm:grid-cols-3">
                <Field label="Document Number (optional)" error={errors.document_number}>
                  <Input
                    value={metadata.document_number}
                    maxLength={50}
                    placeholder={numberLast4 ? `Ends in ${numberLast4}` : ""}
                    onChange={(event) => setMetadata({ ...metadata, document_number: event.target.value })}
                  />
                </Field>
                <Field label="Expiry Date (optional)" error={errors.document_expiry}>
                  <Input
                    type="date"
                    value={metadata.document_expiry}
                    onChange={(event) => setMetadata({ ...metadata, document_expiry: event.target.value })}
                  />
                </Field>
                <Field label="Issuing Country (optional)" error={errors.issuing_country}>
                  <CountrySelect
                    value={issuingKnown ? metadata.issuing_country : ""}
                    placeholder="Not recorded"
                    countries={countryOptions}
                    onChange={(value) => setMetadata({ ...metadata, issuing_country: value })}
                  />
                </Field>
              </div>
            </div>
          ) : step === "address" ? (
            <div className="grid gap-4">
              <Field label="Street Address" error={errors.street}>
                <Input
                  value={address.street}
                  placeholder="e.g. 128 Wall Street"
                  onChange={(event) => setAddress({ ...address, street: event.target.value })}
                />
              </Field>
              <Field label="City" error={errors.city}>
                <Input
                  value={address.city}
                  placeholder="e.g. New York"
                  onChange={(event) => setAddress({ ...address, city: event.target.value })}
                />
              </Field>
              <Field label="Postcode" error={errors.postcode}>
                <Input
                  value={address.postcode}
                  placeholder="e.g. 10005"
                  onChange={(event) => setAddress({ ...address, postcode: event.target.value })}
                />
              </Field>
              <Field label="Country" error={errors.country}>
                <CountrySelect
                  value={address.country}
                  placeholder="Select country"
                  countries={countryOptions}
                  onChange={(value) => setAddress({ ...address, country: value })}
                />
              </Field>
              <Field label="Document Type" error={errors.document_type}>
                <Select
                  value={address.document_type || undefined}
                  onValueChange={(value) =>
                    setAddress({
                      ...address,
                      document_type: value,
                      document_type_other: value === "others" ? address.document_type_other : "",
                    })
                  }
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Select document type" />
                  </SelectTrigger>
                  <SelectContent>
                    {addressTypes.map((type) => (
                      <SelectItem key={type.value} value={type.value}>
                        {type.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </Field>
              {address.document_type === "others" && (
                <Field label="Specify document type" error={errors.document_type_other}>
                  <Input
                    value={address.document_type_other}
                    maxLength={100}
                    onChange={(event) =>
                      setAddress({ ...address, document_type_other: event.target.value })
                    }
                  />
                </Field>
              )}
              <div className="space-y-2">
                <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                  Upload document
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <DropZone
                    label="Front / first page"
                    file={files.address_front ?? null}
                    existing={existing.address_front}
                    maxKb={maxKb}
                    error={errors.address_front}
                    onPick={setFile("address_front")}
                  />
                  <DropZone
                    label="Back / additional page, if any"
                    file={files.address_back ?? null}
                    existing={existing.address_back}
                    maxKb={maxKb}
                    error={errors.address_back}
                    onPick={setFile("address_back")}
                  />
                </div>
              </div>
            </div>
          ) : (
            <div className="flex flex-col items-center py-4 text-center">
              <div className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/15">
                <Check className="h-7 w-7 text-emerald-400" />
              </div>
              <h3 className="mt-4 text-xl font-bold text-foreground">KYC submitted — Pending Review</h3>
              <dl className="mt-5 grid w-full max-w-md grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-left text-sm">
                <dt className="text-muted-foreground">Reference</dt>
                <dd className="font-medium text-foreground">{reference ?? "—"}</dd>
                <dt className="text-muted-foreground">Status</dt>
                <dd className="font-medium text-foreground">Pending Review</dd>
                <dt className="text-muted-foreground">Submitted at</dt>
                <dd className="font-medium text-foreground">
                  {submittedAt ? submittedAt.toLocaleString() : "—"}
                </dd>
              </dl>
              <p className="mt-4 text-xs text-muted-foreground">
                Our compliance team will review it. You will receive an email notification once the review is
                complete.
              </p>
            </div>
          )}
        </div>

        <div className="flex items-center justify-between gap-3 border-t border-border bg-muted/30 px-6 py-4">
          {step === "review" ? (
            <Button
              type="button"
              className="ml-auto bg-[#FF7700] text-white hover:bg-[#e66b00]"
              onClick={onClose}
            >
              Back to KYC
            </Button>
          ) : (
            <>
              <Button
                type="button"
                variant="ghost"
                onClick={onClose}
                disabled={saving}
              >
                Cancel process
              </Button>
              <div className="flex gap-2">
                {step !== "personal" && (
                  <Button
                    type="button"
                    variant="outline"
                    onClick={back}
                    disabled={saving}
                    className="gap-1"
                  >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                  </Button>
                )}
                <Button
                  type="button"
                  onClick={next}
                  disabled={saving || loading || blocked}
                  className="gap-1 bg-[#FF7700] text-white hover:bg-[#e66b00]"
                >
                  {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                  {step === "address" ? "Submit KYC" : "Continue"}
                  {!saving && <ChevronRight className="h-4 w-4" />}
                </Button>
              </div>
            </>
          )}
        </div>
    </section>
  );
}

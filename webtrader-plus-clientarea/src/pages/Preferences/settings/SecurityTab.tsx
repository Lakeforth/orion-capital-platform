// Security — two-factor, active sessions, password, and display preferences.
//
// Everything here is backed by an endpoint that already exists:
// /security/2fa/*, /security/sessions, /change/password and
// /settings/preferences. The sign-in alert switch is a real opt-out --
// User::notifyAuthenticationLogVia reads it before sending the mail.

import * as React from "react";
import { toast } from "sonner";
import type { AxiosError } from "axios";
import { Eye, EyeOff, Loader2, LogOut, Monitor, ShieldCheck } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { languages, timezones } from "@/data/preferences";
import { CurrencySelector } from "@/components/settings/currency-selector";

interface Session {
  id: number;
  label: string;
  location?: string | null;
  last_active_at?: string | null;
  current: boolean;
}

const apiError = (error: unknown, fallback: string) => {
  const response = (
    error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
  ).response;
  return Object.values(response?.data?.errors || {})[0]?.[0] || response?.data?.message || fallback;
};

function Card({
  title,
  description,
  action,
  children,
}: {
  title: string;
  description?: string;
  action?: React.ReactNode;
  children?: React.ReactNode;
}) {
  return (
    <section className="rounded-lg border border-border bg-card p-5">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h2 className="text-base font-semibold text-foreground">{title}</h2>
          {description && <p className="mt-0.5 text-sm text-muted-foreground">{description}</p>}
        </div>
        {action}
      </div>
      {children && <div className="mt-4">{children}</div>}
    </section>
  );
}

function PasswordInput({
  id,
  placeholder,
  value,
  onChange,
}: {
  id: string;
  placeholder: string;
  value: string;
  onChange: (value: string) => void;
}) {
  const [shown, setShown] = React.useState(false);
  return (
    <div className="relative">
      <Input
        id={id}
        type={shown ? "text" : "password"}
        placeholder={placeholder}
        value={value}
        autoComplete="new-password"
        onChange={(event) => onChange(event.target.value)}
        className="pr-10"
      />
      <button
        type="button"
        aria-label={shown ? "Hide password" : "Show password"}
        onClick={() => setShown((s) => !s)}
        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
      >
        {shown ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
      </button>
    </div>
  );
}

export default function SecurityTab() {
  const user = useUserStore((state) => state.user);

  const [sessions, setSessions] = React.useState<Session[] | null>(null);
  const [revoking, setRevoking] = React.useState(false);

  const [current, setCurrent] = React.useState("");
  const [next, setNext] = React.useState("");
  const [confirm, setConfirm] = React.useState("");
  const [changing, setChanging] = React.useState(false);

  const [locale, setLocale] = React.useState("");
  const [timezone, setTimezone] = React.useState("");
  const [alerts, setAlerts] = React.useState<boolean>(user?.signin_alerts !== false);

  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [list, prefs] = await Promise.all([
          axiosInstance.get("/security/sessions"),
          axiosInstance.get("/settings/preferences"),
        ]);
        if (cancelled) return;
        setSessions(list.data?.data ?? []);
        setLocale(prefs.data?.data?.locale || "");
        setTimezone(prefs.data?.data?.timezone || "");
      } catch {
        if (!cancelled) setSessions([]);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const savePreference = async (payload: Record<string, string>) => {
    try {
      await axiosInstance.post("/settings/preferences", payload);
    } catch (error) {
      toast.error(apiError(error, "Could not save that preference."));
    }
  };

  const saveAlerts = async (value: boolean) => {
    setAlerts(value);
    try {
      await axiosInstance.post("/update", { signin_alerts: value });
      const token = useUserStore.getState().token;
      const stored = useUserStore.getState().user;
      if (stored && token) {
        useUserStore.getState().setUser({ ...stored, signin_alerts: value }, token);
      }
    } catch (error) {
      setAlerts(!value);
      toast.error(apiError(error, "Could not save that preference."));
    }
  };

  const changePassword = async (event: React.FormEvent) => {
    event.preventDefault();
    if (next !== confirm) return toast.error("The new passwords do not match.");
    if (next.length < 8) return toast.error("Use at least 8 characters.");

    setChanging(true);
    try {
      await axiosInstance.post("/change/password", {
        current_password: current,
        new_password: next,
        new_password_confirmation: confirm,
      });
      toast.success("Password changed");
      setCurrent("");
      setNext("");
      setConfirm("");
    } catch (error) {
      toast.error(apiError(error, "Could not change your password."));
    } finally {
      setChanging(false);
    }
  };

  const signOutOthers = async () => {
    setRevoking(true);
    try {
      await axiosInstance.post("/security/sessions/revoke-others");
      const list = await axiosInstance.get("/security/sessions");
      setSessions(list.data?.data ?? []);
      toast.success("Signed out of your other sessions");
    } catch (error) {
      toast.error(apiError(error, "Could not sign out those sessions."));
    } finally {
      setRevoking(false);
    }
  };

  const others = (sessions ?? []).filter((session) => !session.current).length;

  return (
    <div className="space-y-5">
      <div>
        <h2 className="text-lg font-semibold text-foreground">Account Security</h2>
        <p className="text-sm text-muted-foreground">
          Add an extra layer of protection and manage access to your account.
        </p>
      </div>

      <Card title="Change Password" description="Leave these fields blank to keep your current password.">
        <form onSubmit={changePassword} className="space-y-3">
          <div className="space-y-1.5">
            <label htmlFor="current_password" className="text-sm font-medium text-foreground">
              Current Password
            </label>
            <PasswordInput id="current_password" placeholder="Enter current password" value={current} onChange={setCurrent} />
          </div>
          <div className="space-y-1.5">
            <label htmlFor="new_password" className="text-sm font-medium text-foreground">
              New Password
            </label>
            <PasswordInput id="new_password" placeholder="Enter new password" value={next} onChange={setNext} />
          </div>
          <div className="space-y-1.5">
            <label htmlFor="confirm_password" className="text-sm font-medium text-foreground">
              Confirm New Password
            </label>
            <PasswordInput id="confirm_password" placeholder="Re-enter new password" value={confirm} onChange={setConfirm} />
          </div>
          <p className="text-xs text-muted-foreground">
            Use at least 8 characters, including upper and lower case letters, a number and a symbol.
          </p>
          <Button
            type="submit"
            disabled={changing || !current || !next}
            className="bg-[#FF7700] text-white hover:bg-[#e66b00]"
          >
            {changing ? "Changing..." : "Change Password"}
          </Button>
        </form>
      </Card>

      <Card
        title="Two-Factor Authentication"
        description="Use an authenticator app to verify your identity when signing in."
        action={
          <div className="flex shrink-0 items-center gap-3">
            <span className="text-sm text-muted-foreground">
              {user?.two_factor_enabled ? "Enabled" : "Not enabled"}
            </span>
            <Button variant="outline" size="sm" asChild>
              <a href="/main/settings?tab=security&setup=2fa">
                {user?.two_factor_enabled ? "Manage 2FA" : "Set Up 2FA"}
              </a>
            </Button>
          </div>
        }
      />

      <Card
        title="Active Sessions"
        description="Sign out of sessions you no longer use or recognise."
        action={
          <Button
            variant="outline"
            size="sm"
            className="shrink-0 gap-1.5"
            disabled={revoking || others === 0}
            onClick={signOutOthers}
          >
            {revoking ? <Loader2 className="h-4 w-4 animate-spin" /> : <LogOut className="h-4 w-4" />}
            Sign Out Other Sessions
          </Button>
        }
      >
        {sessions === null ? (
          <div className="flex justify-center py-6">
            <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
          </div>
        ) : sessions.length === 0 ? (
          <p className="text-sm text-muted-foreground">No active sessions found.</p>
        ) : (
          <ul className="space-y-2">
            {sessions.map((session) => (
              <li
                key={session.id}
                className="flex items-center gap-3 rounded-md border border-border bg-muted/30 px-4 py-3"
              >
                <Monitor className="h-4 w-4 shrink-0 text-muted-foreground" />
                <div className="min-w-0 flex-1">
                  <div className="truncate text-sm font-medium text-foreground">
                    {session.current ? "This browser" : session.label}
                  </div>
                  <div className="truncate text-xs text-muted-foreground">
                    {[session.location, session.last_active_at ? new Date(session.last_active_at).toLocaleString() : null]
                      .filter(Boolean)
                      .join(" · ") || "Active now"}
                  </div>
                </div>
                {session.current && (
                  <span className="shrink-0 text-xs text-muted-foreground">Current session</span>
                )}
              </li>
            ))}
          </ul>
        )}
        <p className="mt-3 text-xs text-muted-foreground">
          Signing out other sessions will not sign you out of this browser.
        </p>
      </Card>

      <Card title="Personal Preferences" description="Choose how information is displayed in your account.">
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-foreground">Language</label>
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
              <label className="text-sm font-medium text-foreground">Time Zone</label>
              <Select
                value={timezone}
                onValueChange={(value) => {
                  setTimezone(value);
                  savePreference({ timezone: value });
                }}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select your time zone" />
                </SelectTrigger>
                <SelectContent>
                  {timezones.map((zone) => (
                    <SelectItem key={zone.value} value={zone.value}>
                      {zone.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                Dates and activity times will use your selected time zone.
              </p>
            </div>
          </div>

          {/* Dashboard currency was on the settings page before it was tabbed.
              The Security design does not show it, but it is a display
              preference and dropping it would take a working feature away, so
              it sits with the other two. */}
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-foreground">Dashboard Currency</label>
            <CurrencySelector />
          </div>

          <label htmlFor="signin_alerts" className="flex cursor-pointer items-start gap-3 pt-1">
            <Checkbox
              id="signin_alerts"
              checked={alerts}
              onCheckedChange={(checked) => saveAlerts(checked === true)}
              className="mt-0.5"
            />
            <span>
              <span className="block text-sm text-foreground">Email me about new account sign-ins</span>
              <span className="block text-xs text-muted-foreground">
                Security alerts help you spot unfamiliar activity. Essential account emails are always sent.
              </span>
            </span>
          </label>
        </div>
      </Card>

      <p className="flex items-center gap-2 text-xs text-muted-foreground">
        <ShieldCheck className="h-3.5 w-3.5" />
        Changes apply only to your account.
      </p>
    </div>
  );
}

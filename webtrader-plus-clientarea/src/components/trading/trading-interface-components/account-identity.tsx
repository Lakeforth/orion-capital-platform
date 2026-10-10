import useUserStore from "@/store/userStore";
import { cn } from "@/lib/utils";

/**
 * Whose account this trade room is: the client's name, the trading account
 * number and whether it is LIVE or DEMO. Staff land here through "Login as
 * Client" and need to see at a glance that it is that client's own account.
 */
/** Characters a name may take in the toolbar before it is abbreviated. */
const MAX_NAME = 18;

/**
 * Fit a name into the toolbar: keep the first name, reduce the rest to
 * initials, and only then cut characters.
 *
 * Truncation alone produced "QAImport October02T16...", which is the least
 * useful part of a name kept and the identifying part thrown away. First name
 * plus initials reads as a person at any width, and the full name is still on
 * the element's title.
 */
const shortenName = (full: string, max = MAX_NAME): string => {
    if (full.length <= max) return full;

    const [first = "", ...rest] = full.split(/\s+/).filter(Boolean);
    const initials = rest
        .map((part) => part[0]?.toUpperCase())
        .filter(Boolean)
        .map((letter) => `${letter}.`)
        .join(" ");

    if (!initials) return `${first.slice(0, Math.max(1, max - 1))}…`;

    const withInitials = `${first} ${initials}`;
    if (withInitials.length <= max) return withInitials;

    // Even the first name is too long on its own; cut it, keep the initials.
    const room = Math.max(1, max - initials.length - 2);
    return `${first.slice(0, room)}… ${initials}`;
};

export default function AccountIdentity({
    compact = false,
    className,
    variant = "full",
}: {
    compact?: boolean;
    className?: string;
    /**
     * Which part to render. The toolbar splits the block across two separate
     * controls -- the account switcher carries what varies per account (live,
     * balance, number) and the avatar menu carries the name, which is the same
     * on every account the client holds. Everywhere else still wants the whole
     * thing.
     */
    variant?: "full" | "status" | "name";
}) {
  const user = useUserStore((state) => state.user);
  const account = useUserStore((state) => state.activeTradingAccount);

  const name = [user?.first_name, user?.last_name].filter(Boolean).join(" ").trim() || "";
  const type = (account?.account_type || "").toLowerCase();
  // Brokerage demo accounts are persisted by the API as "practice".  Keep
  // accepting "demo" as a compatibility alias for older/external payloads.
  const isDemo = type === "practice" || type === "demo";

  if (!name && !account?.account_id) return null;

  const badge = account?.account_id ? (
    <span
      className={cn(
        "rounded px-1 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white",
        isDemo ? "bg-amber-500" : "bg-green-600"
      )}
    >
      {isDemo ? "Demo" : "Live"}
    </span>
  ) : null;

  // Just the badge: it sits with the balance now, not with the name.
  if (variant === "status") return badge;

  // Just the name. No account number with it: the number belongs to one
  // account and the name belongs to the client, and they now live under two
  // different controls.
  if (variant === "name") {
    if (!name) return null;

    return (
      <span
        className={cn(
          "max-w-[190px] truncate text-[13px] font-semibold leading-tight text-foreground notranslate",
          className
        )}
        title={name}
      >
        {shortenName(name)}
      </span>
    );
  }

  return (
    // Left-aligned, not centred: the name, the badge row and the balance are
    // three very different widths, and centring them is what made the block
    // look scattered -- nothing shared an edge.
    <div className={cn("flex flex-col min-w-0", compact ? "items-end" : "items-start gap-0.5", className)}>
      {name && (
        <div
          className={cn("font-semibold text-foreground truncate notranslate leading-tight", compact ? "text-[11px] max-w-[110px]" : "text-[13px] max-w-[190px]")}
          title={name}
        >
          {compact ? shortenName(user?.first_name || name, 12) : shortenName(name)}
        </div>
      )}
      {account?.account_id && (
        <div className="flex items-center gap-1 text-[10px] font-semibold">
          <span
            className={cn(
              "rounded px-1 py-0.5 uppercase tracking-wide text-white",
              isDemo ? "bg-amber-500" : "bg-green-600"
            )}
          >
            {isDemo ? "Demo" : "Live"}
          </span>
          {!compact && (
            <span className="text-muted-foreground border border-border rounded px-1 py-0.5">
              #{account.account_id}
            </span>
          )}
        </div>
      )}
    </div>
  );
}

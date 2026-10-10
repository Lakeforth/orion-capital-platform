/**
 * Where the Profile and KYC entry points go, in the web trader and the client
 * area sidebar alike.
 *
 * The clients asked for both to leave the terminal and open the client area's
 * Settings page full width, instead of the dialogs that used to open over the
 * chart. The dialogs are still here and still work -- this is a switch, not a
 * demolition, because the ask could come back the other way.
 *
 * To put the dialogs back: set OPENS_CLIENT_AREA to false. Nothing else has to
 * change; the components, their state and their wiring are untouched.
 */
export const OPENS_CLIENT_AREA = true;

/**
 * The Settings tab each entry point lands on.
 *
 * `kyc` is unused by the web trader -- its menu is Profile only, since Profile
 * lands on Settings and KYC is a tab there. Kept because the client area's
 * sidebar uses the same destination, and because the menu item is a few lines
 * away if it is ever wanted back.
 */
export const CLIENT_AREA_PATHS = {
  profile: "/main/settings?tab=account",
  kyc: "/main/settings?tab=kyc",
  withdrawal: "/main/withdrawal",
} as const;

/**
 * Whether this particular session can actually be sent to the client area.
 *
 * A client who signed in on the web trader's own form has no client-area
 * session to return to, so sending them there lands on a login screen. Those
 * sessions keep the dialog however the switch above is set -- better the old
 * behaviour than a dead end.
 */
export const canOpenClientArea = (hasClientAreaSession: boolean): boolean =>
  OPENS_CLIENT_AREA && hasClientAreaSession;

/**
 * Whether the client area's own KYC sidebar item goes to the Settings tab.
 *
 * Same bargain as above: false sends it back to the standalone /main/verification
 * page, which is still in the tree and still works. With it true that route
 * redirects, so a bookmark lands on the tab rather than on a page nothing links
 * to any more.
 */
export const KYC_SIDEBAR_OPENS_SETTINGS = true;

/**
 * Whether the web trader's Withdraw button leaves for the client area.
 *
 * Same bargain again: false reopens the withdrawal dialog over the terminal,
 * which is still built and still wired. A session with no client area to
 * return to keeps the dialog either way.
 */
export const WITHDRAW_OPENS_CLIENT_AREA = true;

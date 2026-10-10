/**
 * Timezone for the TradingView chart widgets: the viewer's own clock, so the
 * candles line up with the time on their screen. The widgets were pinned to
 * "Etc/UTC", which put the chart an hour (or more) behind for anyone outside
 * UTC -- e.g. 11:25 on the chart against 12:25 on the clock in UTC+1.
 */
export function chartTimeZone(): string {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || "Etc/UTC";
  } catch {
    return "Etc/UTC";
  }
}

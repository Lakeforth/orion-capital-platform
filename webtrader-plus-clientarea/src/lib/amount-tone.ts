/**
 * The colour a money figure is shown in.
 *
 * Profit green, loss red, and nothing grey. Zero used to take the green branch
 * everywhere, because every one of these reads was written as `value >= 0`, so
 * a client who had never placed a trade opened the terminal to a green $0.00
 * balance and a green $0.00 PnL -- green reading as "you are up" on an account
 * that is simply empty.
 *
 * The threshold is half a cent, not exact zero: these figures are rendered to
 * two decimals, and a PnL of 0.0004 displayed as "$0.00" in green would be the
 * same lie in a smaller font. The colour should answer for the number on
 * screen, not the float behind it.
 */
const ZERO = 0.005;

export const amountTone = (
  value: number,
  { zero = "text-muted-foreground", positive = "text-green-500", negative = "text-red-500" } = {},
): string => {
  if (!Number.isFinite(value) || Math.abs(value) < ZERO) return zero;
  return value > 0 ? positive : negative;
};

/** True when a figure rounds to zero at the precision it is displayed with. */
export const isZeroAmount = (value: number): boolean =>
  !Number.isFinite(value) || Math.abs(value) < ZERO;

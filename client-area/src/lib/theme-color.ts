const HEX_RE = /^#[0-9A-Fa-f]{6}$/

/**
 * WCAG relative-luminance based black/white pick, so an admin-chosen
 * background color (sidebar or accent) always gets a readable label on top
 * of it instead of a fixed white that can vanish against a light pick.
 */
export function readableTextColor(hex?: string | null, fallback = "#FFFFFF"): string {
  if (!hex || !HEX_RE.test(hex)) return fallback

  const r = parseInt(hex.slice(1, 3), 16)
  const g = parseInt(hex.slice(3, 5), 16)
  const b = parseInt(hex.slice(5, 7), 16)

  const [rl, gl, bl] = [r, g, b].map((c) => {
    const s = c / 255
    return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4)
  })
  const luminance = 0.2126 * rl + 0.7152 * gl + 0.0722 * bl

  return luminance > 0.45 ? "#0F172A" : "#FFFFFF"
}

export function isValidHexColor(hex?: string | null): hex is string {
  return !!hex && HEX_RE.test(hex)
}

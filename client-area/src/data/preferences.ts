// Choices for the Regional & Preferences block in the profile dialog.
//
// Both lists are deliberately short. They are the values /settings/preferences
// actually stores (users.locale and users.timezone), not a full IANA dump: a
// 400-entry timezone list in a dialog is worse than a dozen trading centres,
// and the locale column is only read for date and number formatting.

export const languages = [
  { value: "en-GB", label: "English (UK)" },
  { value: "en-US", label: "English (US)" },
  { value: "de", label: "Deutsch" },
  { value: "es", label: "Español" },
  { value: "fr", label: "Français" },
  { value: "it", label: "Italiano" },
  { value: "nl", label: "Nederlands" },
  { value: "pl", label: "Polski" },
  { value: "pt", label: "Português" },
  { value: "ru", label: "Русский" },
  { value: "tr", label: "Türkçe" },
  { value: "ar", label: "العربية" },
  { value: "zh", label: "中文" },
];

// Ordered west to east so the list reads like a clock rather than an alphabet.
export const timezones = [
  { value: "America/Los_Angeles", label: "(GMT-08:00) Los Angeles" },
  { value: "America/Chicago", label: "(GMT-06:00) Chicago" },
  { value: "America/New_York", label: "(GMT-05:00) New York" },
  { value: "America/Sao_Paulo", label: "(GMT-03:00) São Paulo" },
  { value: "UTC", label: "(GMT+00:00) UTC" },
  { value: "Europe/London", label: "(GMT+00:00) London" },
  { value: "Europe/Berlin", label: "(GMT+01:00) Berlin" },
  { value: "Europe/Paris", label: "(GMT+01:00) Paris" },
  { value: "Europe/Madrid", label: "(GMT+01:00) Madrid" },
  { value: "Europe/Warsaw", label: "(GMT+01:00) Warsaw" },
  { value: "Europe/Athens", label: "(GMT+02:00) Athens" },
  { value: "Europe/Istanbul", label: "(GMT+03:00) Istanbul" },
  { value: "Europe/Moscow", label: "(GMT+03:00) Moscow" },
  { value: "Asia/Dubai", label: "(GMT+04:00) Dubai" },
  { value: "Asia/Karachi", label: "(GMT+05:00) Karachi" },
  { value: "Asia/Kolkata", label: "(GMT+05:30) Mumbai" },
  { value: "Asia/Bangkok", label: "(GMT+07:00) Bangkok" },
  { value: "Asia/Singapore", label: "(GMT+08:00) Singapore" },
  { value: "Asia/Hong_Kong", label: "(GMT+08:00) Hong Kong" },
  { value: "Asia/Shanghai", label: "(GMT+08:00) Shanghai" },
  { value: "Asia/Tokyo", label: "(GMT+09:00) Tokyo" },
  { value: "Australia/Sydney", label: "(GMT+11:00) Sydney" },
  { value: "Pacific/Auckland", label: "(GMT+13:00) Auckland" },
];

export const languageLabel = (value?: string | null): string =>
  languages.find((l) => l.value === value)?.label ?? "";

export const timezoneLabel = (value?: string | null): string =>
  timezones.find((t) => t.value === value)?.label ?? "";

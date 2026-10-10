import useSiteSettingsStore from "@/store/siteSettingStore"

const recoveryModuleBypassed = () =>
  import.meta.env.VITE_RECOVERY_MODULE_BYPASS === "true"

export const isBitraceEnabledFromSettings = (bitraceEnabled?: boolean) =>
  recoveryModuleBypassed() || Boolean(bitraceEnabled)

export const useBitraceEnabled = () =>
  isBitraceEnabledFromSettings(
    useSiteSettingsStore((state) => state.settings?.bitrace_enabled),
  )

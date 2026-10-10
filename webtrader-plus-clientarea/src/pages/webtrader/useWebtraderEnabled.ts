import useSiteSettingsStore from "@/store/siteSettingStore"

export const isWebtraderEnabledFromSettings = (webtraderEnabled?: boolean) =>
  Boolean(webtraderEnabled)

export const useWebtraderEnabled = () =>
  isWebtraderEnabledFromSettings(
    useSiteSettingsStore((state) => state.settings?.webtrader_enabled),
  )

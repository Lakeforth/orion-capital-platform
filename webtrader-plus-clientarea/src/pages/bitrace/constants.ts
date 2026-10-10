import {
  BITRACE_CASES_PATH,
  BITRACE_DASHBOARD_PATH,
  BITRACE_PROFILE_PATH,
  BITRACE_SUPPORT_PATH,
} from "./bitrace-routes"

export type BitraceSidebarItem = {
  label: string
  href: string
  icon: "layout-dashboard" | "folder" | "user"
  active?: boolean
}

const getBitraceSidebarItemsBase = (): Omit<BitraceSidebarItem, "active">[] => [
  { label: "Dashboard", href: BITRACE_DASHBOARD_PATH, icon: "layout-dashboard" },
  { label: "My Cases", href: BITRACE_CASES_PATH, icon: "folder" },
  { label: "Profile", href: BITRACE_PROFILE_PATH, icon: "user" },
]

export const getBitraceSidebarItems = (activeLabel: string): BitraceSidebarItem[] =>
  getBitraceSidebarItemsBase().map((item) => ({
    ...item,
    active: item.label === activeLabel,
  }))

export const CASE_DETAIL_TABS = [
  "Overview",
  "Case Timeline",
  "Documents",
  "Communications",
] as const

export type CaseDetailTab = (typeof CASE_DETAIL_TABS)[number]

export const CASE_DETAIL_PROGRESS_STEPS = [
  { step: 1, label: "Case Opened", date: "15 May 2024" },
  { step: 2, label: "Under Review", date: "18 May 2024" },
  { step: 3, label: "Investigation", date: null },
  { step: 4, label: "Recovery", date: null },
  { step: 5, label: "Closed", date: null },
] as const

export const QUICK_ACTIONS = [
  { label: "Withdraw Case", icon: "trash", danger: true },
] as const

export const HOW_IT_WORKS_STEPS = [
  {
    step: "1. Submit Request",
    description: "Fill out our secure form with details about your case.",
    icon: "file-text",
  },
  {
    step: "2. Case Review",
    description: "Our experts review your submission and assess viability.",
    icon: "search",
  },
  {
    step: "3. Investigation",
    description: "We trace funds and gather evidence on your behalf.",
    icon: "trending-up",
  },
  {
    step: "4. Recovery",
    description: "We work to recover your funds and keep you updated.",
    icon: "shield-check",
  },
] as const

export const CASE_PROGRESS_STEPS = [
  "Case Opened",
  "Under Review",
  "Investigation",
  "Recovery",
  "Closed",
] as const

export const QUICK_LINKS = [
  { label: "View All Cases", icon: "folder", href: BITRACE_CASES_PATH },
  { label: "Update Profile", icon: "user", href: BITRACE_PROFILE_PATH },
  { label: "Contact Support", icon: "headphones", href: BITRACE_SUPPORT_PATH },
] as const

export const RECOVERY_STATUS_LABELS: Record<string, string> = {
  open: "Open",
  in_progress: "In Progress",
  completed: "Completed",
  closed: "Closed",
  withdrawn: "Withdrawn",
}

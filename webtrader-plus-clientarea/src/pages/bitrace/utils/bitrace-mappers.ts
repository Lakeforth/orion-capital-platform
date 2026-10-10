import { format, formatDistanceToNow, parseISO } from "date-fns"
import {
  BitraceActivity,
  BitraceActivityType,
  BitraceCaseStatus,
  BitraceCaseSummary,
  BitraceDocument,
} from "@/services/bitrace/bitrace-types"
import { CASE_PROGRESS_STEPS } from "../constants"

export { getBitraceCaseDetailPath } from "../bitrace-routes"

export const formatBitraceDate = (isoDate: string) => {
  try {
    return format(parseISO(isoDate), "d MMM yyyy")
  } catch {
    return isoDate
  }
}

export const formatBitraceRelativeTime = (isoDate: string) => {
  try {
    return formatDistanceToNow(parseISO(isoDate), { addSuffix: true })
  } catch {
    return isoDate
  }
}

export const getBitraceStatusLabel = (status: BitraceCaseStatus) => {
  const labels: Record<BitraceCaseStatus, string> = {
    open: "Open",
    in_progress: "In Progress",
    completed: "Completed",
    closed: "Closed",
    withdrawn: "Withdrawn",
  }
  return labels[status] ?? status
}

export const getBitraceStatusBadgeClass = (status: BitraceCaseStatus) => {
  const classes: Record<BitraceCaseStatus, string> = {
    open: "bg-[#EBF2FF] text-[#2563EB]",
    in_progress: "bg-[#FEF3C7] text-[#D97706]",
    completed: "bg-[#DCFCE7] text-[#15803D]",
    closed: "bg-[#F1F5F9] text-[#64748B]",
    withdrawn: "bg-[#FEE2E2] text-[#DC2626]",
  }
  return classes[status] ?? classes.open
}

export const getStageStepIndex = (currentStage?: string | null) => {
  if (!currentStage?.trim()) {
    return 0
  }

  const normalized = currentStage.trim().toLowerCase()

  if (normalized === "case opened" || normalized === "initial recovery" || normalized === "request submitted") {
    return 0
  }

  const index = CASE_PROGRESS_STEPS.findIndex(
    (step) => step.toLowerCase() === normalized,
  )
  return index >= 0 ? index : 0
}

export const getStageLegendCounts = (cases: BitraceCaseSummary[]) => {
  const stageLabels = ["Under Review", "Investigation", "Recovery", "Closed"]

  return stageLabels.map((label, index) => {
    const count = cases.filter((caseItem) => {
      const stepIndex = getStageStepIndex(caseItem.current_stage)
      if (label === "Under Review") return stepIndex <= 1
      if (label === "Investigation") return stepIndex === 2
      if (label === "Recovery") return stepIndex === 3
      return stepIndex >= 4 || caseItem.status === "closed"
    }).length

    return {
      label,
      count,
      color: count > 0 && index === 0 ? "#3B82F6" : "#94A3B8",
    }
  })
}

export const getActivityPresentation = (activity: BitraceActivity) => {
  const map: Record<
    BitraceActivityType,
    { icon: "check-circle" | "search" | "refresh" | "file"; iconColor: string; iconBg: string }
  > = {
    stage_change: {
      icon: "refresh",
      iconColor: "#2563EB",
      iconBg: "#DBEAFE",
    },
    submission: {
      icon: "file",
      iconColor: "#D97706",
      iconBg: "#FEF3C7",
    },
    admin_update: {
      icon: "check-circle",
      iconColor: "#15803D",
      iconBg: "#DCFCE7",
    },
    message: {
      icon: "search",
      iconColor: "#2563EB",
      iconBg: "#DBEAFE",
    },
    document_upload: {
      icon: "file",
      iconColor: "#22C55E",
      iconBg: "#DCFCE7",
    },
  }

  const presentation = map[activity.type] ?? map.stage_change

  return {
    id: activity.id,
    title: activity.description,
    description: `Updated by ${activity.actor}`,
    time: formatBitraceRelativeTime(activity.created_at),
    ...presentation,
  }
}

export const getDocumentLabel = (document: BitraceDocument, index: number) => {
  const title = document.title || document.name || document.type || `Document ${index + 1}`
  const subtitle = document.type || document.status || "Supporting document"
  const verified = document.status?.toLowerCase() === "verified"

  return { title, subtitle, verified }
}

export const shouldShowFundsLocatedAlert = (
  currentStage: string | null | undefined,
  status: BitraceCaseStatus,
) => {
  const stepIndex = getStageStepIndex(currentStage)
  return stepIndex >= 3 || status === "completed"
}

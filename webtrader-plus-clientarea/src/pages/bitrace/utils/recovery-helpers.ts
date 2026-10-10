import type { RecoveryCase, RecoveryPhase } from "@/services/recovery/recovery-types"
import type { BitraceCaseSummary, BitraceCaseStatus } from "@/services/bitrace/bitrace-types"

const ETHEREUM_ADDRESS_REGEX = /^0x[a-fA-F0-9]{40}$/

const TERMINAL_CASE_STATUSES = new Set<BitraceCaseStatus>(["closed", "withdrawn"])

const WITHDRAWABLE_CASE_STATUSES = new Set<BitraceCaseStatus>(["open", "in_progress"])

export const isValidEthereumAddress = (address: string): boolean =>
  ETHEREUM_ADDRESS_REGEX.test(address.trim())

export const getActivePhase = (recoveryCase?: RecoveryCase | null): RecoveryPhase | null =>
  recoveryCase?.phases?.find((phase) => phase.is_active) ?? null

export const isRecoveryCaseWithdrawn = (status?: string): boolean => status === "withdrawn"

export const hasActiveRecoveryCase = (cases: BitraceCaseSummary[]): boolean =>
  cases.some((caseItem) => !TERMINAL_CASE_STATUSES.has(caseItem.status))

export const canWithdrawRecoveryCase = (status: BitraceCaseStatus): boolean =>
  WITHDRAWABLE_CASE_STATUSES.has(status)

export const getWithdrawableRecoveryCases = (cases: BitraceCaseSummary[]): BitraceCaseSummary[] =>
  cases.filter((caseItem) => canWithdrawRecoveryCase(caseItem.status))

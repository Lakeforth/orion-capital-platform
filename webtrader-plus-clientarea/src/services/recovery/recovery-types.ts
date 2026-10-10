export type RecoveryActionSlug = "submit_wallet_details" | "test_transfer_request"

export type RecoveryCaseStatus =
  | "open"
  | "in_progress"
  | "completed"
  | "closed"
  | "withdrawn"

export type RecoveryActionStatus = "pending" | "completed" | "skipped"

export interface RecoveryActionType {
  id: string
  name: string
  slug: RecoveryActionSlug
}

export interface RecoveryAction {
  id: string
  phase_id: string
  action_type_id: string
  status: RecoveryActionStatus
  action_type: RecoveryActionType
}

export interface RecoveryPhase {
  id: string
  name: string
  order: number
  status: "pending" | "active" | "completed"
  is_active: boolean
  activated_at?: string | null
  completed_at?: string | null
  actions: RecoveryAction[]
}

export type RecoveryTransferStatus = "pending" | "initiated" | "completed" | "rejected"

export interface RecoveryTransferRequest {
  id: string
  user_id: string
  case_id: string
  phase_action_id: string
  status: RecoveryTransferStatus
  created_at?: string
}

export interface RecoveryCase {
  id: string
  company_id: number
  user_id: string
  case_number: string
  title: string
  status: RecoveryCaseStatus
  current_stage?: string | null
  eth_wallet_address?: string | null
  phases: RecoveryPhase[]
  transfer_requests?: RecoveryTransferRequest[]
}

export interface RecoveryWalletEntry {
  id: string
  wallet_address: string
  is_current: boolean
  case_id?: string | null
  created_at: string
}

export interface RecoveryApiResponse<T> {
  status: "success" | "failed"
  message: string
  data: T
}

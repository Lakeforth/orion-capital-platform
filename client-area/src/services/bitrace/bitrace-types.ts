export type BitraceCaseStatus = "open" | "in_progress" | "completed" | "closed" | "withdrawn"

export type BitraceActivityType =
  | "stage_change"
  | "message"
  | "document_upload"
  | "submission"
  | "admin_update"

export interface BitraceCaseSummary {
  id: string
  case_number: string
  title: string
  status: BitraceCaseStatus
  current_stage: string | null
  created_at: string
  updated_at: string
}

export interface BitraceActivity {
  id: string
  case_id: string
  type: BitraceActivityType
  description: string
  actor: string
  created_at: string
  updated_at?: string
}

export interface BitraceDocument {
  id?: string
  name?: string
  title?: string
  type?: string
  status?: string
  file_path?: string
  created_at?: string
}

export interface BitraceCaseDetail extends BitraceCaseSummary {
  eth_wallet_address: string | null
  notes: string | null
  activities: BitraceActivity[]
  documents: BitraceDocument[]
}

export interface BitraceApiResponse<T> {
  status: string
  message: string
  data: T
}

export interface BitraceWalletUpdatePayload {
  eth_wallet_address: string
}

export interface BitraceWalletUpdateResponse {
  eth_wallet_address: string
}

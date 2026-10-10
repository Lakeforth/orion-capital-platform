export const TICKET_CATEGORIES = ["billing", "technical", "verification", "payouts", "challenge_rules", "other"] as const
export type TicketCategory = (typeof TICKET_CATEGORIES)[number]

export const TICKET_STATUSES = ["open", "in_progress", "resolved", "closed"] as const
export type TicketStatus = (typeof TICKET_STATUSES)[number]

export interface TicketAttachment {
  id: number
  path: string
  filename: string
  size: number
}

export interface TicketReply {
  id: string
  ticket_id: string
  author_type: "user" | "admin"
  author_id: string | null
  message: string
  attachments: TicketAttachment[]
  created_at: string
}

export interface SupportTicket {
  id: string
  category: TicketCategory
  subject: string
  status: TicketStatus
  challenge_id: string | null
  replies_count?: number
  replies?: TicketReply[]
  created_at: string
  updated_at: string
}

export interface PaginatedTickets {
  data: SupportTicket[]
  current_page: number
  last_page: number
  total: number
}

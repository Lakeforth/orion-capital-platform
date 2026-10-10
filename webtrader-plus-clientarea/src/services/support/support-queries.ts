import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"
import axiosInstance from "@/lib/axios"
import { PaginatedTickets, SupportTicket, TicketReply } from "./support-types"

interface ApiEnvelope<T> {
  status: string
  message: string
  data: T
}

const TICKETS_KEY = ["support", "tickets"] as const

const fetchTickets = async (): Promise<PaginatedTickets> => {
  const response = await axiosInstance.get<ApiEnvelope<PaginatedTickets>>("/support/tickets")
  return response.data.data
}

export const useSupportTicketsQuery = () =>
  useQuery({
    queryKey: TICKETS_KEY,
    queryFn: fetchTickets,
  })

const fetchTicket = async (id: string): Promise<SupportTicket> => {
  const response = await axiosInstance.get<ApiEnvelope<SupportTicket>>(`/support/tickets/${id}`)
  return response.data.data
}

export const useSupportTicketQuery = (id: string | null) =>
  useQuery({
    queryKey: [...TICKETS_KEY, id],
    queryFn: () => fetchTicket(id as string),
    enabled: Boolean(id),
  })

export interface CreateTicketInput {
  category: string
  subject: string
  message: string
  challenge_id?: string
  attachments?: File[]
}

const createTicket = async (input: CreateTicketInput): Promise<SupportTicket> => {
  const form = new FormData()
  form.append("category", input.category)
  form.append("subject", input.subject)
  form.append("message", input.message)
  if (input.challenge_id) form.append("challenge_id", input.challenge_id)
  ;(input.attachments ?? []).forEach((file) => form.append("attachments[]", file))

  const response = await axiosInstance.post<ApiEnvelope<SupportTicket>>("/support/tickets", form, {
    headers: { "Content-Type": undefined },
  })
  return response.data.data
}

export const useCreateSupportTicketMutation = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createTicket,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TICKETS_KEY })
    },
  })
}

const replyTicket = async ({ id, message, attachments }: { id: string; message: string; attachments?: File[] }): Promise<TicketReply> => {
  const form = new FormData()
  form.append("message", message)
  ;(attachments ?? []).forEach((file) => form.append("attachments[]", file))

  const response = await axiosInstance.post<ApiEnvelope<TicketReply>>(`/support/tickets/${id}/reply`, form, {
    headers: { "Content-Type": undefined },
  })
  return response.data.data
}

export const useReplySupportTicketMutation = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: replyTicket,
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: [...TICKETS_KEY, variables.id] })
      queryClient.invalidateQueries({ queryKey: TICKETS_KEY })
    },
  })
}

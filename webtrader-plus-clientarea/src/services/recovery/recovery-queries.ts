import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"
import axiosInstance from "@/lib/axios"
import type {
  RecoveryApiResponse,
  RecoveryCase,
  RecoveryWalletEntry,
} from "./recovery-types"

const RECOVERY_KEY = ["recovery"] as const

const fetchRecoveryCase = async (caseId: string): Promise<RecoveryCase> => {
  const res = await axiosInstance.get<RecoveryApiResponse<RecoveryCase>>(
    `/recovery/cases/${caseId}`,
  )
  return res.data.data
}

const fetchWalletHistory = async (caseId?: string): Promise<RecoveryWalletEntry[]> => {
  const res = await axiosInstance.get<RecoveryApiResponse<RecoveryWalletEntry[]>>(
    "/recovery/wallet/history",
    { params: caseId ? { case_id: caseId } : undefined },
  )
  return res.data.data ?? []
}

type SubmitWalletPayload = {
  wallet_address: string
  case_id?: string
}

const submitWalletAddress = async ({
  wallet_address,
  case_id,
}: SubmitWalletPayload): Promise<RecoveryWalletEntry> => {
  const res = await axiosInstance.post<RecoveryApiResponse<RecoveryWalletEntry>>(
    "/recovery/wallet",
    { wallet_address, ...(case_id ? { case_id } : {}) },
  )
  return res.data.data
}

const submitTransferRequest = async (phase_action_id: string) => {
  const res = await axiosInstance.post("/recovery/transfer-request", { phase_action_id })
  return res.data
}

const withdrawCase = async (caseId: string): Promise<RecoveryCase> => {
  const res = await axiosInstance.post<RecoveryApiResponse<RecoveryCase>>(
    `/recovery/cases/${caseId}/withdraw`,
  )
  return res.data.data
}

type CreateCasePayload = {
  title: string
  notes?: string
}

const createRecoveryCase = async (payload: CreateCasePayload): Promise<RecoveryCase> => {
  const res = await axiosInstance.post<RecoveryApiResponse<RecoveryCase>>(
    "/recovery/cases",
    payload,
  )
  return res.data.data
}

export const useRecoveryCaseQuery = (caseId: string | undefined, enabled = true) =>
  useQuery({
    queryKey: [...RECOVERY_KEY, "case", caseId],
    queryFn: () => fetchRecoveryCase(caseId!),
    enabled: enabled && Boolean(caseId),
    retry: (failureCount, error) => {
      const status = (error as { response?: { status?: number } })?.response?.status
      if (status === 404) return false
      return failureCount < 2
    },
  })

export const useWalletHistoryQuery = (enabled = true, caseId?: string) =>
  useQuery({
    queryKey: [...RECOVERY_KEY, "wallet-history", caseId ?? "all"],
    queryFn: () => fetchWalletHistory(caseId),
    enabled,
  })

const BITRACE_CASES_KEY = ["bitrace", "cases"] as const

const invalidateRecoveryCaseQueries = (
  qc: ReturnType<typeof useQueryClient>,
  caseId?: string,
) => {
  if (caseId) {
    qc.invalidateQueries({ queryKey: [...RECOVERY_KEY, "case", caseId] })
    qc.invalidateQueries({ queryKey: [...BITRACE_CASES_KEY, caseId] })
    qc.invalidateQueries({ queryKey: [...BITRACE_CASES_KEY, caseId, "activities"] })
  } else {
    qc.invalidateQueries({ queryKey: [...RECOVERY_KEY, "case"] })
  }
  qc.invalidateQueries({ queryKey: BITRACE_CASES_KEY })
}

export const useSubmitWalletMutation = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: submitWalletAddress,
    onSuccess: (_data, variables) => {
      invalidateRecoveryCaseQueries(qc, variables.case_id)
      qc.invalidateQueries({ queryKey: [...RECOVERY_KEY, "wallet-history"] })
    },
  })
}

export const useSubmitTransferRequestMutation = (caseId?: string) => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: submitTransferRequest,
    onSuccess: () => {
      invalidateRecoveryCaseQueries(qc, caseId)
    },
  })
}

export const useWithdrawCaseMutation = (caseId: string | undefined) => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => withdrawCase(caseId!),
    onSuccess: () => {
      if (caseId) {
        qc.invalidateQueries({ queryKey: [...RECOVERY_KEY, "case", caseId] })
      }
      qc.invalidateQueries({ queryKey: ["bitrace", "cases"] })
    },
  })
}

export const useCreateRecoveryCaseMutation = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createRecoveryCase,
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: ["bitrace", "cases"] })
      qc.invalidateQueries({ queryKey: [...RECOVERY_KEY, "case", data.id] })
    },
  })
}

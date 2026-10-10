import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"
import axiosInstance from "@/lib/axios"
import {
  BitraceActivity,
  BitraceApiResponse,
  BitraceCaseDetail,
  BitraceCaseSummary,
  BitraceDocument,
  BitraceWalletUpdatePayload,
  BitraceWalletUpdateResponse,
} from "./bitrace-types"

const BITRACE_QUERY_KEY = ["bitrace", "cases"] as const

const fetchBitraceCases = async (): Promise<BitraceCaseSummary[]> => {
  const response = await axiosInstance.get<BitraceApiResponse<BitraceCaseSummary[]>>(
    "/bitrace/cases",
  )
  return response.data.data ?? []
}

const fetchBitraceCase = async (caseId: string): Promise<BitraceCaseDetail> => {
  const response = await axiosInstance.get<BitraceApiResponse<BitraceCaseDetail>>(
    `/bitrace/cases/${caseId}`,
  )
  return response.data.data
}

const fetchBitraceCaseActivities = async (
  caseId: string,
): Promise<BitraceActivity[]> => {
  const response = await axiosInstance.get<BitraceApiResponse<BitraceActivity[]>>(
    `/bitrace/cases/${caseId}/activities`,
  )
  return response.data.data ?? []
}

const updateBitraceCaseWallet = async ({
  caseId,
  eth_wallet_address,
}: BitraceWalletUpdatePayload & { caseId: string }) => {
  const response = await axiosInstance.post<
    BitraceApiResponse<BitraceWalletUpdateResponse>
  >(`/bitrace/cases/${caseId}/wallet`, { eth_wallet_address })
  return response.data.data
}

const uploadBitraceCaseDocument = async ({
  caseId,
  file,
  name,
}: {
  caseId: string
  file: File
  name?: string
}) => {
  const formData = new FormData()
  formData.append("file", file)
  if (name) {
    formData.append("name", name)
  }
  const response = await axiosInstance.post<BitraceApiResponse<BitraceDocument>>(
    `/bitrace/cases/${caseId}/documents`,
    formData,
    { headers: { "Content-Type": "multipart/form-data" } },
  )
  return response.data.data
}

export const useBitraceCasesQuery = (enabled = true) => {
  return useQuery({
    queryKey: BITRACE_QUERY_KEY,
    queryFn: fetchBitraceCases,
    enabled,
  })
}

export const useBitraceCaseQuery = (caseId: string | undefined, enabled = true) => {
  return useQuery({
    queryKey: [...BITRACE_QUERY_KEY, caseId],
    queryFn: () => fetchBitraceCase(caseId!),
    enabled: enabled && Boolean(caseId),
  })
}

export const useBitraceCaseActivitiesQuery = (
  caseId: string | undefined,
  enabled = true,
) => {
  return useQuery({
    queryKey: [...BITRACE_QUERY_KEY, caseId, "activities"],
    queryFn: () => fetchBitraceCaseActivities(caseId!),
    enabled: enabled && Boolean(caseId),
  })
}

export const useBitraceWalletMutation = (caseId: string) => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: BitraceWalletUpdatePayload) =>
      updateBitraceCaseWallet({ caseId, ...payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...BITRACE_QUERY_KEY, caseId] })
      queryClient.invalidateQueries({ queryKey: BITRACE_QUERY_KEY })
    },
  })
}

export const useUploadBitraceDocumentMutation = (caseId: string) => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: { file: File; name?: string }) =>
      uploadBitraceCaseDocument({ caseId, ...payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...BITRACE_QUERY_KEY, caseId] })
      queryClient.invalidateQueries({ queryKey: [...BITRACE_QUERY_KEY, caseId, "activities"] })
    },
  })
}

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import type {
  Partner,
  PartnerPayload,
  PartnerResponse,
  PartnersResponse,
} from "./partner-types";

const PARTNERS_QUERY_KEY = ["partners"] as const;

// ===== FETCH FUNCTIONS =====

const fetchPartners = async (): Promise<Partner[]> => {
  const response = await axiosInstance.get<PartnersResponse>("/account/partners");
  return response.data.data ?? [];
};

const createPartner = async (payload: PartnerPayload): Promise<Partner> => {
  const form = new FormData();
  form.append("name", payload.name);
  form.append("email", payload.email);
  form.append("phone", payload.phone);
  form.append("address", payload.address);
  form.append("country", payload.country);
  const response = await axiosInstance.post<PartnerResponse>("/account/partners", form);
  return response.data.data;
};

const updatePartner = async ({
  id,
  ...payload
}: { id: string } & PartnerPayload): Promise<Partner> => {
  const response = await axiosInstance.put<PartnerResponse>(
    `/account/partners/${id}`,
    payload,
  );
  return response.data.data;
};

const deletePartner = async (id: string): Promise<void> => {
  await axiosInstance.delete(`/account/partners/${id}`);
};

// ===== QUERY HOOKS =====

export const usePartnersQuery = (enabled = true) =>
  useQuery({
    queryKey: PARTNERS_QUERY_KEY,
    queryFn: fetchPartners,
    enabled,
  });

// ===== MUTATION HOOKS =====

export const useCreatePartnerMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createPartner,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PARTNERS_QUERY_KEY });
    },
  });
};

export const useUpdatePartnerMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: updatePartner,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PARTNERS_QUERY_KEY });
    },
  });
};

export const useDeletePartnerMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: deletePartner,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PARTNERS_QUERY_KEY });
    },
  });
};

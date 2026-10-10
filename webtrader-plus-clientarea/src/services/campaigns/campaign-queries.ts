import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import { CampaignResponse } from "./campaign-types";

const fetchCampaign = async (uuid: string): Promise<CampaignResponse> => {
  const response = await axiosInstance.get<CampaignResponse>(`/leads/campaigns/${uuid}`);
  return response.data;
};

export const useCampaignQuery = (uuid: string | null) =>
  useQuery({
    queryKey: ["campaign", uuid],
    queryFn: () => fetchCampaign(uuid!),
    enabled: !!uuid,
  });

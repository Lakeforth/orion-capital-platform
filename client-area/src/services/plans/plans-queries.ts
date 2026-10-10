import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import type { PlansResponse } from "./plans-types";

const fetchPlans = async (): Promise<PlansResponse> => {
  const response = await axiosInstance.get<PlansResponse>("/plans");
  return response.data;
};

export const usePlansQuery = () =>
  useQuery({
    queryKey: ["plans"],
    queryFn: fetchPlans,
  });

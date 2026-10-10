import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import type { PortfolioSummary, PortfolioSummaryResponse } from "./portfolio-types";

export const PORTFOLIO_SUMMARY_QUERY_KEY = ["portfolio-summary"] as const;

const fetchPortfolioSummary = async (): Promise<PortfolioSummary> => {
  const response = await axiosInstance.get<PortfolioSummaryResponse>(
    "/portfolio-summary",
  );
  return response.data.data;
};

export const usePortfolioSummaryQuery = (enabled = true) =>
  useQuery({
    queryKey: PORTFOLIO_SUMMARY_QUERY_KEY,
    queryFn: fetchPortfolioSummary,
    enabled,
  });

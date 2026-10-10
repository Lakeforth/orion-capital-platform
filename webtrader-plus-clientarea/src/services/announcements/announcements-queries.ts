import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import { AnnouncementsResponse } from "./announcements-types";

// Fetch logic
const fetchAnnouncements = async (): Promise<AnnouncementsResponse> => {
  const response = await axiosInstance.get<AnnouncementsResponse>("/announcements");
  return response.data;
};

// Query hook
export const useAnnouncementsQuery = () => {
  return useQuery({
    queryKey: ["announcements"],
    queryFn: fetchAnnouncements,
  });
};

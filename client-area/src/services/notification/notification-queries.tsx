import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import {
  MarkReadResponse,
  NotificationPreference,
  NotificationPreferencesData,
  NotificationResponse,
  ReadAllResponse,
} from "./notification-types";

interface ApiEnvelope<T> {
  status: string;
  message: string;
  data: T;
}

//============================ Get Notifications =================================
// Fetch logic
const fetchNotifications = async (page = 1): Promise<NotificationResponse> => {
  const response = await axiosInstance.get<NotificationResponse>(
    `/notifications?page=${page}`,
  );
  return response.data;
};

// Query hook
export const useNotificationsQuery = (page = 1, options = {}) => {
  return useQuery({
    queryKey: ["notifications", page],
    queryFn: () => fetchNotifications(page),
    ...options,
  });
};

//========================== Read Notification ===========================
// Fetch logic
const markAsRead = async (id: string): Promise<MarkReadResponse> => {
  const response = await axiosInstance.post<MarkReadResponse>(
    `/notifications/${id}/read`,
  );
  return response.data;
};

// Mutation hook
export const useMarkReadMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: markAsRead,
    onSuccess: () => {
      // Invalidate notifications query to refresh the list and unread count
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
};

//========================== Read All Notifications ===========================
// Fetch logic
const readAllNotifications = async (): Promise<ReadAllResponse> => {
  const response = await axiosInstance.post<ReadAllResponse>(
    "/notifications/read",
  );
  return response.data;
};

// Mutation hook
export const useReadAllNotificationsMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: readAllNotifications,
    onSuccess: () => {
      // Invalidate notifications query to refresh the list and unread count
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
};

//========================== Notification Preferences ===========================
const fetchNotificationPreferences = async (): Promise<NotificationPreferencesData> => {
  const response = await axiosInstance.get<ApiEnvelope<NotificationPreferencesData>>(
    "/notifications/preferences",
  );
  return response.data.data;
};

export const useNotificationPreferencesQuery = () =>
  useQuery({
    queryKey: ["notification-preferences"],
    queryFn: fetchNotificationPreferences,
  });

const updateNotificationPreferences = async (
  preferences: NotificationPreference[],
): Promise<void> => {
  await axiosInstance.post("/notifications/preferences", { preferences });
};

export const useUpdateNotificationPreferencesMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: updateNotificationPreferences,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notification-preferences"] });
    },
  });
};

//========================== Telegram Connect/Disconnect ===========================
const connectTelegram = async (): Promise<{ deep_link: string; expires_in_minutes: number }> => {
  const response = await axiosInstance.post<
    ApiEnvelope<{ deep_link: string; expires_in_minutes: number }>
  >("/notifications/telegram/connect");
  return response.data.data;
};

export const useConnectTelegramMutation = () => useMutation({ mutationFn: connectTelegram });

const disconnectTelegram = async (): Promise<void> => {
  await axiosInstance.delete("/notifications/telegram");
};

export const useDisconnectTelegramMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: disconnectTelegram,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notification-preferences"] });
    },
  });
};

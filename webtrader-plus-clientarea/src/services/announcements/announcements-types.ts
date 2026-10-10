export interface Announcement {
  id: string;
  title: string;
  category: string;
  body: string;
  priority: number;
  starts_at: string | null;
  ends_at: string | null;
  created_at: string;
}

export interface AnnouncementsResponse {
  status: "success" | "error";
  message: string;
  data: {
    enabled: boolean;
    items: Announcement[];
  };
}

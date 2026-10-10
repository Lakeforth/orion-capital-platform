export interface CampaignData {
  id: string;
  name: string;
  source: string | null;
  language: string | null;
  whatsappNumber: string | null;
  whatsappMessage: string | null;
  whatsappLink: string | null;
}

export interface CampaignResponse {
  status: boolean;
  message: string;
  data: CampaignData;
}

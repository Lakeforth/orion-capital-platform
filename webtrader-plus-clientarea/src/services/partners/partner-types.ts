export interface Partner {
  id: string;
  user_id: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  country: string;
  custom: unknown | null;
  created_at: string;
  updated_at: string;
}

export interface PartnersResponse {
  status: string;
  message: string;
  data: Partner[];
}

export interface PartnerResponse {
  status: string;
  message: string;
  data: Partner;
}

export interface PartnerPayload {
  name: string;
  email: string;
  phone: string;
  address: string;
  country: string;
}

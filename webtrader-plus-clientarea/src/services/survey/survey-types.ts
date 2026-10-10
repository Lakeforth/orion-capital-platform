export interface SurveyRequest {
  campaign: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  scam_type: string;
  amount_lost: number;
  scam_payment_method: string;
  scam_first_payment: string;
}

export interface PlanFeatureDefinition {
  id: number;
  index: number;
  name: string;
  icon_key: string;
  use_image: boolean;
  image_url: string | null;
}

export interface Plan {
  id: string;
  title: string;
  color: string | null;
  icon: string;
  image: string;
  leverage: number;
  features: string[];
  expert_advisors: string[] | null;
  description: string | null;
  min_deposit: number;
  price: number;
}

export interface PlansData {
  plans: Plan[];
  plan_features: string[];
  plan_feature_definitions: PlanFeatureDefinition[];
}

export interface PlansResponse {
  status: "success" | "error";
  message: string;
  data: PlansData;
}

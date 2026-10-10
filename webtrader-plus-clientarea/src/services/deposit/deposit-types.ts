export interface DepositRequest {
  amount: number;
  // "card" is the customer-facing label. Whether it runs through hosted
  // checkout or stays a manually-approved request is a per-tenant setting the
  // backend resolves; the client sends the same thing either way.
  method: "card" | "crypto" | "interac";
  card_holder_name?: string;
  wallet_id?: string | null;
  crypto?: string;
  crypto_network?: string;
  // Optional screenshot/receipt of the transfer, attached on the confirm step
  // of the wallet funding flow. Sent as multipart when present.
  payment_proof?: File | null;
  // "spot_wallet" (default) or "trading_account:{id}" -- lets the client
  // choose to fund the Spot Wallet directly or deposit straight to one of
  // their trading accounts, bypassing it. Only meaningful once the tenant
  // has wallet_components_enabled; ignored/defaults to spot_wallet otherwise.
  destination?: string;
}

export interface DepositResponseData {
  id: string;
  order_id: number;
  // Present when the deposit has to be completed somewhere else -- hosted
  // card checkout, or the existing WhatsApp handoff. When it is set the
  // deposit is created but unpaid, so the caller must navigate rather than
  // show a success state.
  redirect_url?: string | null;
  redirect?: boolean;
  [key: string]: unknown;
}

export interface DepositResponse {
  status: "success" | "error";
  message: string;
  data: DepositResponseData;
}

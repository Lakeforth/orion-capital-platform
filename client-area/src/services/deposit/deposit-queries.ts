import { useMutation } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import { DepositRequest, DepositResponse } from "./deposit-types";

// Fetch logic
const submitDeposit = async (
  data: DepositRequest,
): Promise<DepositResponse> => {
  const { payment_proof, ...fields } = data;

  // A proof-of-payment screenshot is optional, so the plain JSON post stays
  // the default shape and only a submission that actually carries a file
  // switches to multipart.
  if (payment_proof) {
    const form = new FormData();

    Object.entries(fields).forEach(([key, value]) => {
      if (value === undefined || value === null) return;
      form.append(key, String(value));
    });

    form.append("payment_proof", payment_proof);

    const response = await axiosInstance.post<DepositResponse>(
      "/user/deposit/store",
      form,
      { headers: { "Content-Type": undefined } },
    );
    return response.data;
  }

  const response = await axiosInstance.post<DepositResponse>(
    "/user/deposit/store",
    fields,
  );
  return response.data;
};

// Query hook
export const useDepositMutation = () => {
  return useMutation({
    mutationFn: submitDeposit,
  });
};

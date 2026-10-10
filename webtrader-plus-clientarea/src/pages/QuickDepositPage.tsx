import { useNavigate } from "react-router-dom";
import DepositFunds from "@/components/deposit-funds/DepositFunds";

export default function QuickDepositPage() {
  const navigate = useNavigate();

  return (
    <>
      <div className="fixed inset-0 bg-[#0a0f1e]" />
      <DepositFunds
        isOpen={true}
        onClose={() => navigate("/")}
      />
    </>
  );
}

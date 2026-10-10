import { useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import useUserStore, { User } from "@/store/userStore";
import { replaceAuthenticatedUser } from "@/lib/session";
import LoadingScreen from "@/components/loading-screen";
import { toast } from "@/components/ui/sonner";

export default function AutoLoginPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const getCurrentUser = useUserStore((state) => state.getCurrentUser);

  useEffect(() => {
    const handleAutoLogin = async () => {
      try {
        const token = searchParams.get("token");
        const redirectPath = searchParams.get("path") || "/main/dashboard"; // <-- Added this line

        if (!token) {
          toast.error("No login token provided");
          navigate("/");
          return;
        }

        const dummyUser: User = {
          id: "temp",
          account_id: "temp",
          first_name: "Loading",
          last_name: "User",
          email: "loading@example.com",
          phone: "",
          address: "",
          country_code: null,
          country: null,
          username: null,
          avatar: "null",
          balance: 0,
          savings_balance: 0,
          credit_balance: 0,
          copy_trader: 0,
          autotrader: false,
          autotrader_status: "",
          ai_trade: false,
          verification_status: "",
          notification_msg: null,
          status: "",
          can_open_trade: false,
          can_close_trade: false,
          trades_summary: {
            total_pnl: 0,
            total_wins: 0,
            total_losses: 0,
            total_deposit: 0,
            trades_count: 0,
            win_rate: 0,
          },
          account_type: {
            id: "",
            title: "",
            leverage: 0,
            icon: "",
            color: "",
            image: "",
            expert_advisors: [], // Adding expert_advisors array
          },
          accounts: [],
          notifications: [],
        };

        replaceAuthenticatedUser(dummyUser, token);

        console.log("token", token);

        await getCurrentUser();

        navigate(redirectPath);
      } catch (error) {
        console.error("Auto login failed:", error);
        toast.error("Failed to auto login. Please try again.");
        navigate("/");
      }
    };

    handleAutoLogin();
  }, [searchParams, navigate, getCurrentUser]);

  return <LoadingScreen />;
}

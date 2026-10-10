import { useEffect, useRef } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import axiosInstance from "@/lib/axios";
import { replaceAuthenticatedUser } from "@/lib/session";
import { AxiosError } from "axios";
import { toast } from "@/components/ui/sonner";
import LoadingScreen from "@/components/loading-screen";

/**
 * The mirror of WebtraderLaunchConsumePage: consumes a single-use token
 * minted on the webtrader origin (TradingAccountController::
 * clientAreaLaunchToken) and exchanges it for a client-area session, so
 * "Back to Client Area" stops landing on a login form.
 *
 * Like its counterpart, and unlike the older auto-login.tsx, the token in the
 * query string is never the session credential itself -- it is scrubbed from
 * the address bar immediately and sent once, in a POST body, to be exchanged
 * for a real one.
 */
export default function ClientAreaLaunchConsumePage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const hasRun = useRef(false);

  useEffect(() => {
    if (hasRun.current) return;
    hasRun.current = true;

    const token = searchParams.get("token");
    // Where in the client area to land. Internal paths only: this comes off
    // the query string, so "//evil.example" or a full URL would make it an
    // open redirect.
    const requested = searchParams.get("path");
    const destination =
      requested && requested.startsWith("/") && !requested.startsWith("//")
        ? requested
        : "/main/dashboard";

    // Out of the address bar before anything else, so it cannot linger in a
    // shared or screen-shared tab.
    window.history.replaceState({}, "", "/launch");

    if (!token) {
      toast.error("Missing or invalid launch link.");
      navigate("/");
      return;
    }

    (async () => {
      try {
        const response = await axiosInstance.post("/auth/client-area/launch", { token });
        const { user, token: sessionToken, trading_account } = response.data.data;
        replaceAuthenticatedUser(user, sessionToken, trading_account);
        navigate(destination);
      } catch (error) {
        if (error instanceof AxiosError) {
          toast.error(
            error.response?.data?.message || "This link has expired or was already used.",
          );
        } else {
          toast.error("Could not open the client area. Please sign in.");
        }
        navigate("/");
      }
    })();
  }, [searchParams, navigate]);

  return <LoadingScreen />;
}

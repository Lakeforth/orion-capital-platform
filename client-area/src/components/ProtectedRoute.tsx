import { Navigate, useLocation } from "react-router-dom";
import useUserStore from "@/store/userStore";

interface ProtectedRouteProps {
  children: React.ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const user = useUserStore((state) => state.user);
  const token = useUserStore((state) => state.token);

  const location = useLocation();

  if (!user || !token) {
    // Save the location they tried to access for redirecting after login
    return <Navigate to="/" state={{ from: location }} replace />;
  }

  return children;
};

export default ProtectedRoute;

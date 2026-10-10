import { useLocation } from "react-router-dom";
import useDarkModeStore from "@/store/darkModeStore";

export const useStepNumberColor = (): string => {
  const location = useLocation();
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  // Default to dark theme for routes outside the light trading layout
  if (!location.pathname.includes("/mt4-light")) {
    return "text-muted-foreground";
  }

  return isDarkMode ? "text-muted-foreground" : "text-foreground";
};

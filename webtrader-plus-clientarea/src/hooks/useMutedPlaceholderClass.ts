import { useLocation } from "react-router-dom";
import useDarkModeStore from "@/store/darkModeStore";

export const useMutedPlaceholderClass = (): string => {
  const location = useLocation();
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  // Default to dark theme for routes outside the light trading layout
  if (!location.pathname.includes("/mt4-light")) {
    return "placeholder-muted-foreground";
  }

  return isDarkMode ? "placeholder-muted-foreground" : "placeholder-muted";
};

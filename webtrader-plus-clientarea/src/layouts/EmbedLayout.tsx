import { useEffect } from "react";
import { Outlet } from "react-router-dom";

export default function EmbedLayout() {
  useEffect(() => {
    document.documentElement.style.background = "white";
    document.body.style.background = "white";
    return () => {
      document.documentElement.style.background = "";
      document.body.style.background = "";
    };
  }, []);

  return <Outlet />;
}

import { createContext, useContext } from "react";

interface LandingContextValue {
  uuid: string | null;
}

export const LandingContext = createContext<LandingContextValue>({ uuid: null });

export const useLandingContext = () => useContext(LandingContext);

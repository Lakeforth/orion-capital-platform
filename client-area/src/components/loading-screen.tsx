import { useEffect } from "react";

interface LoadingScreenProps {
  onLoaded?: () => void;
}

export default function LoadingScreen({ onLoaded }: LoadingScreenProps) {
  useEffect(() => {
    onLoaded?.();
  }, [onLoaded]);

  return (
    <div className="fixed inset-0 flex items-center justify-center bg-background">
      <div
        className="h-8 w-8 rounded-full border-2 border-zinc-700 border-t-zinc-300 animate-spin"
        role="status"
        aria-label="Loading"
      />
    </div>
  );
}

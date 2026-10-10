import { AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import useSiteSettingsStore from "@/store/siteSettingStore";

export function SiteUnreachable() {
    const { fetchSettings } = useSiteSettingsStore();

    return (
        <div className="h-screen w-screen flex flex-col items-center justify-center bg-background">
            <div className="max-w-md flex flex-col items-center text-center p-8 rounded-lg border border-border">
                <AlertCircle className="h-16 w-16 text-destructive mb-6" />
                <h2 className="text-2xl font-semibold mb-2">Site Unreachable</h2>
                <p className="text-muted-foreground mb-6">
                    We're having trouble connecting to the server. This could be due to network issues
                    or the server might be undergoing maintenance.
                </p>
                <Button onClick={() => fetchSettings()}>
                    Try Again
                </Button>
            </div>
        </div>
    );
}

import { Lock } from "lucide-react";
import { Button } from "@/components/ui/button";
// import useSiteSettingsStore from "@/store/siteSettingStore";
import { clearAuthenticatedSession } from "@/lib/session";

export function SitePending() {
    // const { settings } = useSiteSettingsStore();

    return (
        <div className="h-screen w-screen flex flex-col items-center justify-center bg-background">
            <div className="max-w-md flex flex-col items-center text-center p-8 rounded-lg border border-border">
                <Lock className="h-16 w-16 text-muted-foreground mb-6" />
                <h2 className="text-2xl font-semibold mb-2">Awaiting Approval</h2>
                <p className="text-muted-foreground mb-4">
                    Your account is awaiting approval from the Client Management Team
                </p>

                {/*{settings?.contactEmail && (*/}
                {/*    <p className="text-sm text-muted-foreground mb-6">*/}
                {/*        If you need immediate assistance, please contact us at{" "}*/}
                {/*        <a href={`mailto:${settings.contactEmail}`} className="text-primary underline">*/}
                {/*            {settings.contactEmail}*/}
                {/*        </a>*/}
                {/*    </p>*/}
                {/*)}*/}

                <Button variant="outline" onClick={clearAuthenticatedSession}>
                    Logout
                </Button>
            </div>
        </div>
    );
}

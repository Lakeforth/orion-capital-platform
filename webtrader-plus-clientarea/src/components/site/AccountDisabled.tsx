import { Lock } from "lucide-react";
import { Button } from "@/components/ui/button";
// import useSiteSettingsStore from "@/store/siteSettingStore";
import useUserStore from "@/store/userStore.ts";

export function AccountDisabled() {

    const getCurrentUser = useUserStore(state => state.getCurrentUser);

    // const { settings } = useSiteSettingsStore();


    return (
        <div className="h-screen w-screen flex flex-col items-center justify-center bg-background">
            <div className="max-w-md flex flex-col items-center text-center p-8 rounded-lg border border-border">
                <Lock className="h-16 w-16 text-muted-foreground mb-6" />
                <h2 className="text-2xl font-semibold mb-2">Account Currently Disabled</h2>
                <p className="text-muted-foreground mb-4">
                    Your account is currently disabled, please contact support.
                </p>

                {/*{settings?.contactEmail && (*/}
                {/*    <p className="text-sm text-muted-foreground mb-6">*/}
                {/*        If you need immediate assistance, please contact us at{" "}*/}
                {/*        <a href={`mailto:${settings.contactEmail}`} className="text-primary underline">*/}
                {/*            {settings.contactEmail}*/}
                {/*        </a>*/}
                {/*    </p>*/}
                {/*)}*/}

                <Button variant="outline" onClick={() => getCurrentUser()}>
                    Check Status
                </Button>
            </div>
        </div>
    );
}

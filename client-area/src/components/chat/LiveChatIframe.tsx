
import { Card, CardContent } from "@/components/ui/card";

export default function LiveChatIframe() {
    return (
        <div className="flex flex-col h-[calc(100vh-80px)] md:h-[calc(100vh-90px)]">
            <Card className="flex-1">
                <CardContent className="p-0 h-full">
                    <iframe
                        src="https://jivo.chat/vecno9UbiT"
                        className="w-full h-full border-0"
                        allow="camera; microphone; geolocation; autoplay; encrypted-media"
                    />
                </CardContent>
            </Card>
        </div>
    );
}

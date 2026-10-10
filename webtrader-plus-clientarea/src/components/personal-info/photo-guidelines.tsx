import { Card } from "@/components/ui/card";

export function PhotoGuidelines() {
  return (
    <Card className="p-6 bg-card border-card-foreground/10">
      <h3 className="text-lg font-medium mb-4">
        It is not allowed to publish:
      </h3>

      <ul className="space-y-3">
        <li className="flex gap-2">
          <span className="text-muted-foreground">-</span>
          <span>Photos of an explicitly sexual or pornographic nature</span>
        </li>
        <li className="flex gap-2">
          <span className="text-muted-foreground">-</span>
          <span>
            Images aimed at inciting ethnic or racial hatred or aggression
          </span>
        </li>
        <li className="flex gap-2">
          <span className="text-muted-foreground">-</span>
          <span>Photos involving persons under 18 years of age</span>
        </li>
        <li className="flex gap-2">
          <span className="text-muted-foreground">-</span>
          <span>Third-party copyright protected photos</span>
        </li>
        <li className="flex gap-2">
          <span className="text-muted-foreground">-</span>
          <span>
            Images larger than 5 MB and in a format other than JPG, GIF or PNG
          </span>
        </li>
      </ul>

      <p className="mt-6">
        Your face must be clearly visible on the photo. All photos and videos
        uploaded by you must comply with these requirements, otherwise they can
        be removed.
      </p>
    </Card>
  );
}

import * as React from "react";
import { Card } from "@/components/ui/card";
import { User } from "lucide-react";

export function ProfilePhotoUploader() {
  const [isDragging, setIsDragging] = React.useState(false);
  const [image, setImage] = React.useState<string | null>(null);

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      const file = files[0];
      if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (event) => {
          if (event.target?.result) {
            setImage(event.target.result as string);
          }
        };
        reader.readAsDataURL(file);
      }
    }
  };

  const handleClick = () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = (e) => {
      const files = (e.target as HTMLInputElement).files;
      if (files && files.length > 0) {
        const file = files[0];
        const reader = new FileReader();
        reader.onload = (event) => {
          if (event.target?.result) {
            setImage(event.target.result as string);
          }
        };
        reader.readAsDataURL(file);
      }
    };
    input.click();
  };

  return (
    <Card
      className="flex flex-col items-center justify-center p-6 h-full min-h-[400px] bg-card border-card-foreground/10 cursor-pointer"
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
      onClick={handleClick}
    >
      <div className="flex flex-col items-center justify-center w-full h-full">
        <div
          className={`relative flex items-center justify-center w-64 h-64 rounded-full mb-6 transition-all ${
            isDragging ? "scale-110" : ""
          }`}
          style={{
            background:
              "radial-gradient(circle, rgba(34,197,94,0.2) 0%, rgba(17,24,39,1) 70%)",
          }}
        >
          {image ? (
            <img
              src={image || "/placeholder.svg"}
              alt="Profile"
              className="w-56 h-56 rounded-full object-cover"
            />
          ) : (
            <User className="w-32 h-32 text-muted-foreground/50" />
          )}
        </div>
        <p className="text-muted-foreground text-center">
          Drop a file on the circle above to upload
        </p>
      </div>
    </Card>
  );
}

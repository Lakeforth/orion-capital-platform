import * as React from "react";
import { Card } from "@/components/ui/card";
import { Check, X, ChevronDown } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import type { ReactNode } from "react";

interface DocumentUploaderProps {
  type: string;
  title: string;
  icon: ReactNode;
  onUpload: (type: string, file: File) => void;
  onRemove?: (type: string) => void;
  uploadedFile?: {
    name: string;
    uploadedDate: string;
  } | null;
  disabled?: boolean;
}

const getTypeGuidelines = (type: string): string[] => {
  switch (type) {
    case "selfie":
      return [
        "Face should be clearly visible",
        "Good lighting conditions",
        "No filters or editing",
        "Recent photo (within 6 months)",
      ];
    case "proof_of_address":
      return [
        "Must be less than 3 months old",
        "Show your full name and address",
        "Can be utility bill, bank statement, or government letter",
        "Must be in color and clearly legible",
      ];
    case "proof_of_id":
      return [
        "Front side of government-issued ID",
        "All corners must be visible",
        "Must be in color and clearly legible",
        "No glare or reflection",
      ];
    case "proof_of_id_back":
      return [
        "Back side of government-issued ID",
        "All corners must be visible",
        "Must be in color and clearly legible",
        "No glare or reflection",
      ];
    default:
      return [];
  }
};

const getAllowedFileTypes = (type: string): string => {
  if (type === "selfie") {
    return "image/jpeg,image/png";
  }
  return "image/jpeg,image/png,application/pdf";
};

export function DocumentUploader({
  type,
  title,
  icon,
  onUpload,
  onRemove,
  uploadedFile,
  disabled = false,
}: DocumentUploaderProps) {
  const [isDragging, setIsDragging] = React.useState(false);
  const [isUploading, setIsUploading] = React.useState(false);
  const guidelines = getTypeGuidelines(type);
  const allowedTypes = getAllowedFileTypes(type);

  const processFile = async (file: File) => {
    if (!file || disabled) return;

    // Validate file type
    if (!allowedTypes.includes(file.type)) {
      alert("Invalid file type. Please upload a valid document.");
      return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert("File is too large. Maximum size is 5MB.");
      return;
    }

    setIsUploading(true);
    try {
      await onUpload(type, file);
    } catch (error) {
      console.error("Upload error:", error);
      alert("Failed to upload file. Please try again.");
    } finally {
      setIsUploading(false);
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    if (uploadedFile || isUploading || disabled) return;
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = async (e: React.DragEvent) => {
    if (uploadedFile || isUploading || disabled) return;
    e.preventDefault();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      await processFile(files[0]);
    }
  };

  const handleClick = () => {
    if (uploadedFile || isUploading || disabled) return;

    const input = document.createElement("input");
    input.type = "file";
    input.accept = allowedTypes;
    input.onchange = async (e) => {
      const files = (e.target as HTMLInputElement).files;
      if (files && files.length > 0) {
        await processFile(files[0]);
      }
    };
    input.click();
  };

  const handleRemove = () => {
    if (onRemove && !isUploading) {
      onRemove(type);
    }
  };

  // If file is uploaded, show a different UI
  if (uploadedFile) {
    return (
      <Card className="flex flex-col p-6 h-[200px] bg-card border-card-foreground/10 relative">
        <div className="absolute top-2 right-2">
          {onRemove && (
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6 rounded-full"
              onClick={handleRemove}
            >
              <X className="h-4 w-4" />
              <span className="sr-only">Remove</span>
            </Button>
          )}
        </div>
        <div className="flex items-center justify-center mb-3">
          <div className="bg-primary/10 p-2 rounded-full">
            <Check className="h-5 w-5 text-primary" />
          </div>
        </div>
        <div className="text-center">
          <p className="font-medium text-sm">{title}</p>
          <p className="text-xs text-primary mt-1">Uploaded</p>
          <p className="text-xs text-muted-foreground mt-1 truncate max-w-full">
            {uploadedFile.name}
          </p>
        </div>
      </Card>
    );
  }

  // Default UI for uploading
  return (
    <Card
      className={`flex flex-col items-center justify-center p-6 h-auto min-h-[200px] bg-card border-card-foreground/10 ${
        !disabled ? "cursor-pointer" : "cursor-not-allowed opacity-50"
      } transition-all relative ${
        isDragging ? "border-success border-2" : ""
      } ${isUploading ? "opacity-50" : ""}`}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
      onClick={disabled ? undefined : handleClick}
    >
      <div className="flex items-center justify-center mb-3">{icon}</div>
      <div className="text-center w-full">
        <p className="font-medium text-sm">{title}</p>
        <p className="text-xs text-muted-foreground mt-1">
          {isUploading
            ? "Uploading..."
            : disabled
            ? "Document already uploaded"
            : "drag and drop the document to this area"}
        </p>

        <Collapsible className="w-full mt-4">
          <CollapsibleTrigger asChild>
            <Button
              variant="ghost"
              size="sm"
              className="w-full flex items-center justify-center gap-2"
              onClick={(e) => {
                e.stopPropagation(); // Prevent click from bubbling up to the card
              }}
            >
              <span className="text-xs">View Guidelines</span>
              <ChevronDown className="h-4 w-4" />
            </Button>
          </CollapsibleTrigger>
          <CollapsibleContent className="absolute left-0 right-0 bg-background z-10 p-4 border-t shadow-lg rounded-b-lg w-[400px]">
            <ul className="mt-2 text-xs text-muted-foreground list-disc list-inside text-left">
              {guidelines.map((guideline, index) => (
                <li key={index}>{guideline}</li>
              ))}
            </ul>
          </CollapsibleContent>
        </Collapsible>
      </div>
    </Card>
  );
}

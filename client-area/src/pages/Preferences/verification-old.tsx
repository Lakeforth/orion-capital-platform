import { useState } from "react";
import {
  FileText,
  User,
  ArrowLeft,
  ArrowRight,
  CheckCircle,
} from "lucide-react";
import { Card } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import axiosInstance from "@/lib/axios";
import { DocumentUploader } from "@/components/verification/document-uploader";
import { DocumentsTable } from "@/components/verification/documents-table";
import { toast } from "@/components/ui/sonner";
import { useMobile } from "@/hooks/use-mobile";
import useUserStore from "@/store/userStore.ts";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AxiosError } from "axios";

type Document = {
  id: string;
  user_id: string;
  document: string[];
  type: string;
  status: string;
};

interface ErrorResponse {
  message: string;
}

export default function VerificationPage() {
  const [documents, setDocuments] = useState<Document[]>([]);
  const [editingType, setEditingType] = useState<string | null>(null);
  const isMobile = useMobile();

  const user = useUserStore((state) => state.user);
  const isVerified = user?.verification_status === "approved";

  // Calculate progress based on number of unique document types
  const progress =
    ((documents.length + (documents.find((d) => d.type === "id") ? 1 : 0)) /
      4) *
    100;

  const handleUpload = async (type: string, file: File) => {
    if (isVerified) {
      toast.error("Your account is already verified.");
      return;
    }

    try {
      const formData = new FormData();

      switch (type) {
        case "selfie":
          formData.append("type", "selfie");
          formData.append("document", file);
          break;
        case "proof_of_id":
          formData.append("type", "id");
          formData.append("id_front", file);
          break;
        case "proof_of_id_back":
          formData.append("type", "id");
          formData.append("id_back", file);
          break;
        case "proof_of_address":
          formData.append("type", "proof_of_address");
          formData.append("document", file);
          break;
      }

      toast.loading(`Uploading ${getTypeLabel(type).toLowerCase()}...`, {
        id: "upload-toast",
      });

      await axiosInstance.post("/update/kyc", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      toast.dismiss("upload-toast");
      setEditingType(null);
      toast.success(`${getTypeLabel(type)} uploaded successfully`);
    } catch (error: unknown) {
      console.error("Upload error:", error);
      const axiosError = error as AxiosError<ErrorResponse>;
      const errorMessage =
        axiosError.response?.data?.message ||
        (error as Error).message ||
        `Failed to upload ${getTypeLabel(
          type
        ).toLowerCase()}. Please try again.`;

      toast.dismiss("upload-toast");
      toast.error(errorMessage);
    }
  };

  const getTypeLabel = (type: string) => {
    switch (type) {
      case "selfie":
        return "Selfie";
      case "proof_of_address":
        return "Proof of Address";
      case "proof_of_id":
        return "Proof of ID";
      case "proof_of_id_back":
        return "Proof of ID Back";
      default:
        return type;
    }
  };

  const isDocumentUploaded = (type: string) => {
    if (type === "proof_of_id") {
      return documents.some((doc) => doc.type === "id" && doc.document[0]);
    }
    if (type === "proof_of_id_back") {
      return documents.some((doc) => doc.type === "id" && doc.document[1]);
    }
    return documents.some((doc) => doc.type === type);
  };

  const handleEditDocument = (type: string) => {
    if (isVerified) {
      toast.error(
        "Your account is already verified. Document editing is disabled."
      );
      return;
    }
    setEditingType(type);
  };

  return (
    <div className="container mx-auto p-8">
      <h1 className="text-2xl font-bold mb-6">Identity Verification</h1>

      {isVerified && (
        <Alert className="mb-6 bg-primary/10 border-primary/30">
          <CheckCircle className="h-5 w-5 text-primary" />
          <AlertTitle>Verification Complete</AlertTitle>
          <AlertDescription>
            Your account is fully verified. All trading features are now
            available.
          </AlertDescription>
        </Alert>
      )}

      {/* Document Uploaders */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <DocumentUploader
          type="selfie"
          title="Upload Selfie"
          icon={<User className="h-6 w-6 text-primary" />}
          onUpload={handleUpload}
          disabled={
            (isDocumentUploaded("selfie") && editingType !== "selfie") ||
            isVerified
          }
        />

        <DocumentUploader
          type="proof_of_address"
          title="Upload Proof of Address"
          icon={<FileText className="h-6 w-6 text-primary" />}
          onUpload={handleUpload}
          disabled={
            (isDocumentUploaded("proof_of_address") &&
              editingType !== "proof_of_address") ||
            isVerified
          }
        />

        <DocumentUploader
          type="proof_of_id"
          title="Upload Proof of ID"
          icon={<FileText className="h-6 w-6 text-primary" />}
          onUpload={handleUpload}
          disabled={
            (isDocumentUploaded("proof_of_id") &&
              editingType !== "proof_of_id") ||
            isVerified
          }
        />

        <DocumentUploader
          type="proof_of_id_back"
          title="Upload Proof of ID Back"
          icon={<FileText className="h-6 w-6 text-primary" />}
          onUpload={handleUpload}
          disabled={
            (isDocumentUploaded("proof_of_id_back") &&
              editingType !== "proof_of_id_back") ||
            isVerified
          }
        />
      </div>

      {/* Progress Bar */}
      <div className="mb-8">
        <div className="flex justify-between mb-2">
          <span className="text-sm font-medium">Verification Progress</span>
          <span className="text-sm font-medium">
            {isVerified
              ? "4 of 4 (Complete)"
              : `${
                  documents.length +
                  (documents.find((d) => d.type === "id") ? 1 : 0)
                } of 4`}
          </span>
        </div>
        <Progress value={isVerified ? 100 : progress} className="h-2 mb-4" />

        {/* File Count Visualization */}
        <div className="grid grid-cols-4 gap-2 mt-4">
          {[
            "selfie",
            "proof_of_address",
            "proof_of_id",
            "proof_of_id_back",
          ].map((docType) => {
            const isUploaded = isVerified || isDocumentUploaded(docType);
            return (
              <div key={docType} className="relative">
                <div
                  className={`h-2 rounded-full ${
                    isUploaded ? "bg-primary" : "bg-muted"
                  }`}
                />
                <div className="mt-2 text-xs text-center">
                  {getTypeLabel(docType).split(" ")[0]}
                  <span
                    className={`ml-1 ${
                      isUploaded
                        ? "text-primary font-bold"
                        : "text-muted-foreground"
                    }`}
                  >
                    {isUploaded ? "✓" : ""}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Documents Table */}
      <DocumentsTable
        onEditDocument={handleEditDocument}
        onDocumentsChange={setDocuments}
      />

      <Card className="p-4">
        {isVerified ? (
          <p className="text-sm text-primary">
            Your account is fully verified. You can view your submitted
            documents above but can no longer modify them.
          </p>
        ) : (
          <p className="text-sm text-muted-foreground">
            Please upload all required documents to complete your verification
            process. All documents must be clear, unmodified, and show your full
            information.
          </p>
        )}
        {isMobile && (
          <div className="mt-4 flex justify-between items-center text-xs text-muted-foreground">
            <div className="flex items-center">
              <ArrowLeft className="w-4 h-4 mr-1" />
              <span>Slide left</span>
            </div>
            <div className="flex items-center">
              <span>Slide right</span>
              <ArrowRight className="w-4 h-4 ml-1" />
            </div>
          </div>
        )}
      </Card>
    </div>
  );
}

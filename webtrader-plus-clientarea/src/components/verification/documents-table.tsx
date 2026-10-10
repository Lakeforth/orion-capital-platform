import { useState, useEffect, useCallback } from "react";
import { FileIcon, PencilIcon } from "lucide-react";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore.ts";

type Document = {
  id: string;
  user_id: string;
  document: string[];
  type: string;
  status: string;
};

interface DocumentsTableProps {
  onEditDocument?: (type: string) => void;
  onDocumentsChange?: (documents: Document[]) => void;
}

export function DocumentsTable({
  onEditDocument,
  onDocumentsChange,
}: DocumentsTableProps) {
  const [documents, setDocuments] = useState<Document[]>([]);

  const user = useUserStore((state) => state.user);


  const fetchDocuments = useCallback(async () => {
    try {
      const response = await axiosInstance.get("/user/verification");
      if (response.data.status === "success") {
        setDocuments(response.data.data);
        onDocumentsChange?.(response.data.data);
      }
    } catch (error) {
      console.error("Error fetching documents:", error);
    }
  }, [onDocumentsChange]);

  useEffect(() => {
    fetchDocuments();
    const interval = setInterval(fetchDocuments, 30000);
    return () => clearInterval(interval);
  }, [fetchDocuments]);

  // Helper functions to find documents
  const findSelfie = () => documents.find((doc) => doc.type === "selfie");
  const findProofOfAddress = () =>
    documents.find((doc) => doc.type === "proof_of_address");
  const findId = () => documents.find((doc) => doc.type === "id");

  return (
    <Card className="mb-6">
      <div className="p-4 border-b">
        <h2 className="text-lg font-medium">Uploaded Documents</h2>
      </div>
      <div className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Document</TableHead>
              <TableHead>Time Uploaded</TableHead>
              <TableHead>File</TableHead>
              <TableHead>Status</TableHead>
              { user?.verification_status != 'approved' && (<TableHead>Action</TableHead>)}

            </TableRow>
          </TableHeader>
          <TableBody>
            {/* Selfie Row */}
            <TableRow>
              <TableCell className="font-medium">Selfie</TableCell>
              <TableCell>
                {findSelfie()?.id ? new Date().toLocaleString() : "-"}
              </TableCell>
              <TableCell>
                {findSelfie()?.document[0] ? (
                  <a
                    href={findSelfie()?.document[0]}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary hover:text-primary/80"
                  >
                    <FileIcon className="h-5 w-5" />
                  </a>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findSelfie()?.status === "approved" ? (
                  <span className="text-success">Approved</span>
                ) : findSelfie()?.status === "pending" ? (
                  <span className="text-amber-500">Pending</span>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findSelfie() && findSelfie()?.status !== "approved" && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => onEditDocument?.("selfie")}
                  >
                    <PencilIcon className="h-4 w-4" />
                  </Button>
                )}
              </TableCell>
            </TableRow>

            {/* ID Front Row */}
            <TableRow>
              <TableCell className="font-medium">ID Front</TableCell>
              <TableCell>
                {findId()?.document[0] ? new Date().toLocaleString() : "-"}
              </TableCell>
              <TableCell>
                {findId()?.document[0] ? (
                  <a
                    href={findId()?.document[0]}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary hover:text-primary/80"
                  >
                    <FileIcon className="h-5 w-5" />
                  </a>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findId()?.status === "approved" ? (
                  <span className="text-success">Approved</span>
                ) : findId()?.status === "pending" ? (
                  <span className="text-amber-500">Pending</span>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findId()?.document[0] && findId()?.status !== "approved" && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => onEditDocument?.("proof_of_id")}
                  >
                    <PencilIcon className="h-4 w-4" />
                  </Button>
                )}
              </TableCell>
            </TableRow>

            {/* ID Back Row */}
            <TableRow>
              <TableCell className="font-medium">ID Back</TableCell>
              <TableCell>
                {findId()?.document[1] ? new Date().toLocaleString() : "-"}
              </TableCell>
              <TableCell>
                {findId()?.document[1] ? (
                  <a
                    href={findId()?.document[1]}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary hover:text-primary/80"
                  >
                    <FileIcon className="h-5 w-5" />
                  </a>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findId()?.status === "approved" ? (
                  <span className="text-success">Approved</span>
                ) : findId()?.status === "pending" ? (
                  <span className="text-amber-500">Pending</span>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findId()?.document[1] && findId()?.status !== "approved" && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => onEditDocument?.("proof_of_id_back")}
                  >
                    <PencilIcon className="h-4 w-4" />
                  </Button>
                )}
              </TableCell>
            </TableRow>

            {/* Proof of Address Row */}
            <TableRow>
              <TableCell className="font-medium">Proof of Address</TableCell>
              <TableCell>
                {findProofOfAddress()?.id ? new Date().toLocaleString() : "-"}
              </TableCell>
              <TableCell>
                {findProofOfAddress()?.document[0] ? (
                  <a
                    href={findProofOfAddress()?.document[0]}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary hover:text-primary/80"
                  >
                    <FileIcon className="h-5 w-5" />
                  </a>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findProofOfAddress()?.status === "approved" ? (
                  <span className="text-success">Approved</span>
                ) : findProofOfAddress()?.status === "pending" ? (
                  <span className="text-amber-500">Pending</span>
                ) : (
                  "-"
                )}
              </TableCell>
              <TableCell>
                {findProofOfAddress() &&
                  findProofOfAddress()?.status !== "approved" && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => onEditDocument?.("proof_of_address")}
                    >
                      <PencilIcon className="h-4 w-4" />
                    </Button>
                  )}
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </Card>
  );
}

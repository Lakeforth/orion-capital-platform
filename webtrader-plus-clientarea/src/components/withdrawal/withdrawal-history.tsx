import { useEffect, useState } from "react";
import axiosInstance from "@/lib/axios";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Info } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

interface WithdrawalRequest {
  id: string;
  amount: string;
  date: string;
  type: string;
  account: string;
  status: string;
  details: string;
}

interface ApiResponse {
  status: string;
  message: string;
  data: {
    current_page: number;
    data: WithdrawalRequest[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
  };
}

interface WithdrawalHistoryProps {
  refreshTrigger?: number;
}

export function WithdrawalHistory({
  refreshTrigger = 0,
}: WithdrawalHistoryProps) {
  const [withdrawals, setWithdrawals] = useState<WithdrawalRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedRequest, setSelectedRequest] =
    useState<WithdrawalRequest | null>(null);
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);

  useEffect(() => {
    const fetchWithdrawals = async () => {
      try {
        const response = await axiosInstance.get<ApiResponse>(
          "/user/withdrawals"
        );
        setWithdrawals(response.data.data.data);
        setError(null);
      } catch (err) {
        setError("Failed to load withdrawal history");
        console.error("Error fetching withdrawals:", err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchWithdrawals();
  }, [refreshTrigger]);

  if (isLoading) {
    return (
      <div className="rounded-md border border-border/40 overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="bg-card hover:bg-card">
              <TableHead className="text-foreground font-bold">DATE</TableHead>
              <TableHead className="text-foreground font-bold">
                AMOUNT
              </TableHead>
              <TableHead className="text-foreground font-bold">TYPE</TableHead>
              <TableHead className="text-foreground font-bold">ACCOUNT</TableHead>
              <TableHead className="text-foreground font-bold">
                DETAILS
              </TableHead>
              <TableHead className="text-foreground font-bold">
                STATUS
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableCell colSpan={5} className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-white mx-auto"></div>
                <p>Loading withdrawal history...</p>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    );
  }

  if (error) {
    return <div className="p-4 text-center text-destructive">{error}</div>;
  }

  return (
    <>
      <div className="rounded-md border border-border/40 overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="bg-card hover:bg-card">
              <TableHead className="text-foreground font-bold">DATE</TableHead>
              <TableHead className="text-foreground font-bold">
                AMOUNT
              </TableHead>
              <TableHead className="text-foreground font-bold">TYPE</TableHead>
              <TableHead className="text-foreground font-bold">ACCOUNT</TableHead>
              <TableHead className="text-foreground font-bold">
                DETAILS
              </TableHead>
              <TableHead className="text-foreground font-bold">
                STATUS
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {withdrawals.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={5}
                  className="text-center text-muted-foreground"
                >
                  No withdrawal history found
                </TableCell>
              </TableRow>
            ) : (
              withdrawals.map((request) => (
                <TableRow key={request.id} className="bg-card/50 hover:bg-card">
                  <TableCell className="text-muted-foreground">
                    {request.date}
                  </TableCell>
                  <TableCell>{request.amount}</TableCell>
                  <TableCell className="capitalize">{request.type}</TableCell>
                  <TableCell className="capitalize">{request.account}</TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="flex items-center gap-1 text-muted-foreground hover:text-foreground"
                      onClick={() => {
                        setSelectedRequest(request);
                        setIsDetailsOpen(true);
                      }}
                    >
                      <Info className="h-4 w-4" />
                      <span>View Details</span>
                    </Button>
                  </TableCell>
                  <TableCell>
                    <StatusBadge status={request.status} />
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Withdrawal Details</DialogTitle>
          </DialogHeader>
          {selectedRequest && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-2 text-sm">
                {selectedRequest.details &&
                  (() => {
                    try {
                      const details =
                        typeof selectedRequest.details === "string"
                          ? JSON.parse(selectedRequest.details)
                          : selectedRequest.details;

                      return Object.entries(details).map(([key, value]) => (
                        <div key={key} className="contents">
                          <div className="font-medium capitalize">
                            {key.replace(/_/g, " ")}:
                          </div>
                          <div className="text-muted-foreground break-all">
                            {String(value)}
                          </div>
                        </div>
                      ));
                    } catch (error) {
                      console.error("Error parsing details:", error);
                      return (
                        <div className="col-span-2 text-destructive">
                          Unable to display additional details
                        </div>
                      );
                    }
                  })()}
                <div className="font-medium">Status:</div>
                <div>
                  <StatusBadge status={selectedRequest.status} />
                </div>
                <div className="font-medium">Amount:</div>
                <div className="text-muted-foreground">
                  {selectedRequest.amount}
                </div>
                <div className="font-medium">Date:</div>
                <div className="text-muted-foreground">
                  {selectedRequest.date}
                </div>
              </div>
              <div className="flex justify-end">
                <Button
                  variant="outline"
                  onClick={() => setIsDetailsOpen(false)}
                >
                  Close
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}

function StatusBadge({ status }: { status: string }) {
  switch (status.toLowerCase()) {
    case "pending":
      return (
        <Badge
          variant="outline"
          className="bg-yellow-500/10 text-yellow-500 border-yellow-500/20"
        >
          Pending
        </Badge>
      );
    case "completed":
      return (
        <Badge
          variant="outline"
          className="bg-success/10 text-success border-success/20"
        >
          Completed
        </Badge>
      );
    case "rejected":
      return (
        <Badge
          variant="outline"
          className="bg-destructive/10 text-destructive border-destructive/20"
        >
          Rejected
        </Badge>
      );
    default:
      return (
        <Badge variant="outline">
          {status.charAt(0).toUpperCase() + status.slice(1)}
        </Badge>
      );
  }
}

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { UserAccount } from "@/store/userStore";

interface AccountsTableProps {
  accounts: UserAccount[];
}

export function AccountsTable({ accounts }: AccountsTableProps) {
  return (
    <div className="rounded-md border border-border/40 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="bg-card hover:bg-card">
            <TableHead className="text-foreground font-bold">ACCOUNT</TableHead>
            <TableHead className="text-foreground font-bold">TYPE</TableHead>
            <TableHead className="text-foreground font-bold">BALANCE</TableHead>
            <TableHead className="text-foreground font-bold">
              CURRENCY
            </TableHead>
            <TableHead className="text-foreground font-bold">STATUS</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {accounts.map((account) => (
            <TableRow key={account.type} className="bg-card/50 hover:bg-card">
              <TableCell>{account.title}</TableCell>
              <TableCell className="capitalize">{account.type}</TableCell>
              <TableCell>${account.balance}</TableCell>
              <TableCell>{account.currency}</TableCell>
              <TableCell>
                <StatusBadge status={account.status} />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

function StatusBadge({ status }: { status: UserAccount["status"] }) {
  switch (status) {
    case "active":
      return (
        <Badge
          variant="outline"
          className="bg-success/10 text-success border-success/20"
        >
          Active
        </Badge>
      );
    case "inactive":
      return (
        <Badge
          variant="outline"
          className="bg-muted/10 text-muted-foreground border-muted/20"
        >
          Inactive
        </Badge>
      );
    case "suspended":
      return (
        <Badge
          variant="outline"
          className="bg-destructive/10 text-destructive border-destructive/20"
        >
          Suspended
        </Badge>
      );
    default:
      return null;
  }
}

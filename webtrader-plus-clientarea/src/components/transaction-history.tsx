import { useEffect, useRef, useState } from "react";
import axiosInstance from "@/lib/axios";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";
import {
  History,
  ArrowDownCircle,
  ArrowUpCircle,
  Download,
  CalendarDays,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import useTradeStore, { Trade } from "@/store/tradeStore";
import { formatTradePrice, formatVolume } from "@/lib/price-format";
import useUserStore from "@/store/userStore";
import useSiteSettingsStore from "@/store/siteSettingStore";
import { usePartnersQuery } from "@/services/partners/partner-queries";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
const safeNewDate = (dateStr: string | Date | null | undefined): Date => {
  if (!dateStr) return new Date(NaN);
  if (dateStr instanceof Date) return dateStr;
  const str = String(dateStr).trim();

  // 1. Try standard browser parsing first (but reject if it results in extremely low years < 1950)
  const d = new Date(str);
  if (!isNaN(d.getTime()) && d.getFullYear() > 1950) {
    return d;
  }

  // 2. Custom robust parser
  // Replace comma with space, split by slashes, dashes, colons, T, and spaces
  const cleanStr = str.replace(/,/, " ");
  const parts = cleanStr.split(/[-/ :T]+/).filter(Boolean);

  if (parts.length >= 3) {
    let year = NaN;
    let month = NaN;
    let day = NaN;
    let hour = 0;
    let minute = 0;
    let second = 0;

    const p3 = parts[3];
    const p4 = parts[4];
    const p5 = parts[5];
    if (p3) hour = parseInt(p3, 10) || 0;
    if (p4) minute = parseInt(p4, 10) || 0;
    if (p5) second = parseInt(p5, 10) || 0;

    const monthsMap: Record<string, number> = {
      jan: 0,
      feb: 1,
      mar: 2,
      apr: 3,
      may: 4,
      jun: 5,
      jul: 6,
      aug: 7,
      sep: 8,
      oct: 9,
      nov: 10,
      dec: 11,
    };

    const parseMonth = (mStr: string): number => {
      const lower = mStr.toLowerCase().substring(0, 3);
      if (lower in monthsMap) return monthsMap[lower];
      const parsed = parseInt(mStr, 10);
      return isNaN(parsed) ? NaN : parsed - 1;
    };

    const p0 = parts[0] ?? "";
    const p1 = parts[1] ?? "";
    const p2 = parts[2] ?? "";

    const isYear = (val: string) =>
      val.length === 4 && !isNaN(parseInt(val, 10));

    if (isYear(p0)) {
      // YYYY MM DD
      year = parseInt(p0, 10);
      month = parseMonth(p1);
      day = parseInt(p2, 10);
    } else if (isYear(p2)) {
      // DD MM YYYY or MM DD YYYY
      year = parseInt(p2, 10);
      const mVal = parseMonth(p1);
      const dVal = parseInt(p0, 10);

      if (mVal >= 0 && mVal <= 11) {
        month = mVal;
        day = dVal;
      } else {
        month = parseInt(p1, 10) - 1;
        day = parseInt(p0, 10);
      }
    } else {
      // Fallback: assume parts[2] is YY or standard fallback
      const yr = parseInt(p2, 10);
      year = yr < 100 ? (yr > 50 ? 1900 + yr : 2000 + yr) : yr;
      month = parseMonth(p1);
      day = parseInt(p0, 10);
    }

    if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
      const manualDate = new Date(year, month, day, hour, minute, second);
      if (!isNaN(manualDate.getTime())) {
        if (manualDate.getFullYear() < 1990) return new Date(NaN);
        return manualDate;
      }
    }
  }

  // Final fallback to the original parsed Date (even if invalid)
  if (!isNaN(d.getTime()) && d.getFullYear() < 1990) return new Date(NaN);
  return d;
};

interface Transaction {
  id: string;
  amount: string;
  date: string;
  type: string;
  account: string;
  status: string;
  details: string | null;
}

interface ApiResponse {
  status: string;
  message: string;
  data: {
    current_page: number;
    data: Transaction[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
  };
}

const TransactionList: React.FC<{
  transactions: Transaction[];
}> = ({ transactions }) => {
  if (transactions.length === 0) {
    return (
      <div className="text-center py-12 text-muted-foreground bg-card/30 rounded-lg border border-dashed border-border/40">
        No transaction history found
      </div>
    );
  }

  return (
    <>
      {/* Desktop Table View */}
      <div className="hidden md:block rounded-md border border-border/40 overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="bg-card hover:bg-card">
              <TableHead className="text-foreground font-bold">DATE</TableHead>
              <TableHead className="text-foreground font-bold">
                AMOUNT
              </TableHead>
              <TableHead className="text-foreground font-bold">TYPE</TableHead>
              <TableHead className="text-foreground font-bold">
                ACCOUNT
              </TableHead>
              <TableHead className="text-foreground font-bold">
                STATUS
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {transactions.map((tx) => (
              <TableRow key={tx.id} className="bg-card/50 hover:bg-card">
                <TableCell>{tx.date}</TableCell>
                <TableCell>{tx.amount}</TableCell>
                <TableCell className="capitalize">{tx.type}</TableCell>
                <TableCell className="capitalize">{tx.account}</TableCell>
                <TableCell>
                  <span
                    className={cn(
                      "px-2 py-1 rounded text-xs",
                      tx.status === "approved" || tx.status === "completed"
                        ? "bg-green-500/20 text-green-400"
                        : tx.status === "rejected"
                          ? "bg-red-500/20 text-red-400"
                          : "bg-yellow-500/20 text-yellow-400",
                    )}
                  >
                    {tx.status.charAt(0).toUpperCase() + tx.status.slice(1)}
                  </span>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {/* Mobile Card View */}
      <div className="md:hidden space-y-3">
        {transactions.map((tx) => (
          <div
            key={tx.id}
            className="rounded-lg border border-border/40 bg-card/50 p-4 space-y-3"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Date</p>
                <p className="text-sm font-medium text-foreground">{tx.date}</p>
              </div>
              <span
                className={cn(
                  "px-2 py-1 rounded text-xs font-medium",
                  tx.status === "approved" || tx.status === "completed"
                    ? "bg-green-500/20 text-green-400"
                    : tx.status === "rejected"
                      ? "bg-red-500/20 text-red-400"
                      : "bg-yellow-500/20 text-yellow-400",
                )}
              >
                {tx.status.charAt(0).toUpperCase() + tx.status.slice(1)}
              </span>
            </div>

            <div className="grid grid-cols-2 gap-3 py-2 border-t border-b border-border/20">
              <div>
                <p className="text-xs text-muted-foreground">Amount</p>
                <p className="text-sm font-semibold text-foreground">
                  {tx.amount}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Type</p>
                <p className="text-sm font-medium capitalize text-foreground">
                  {tx.type}
                </p>
              </div>
            </div>

            <div>
              <p className="text-xs text-muted-foreground mb-1">Account</p>
              <p className="text-sm capitalize text-foreground">{tx.account}</p>
            </div>
          </div>
        ))}
      </div>
    </>
  );
};

const TradeList: React.FC<{
  trades: Trade[];
}> = ({ trades }) => {
  if (trades.length === 0) {
    return (
      <div className="text-center py-12 text-muted-foreground bg-card/30 rounded-lg border border-dashed border-border/40">
        No trade history found
      </div>
    );
  }

  return (
    <>
      {/* Desktop Table View */}
      <div className="hidden md:block rounded-md border border-border/40 overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="bg-card hover:bg-card">
              <TableHead className="text-foreground font-bold">DATE</TableHead>
              <TableHead className="text-foreground font-bold">ASSET</TableHead>
              <TableHead className="text-foreground font-bold">TYPE</TableHead>
              <TableHead className="text-foreground font-bold">
                VOLUME
              </TableHead>
              <TableHead className="text-foreground font-bold text-right">
                OPEN / CLOSE
              </TableHead>
              <TableHead className="text-foreground font-bold text-right">
                PNL
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {trades.map((trade) => (
              <TableRow key={trade.id} className="bg-card/50 hover:bg-card">
                <TableCell>
                  <span>{trade.close_time}</span>
                </TableCell>
                <TableCell>
                  <div className="font-medium">{trade.asset_symbol}</div>
                </TableCell>
                <TableCell>
                  <span
                    className={cn(
                      "px-2 py-1 rounded text-xs",
                      trade.trade_type === "buy"
                        ? "bg-green-500/20 text-green-400"
                        : "bg-red-500/20 text-red-400",
                    )}
                  >
                    {trade.trade_type.toUpperCase()}
                  </span>
                </TableCell>
                <TableCell>{formatVolume(trade.volume_lots ?? trade.volume)}</TableCell>
                <TableCell className="text-right">
                  <div className="flex flex-col items-end">
                    <span className="font-mono tabular-nums">
                      {formatTradePrice(trade.opening_price, trade.asset_category, trade.asset_symbol)}
                    </span>
                    <span className="text-xs text-muted-foreground font-mono tabular-nums">
                      {formatTradePrice(trade.closing_price, trade.asset_category, trade.asset_symbol)}
                    </span>
                  </div>
                </TableCell>
                <TableCell className="text-right">
                  <span
                    className={cn(
                      "font-medium",
                      trade.pnl > 0
                        ? "text-green-500"
                        : trade.pnl < 0
                          ? "text-red-500"
                          : "",
                    )}
                  >
                    {trade.pnl > 0 ? "+" : ""}
                    {trade.pnl.toFixed(2)}
                  </span>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {/* Mobile Card View */}
      <div className="md:hidden space-y-3">
        {trades.map((trade) => (
          <div
            key={trade.id}
            className="rounded-lg border border-border/40 bg-card/50 p-4 space-y-3"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-foreground">
                  {trade.asset_symbol}
                </p>
                <p className="text-xs text-muted-foreground">
                  {trade.close_time}
                </p>
              </div>
              <span
                className={cn(
                  "px-2 py-1 rounded text-xs font-medium",
                  trade.trade_type === "buy"
                    ? "bg-green-500/20 text-green-400"
                    : "bg-red-500/20 text-red-400",
                )}
              >
                {trade.trade_type.toUpperCase()}
              </span>
            </div>

            <div className="grid grid-cols-2 gap-3 py-2 border-t border-b border-border/20">
              <div>
                <p className="text-xs text-muted-foreground">Volume</p>
                <p className="text-sm font-semibold text-foreground">
                  {formatVolume(trade.volume_lots ?? trade.volume)}
                </p>
              </div>
              <div className="text-right">
                <p className="text-xs text-muted-foreground">PnL</p>
                <p
                  className={cn(
                    "text-sm font-semibold",
                    trade.pnl > 0
                      ? "text-green-500"
                      : trade.pnl < 0
                        ? "text-red-500"
                        : "text-foreground",
                  )}
                >
                  {trade.pnl > 0 ? "+" : ""}
                  {trade.pnl.toFixed(2)}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>
    </>
  );
};

const TransactionHistory: React.FC = () => {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("all");
  const [isDateRangeOpen, setIsDateRangeOpen] = useState(false);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [isGenerating, setIsGenerating] = useState(false);
  const logoImgRef = useRef<HTMLImageElement>(null);

  const setPreset = (preset: "all" | "6m" | "3m" | "30d") => {
    // Find the latest transaction or trade date to handle historical/mock data gracefully.
    // If no transactions are loaded yet, fall back to the actual current date.
    let today = new Date();
    let maxDate: Date | null = null;

    closedTrades.forEach((trade) => {
      const d = safeNewDate(trade.close_time);
      if (!isNaN(d.getTime())) {
        if (!maxDate || d > maxDate) {
          maxDate = d;
        }
      }
    });

    transactions.forEach((tx) => {
      const d = safeNewDate(tx.date);
      if (!isNaN(d.getTime())) {
        if (!maxDate || d > maxDate) {
          maxDate = d;
        }
      }
    });

    if (maxDate) {
      today = maxDate;
    }

    const formatDate = (date: Date) => {
      const yyyy = date.getFullYear();
      const mm = String(date.getMonth() + 1).padStart(2, "0");
      const dd = String(date.getDate()).padStart(2, "0");
      return `${yyyy}-${mm}-${dd}`;
    };

    if (preset === "all") {
      setStartDate("");
      setEndDate("");
    } else if (preset === "6m") {
      const past = new Date(today);
      past.setMonth(today.getMonth() - 6);
      setStartDate(formatDate(past));
      setEndDate(formatDate(today));
    } else if (preset === "3m") {
      const past = new Date(today);
      past.setMonth(today.getMonth() - 3);
      setStartDate(formatDate(past));
      setEndDate(formatDate(today));
    } else if (preset === "30d") {
      const past = new Date(today);
      past.setDate(today.getDate() - 30);
      setStartDate(formatDate(past));
      setEndDate(formatDate(today));
    }
  };

  const {
    closedTrades,
    fetchClosedTrades,
    fetchMoreClosedTrades,
    hasMoreClosedTrades,
    isLoadingClosed,
  } = useTradeStore();
  const siteSettings = useSiteSettingsStore((s) => s.settings);
  const { data: partners } = usePartnersQuery();

  useEffect(() => {
    fetchClosedTrades();
  }, [fetchClosedTrades]);

  useEffect(() => {
    const fetchTransactions = async () => {
      try {
        const [depositsRes, withdrawalsRes] = await Promise.all([
          axiosInstance.get<ApiResponse>("/user/deposits"),
          axiosInstance.get<ApiResponse>("/user/withdrawals"),
        ]);

        const combined = [
          ...depositsRes.data.data.data,
          ...withdrawalsRes.data.data.data,
        ].sort(
          (a, b) =>
            safeNewDate(b.date).getTime() - safeNewDate(a.date).getTime(),
        );

        setTransactions(combined);
        setError(null);
      } catch (err) {
        setError("Failed to load transaction history");
        console.error("Error fetching transactions:", err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchTransactions();
  }, []);

  const deposits = transactions.filter(
    (tx) => tx.type.toLowerCase() === "deposit",
  );
  const withdrawals = transactions.filter(
    (tx) => tx.type.toLowerCase() === "withdrawal",
  );

  const handleDownloadStatement = async (from: string, to: string) => {
    try {
      const params = from && to
        ? { period: "custom", start_date: from, end_date: to }
        : { period: "all_time" };
      const response = await axiosInstance.get("/account/statement", { params });
      const downloadUrl = response.data?.data?.download_url;

      if (downloadUrl) {
        const link = document.createElement("a");
        link.href = downloadUrl;
        link.download = response.data?.data?.file_name || "account-statement.pdf";
        document.body.appendChild(link);
        link.click();
        link.remove();
        return;
      }
    } catch (error) {
      console.error("Server statement generation failed; using local fallback", error);
    }

    const start = from ? safeNewDate(from + "T00:00:00") : null;
    const end = to ? safeNewDate(to + "T23:59:59") : null;

    const inRange = (dateStr: string | null | undefined) => {
      if (!dateStr) return false;
      const d = safeNewDate(dateStr);
      if (isNaN(d.getTime())) return false;
      if (start && d < start) return false;
      if (end && d > end) return false;
      return true;
    };

    console.log("Statement Range limits:", {
      from,
      to,
      start: start?.toString(),
      end: end?.toString(),
    });
    if (closedTrades.length > 0) {
      console.log(
        "Sample Trade Date:",
        closedTrades[0].close_time,
        "parsed as:",
        safeNewDate(closedTrades[0].close_time).toString(),
        "inRange:",
        inRange(closedTrades[0].close_time),
      );
    }
    if (deposits.length > 0) {
      console.log(
        "Sample Deposit Date:",
        deposits[0].date,
        "parsed as:",
        safeNewDate(deposits[0].date).toString(),
        "inRange:",
        inRange(deposits[0].date),
      );
    }
    const doc = new jsPDF({ compress: true });
    const user = useUserStore.getState().user;
    const settings = useSiteSettingsStore.getState().settings;
    const siteName = settings?.name || "Trading CRM";

    let logoImgData: string | null = null;
    let logoWidth = 60;
    const logoHeight = 14;

    const logoUrl = settings?.logo || settings?.logo_sm;
    if (logoUrl) {
      if (logoUrl.startsWith("data:")) {
        logoImgData = logoUrl;
      } else if (
        logoUrl.startsWith("http://") ||
        logoUrl.startsWith("https://")
      ) {
        try {
          // Dynamic fetch using axiosInstance to bypass canvas CORS issues
          const response = await axiosInstance.get(logoUrl, {
            responseType: "blob",
          });
          logoImgData = await new Promise<string>((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result as string);
            reader.onerror = () =>
              reject(new Error("Failed to read logo blob"));
            reader.readAsDataURL(response.data);
          });
        } catch (axiosError) {
          console.warn(
            "Direct logo fetch failed (likely CORS), trying Cloudflare proxy:",
            axiosError,
          );

          try {
            // Tier 3: Fetch via Cloudflare image proxy to bypass CORS protection on S3/CDNs
            const proxiedUrl = `https://images.weserv.nl/?url=${encodeURIComponent(logoUrl)}`;
            const proxyRes = await fetch(proxiedUrl);
            if (proxyRes.ok) {
              const blob = await proxyRes.blob();
              logoImgData = await new Promise<string>((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result as string);
                reader.onerror = () =>
                  reject(new Error("Failed to read proxy blob"));
                reader.readAsDataURL(blob);
              });
            } else {
              console.warn(
                "Cloudflare proxy returned non-OK response:",
                proxyRes.status,
              );
            }
          } catch (proxyError) {
            console.error("Cloudflare proxy fetch failed too:", proxyError);
          }
        }
      }
    }

    // Use the hidden <img> that is already rendered in the DOM (same as Logo.tsx),
    // so the browser has already loaded and decoded it. We can read its dimensions for aspect ratio.
    const renderedImg = logoImgRef.current;
    if (renderedImg && renderedImg.complete && renderedImg.naturalWidth > 0) {
      logoWidth =
        (renderedImg.naturalWidth / renderedImg.naturalHeight) * logoHeight;

      // Fallback: If axios fetch failed (or URL is relative), try DOM canvas rendering
      if (!logoImgData) {
        try {
          const canvas = document.createElement("canvas");
          canvas.width = renderedImg.naturalWidth;
          canvas.height = renderedImg.naturalHeight;
          const ctx = canvas.getContext("2d");
          if (ctx) {
            ctx.drawImage(renderedImg, 0, 0);
            logoImgData = canvas.toDataURL("image/png");
          }
        } catch (canvasError) {
          console.warn("Canvas DOM rendering failed too:", canvasError);
        }
      }
    }

    // Downscale and compress the logo to keep final PDF file size extremely small (approx. 50-100KB)
    const compressImage = async (base64OrUrl: string): Promise<string> => {
      return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = "anonymous";
        img.onload = () => {
          try {
            const canvas = document.createElement("canvas");
            const maxW = 300; // standard width for crisp high-quality rendering in PDF
            const scale = img.naturalWidth > maxW ? maxW / img.naturalWidth : 1;
            canvas.width = img.naturalWidth * scale;
            canvas.height = img.naturalHeight * scale;
            const ctx = canvas.getContext("2d");
            if (ctx) {
              ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
              resolve(canvas.toDataURL("image/jpeg", 0.75)); // Highly compressed JPEG
              return;
            }
          } catch (e) {
            console.warn(
              "Failed to compress image on canvas, using original:",
              e,
            );
          }
          resolve(base64OrUrl);
        };
        img.onerror = () => resolve(base64OrUrl);
        img.src = base64OrUrl;
      });
    };

    if (logoImgData) {
      try {
        logoImgData = await compressImage(logoImgData);
      } catch (err) {
        console.warn("Failed to compress logo:", err);
      }
    }

    // Set document properties
    doc.setProperties({
      title: `${siteName} - Account Statement`,
      subject: "Account Statement",
      author: siteName,
    });

    // Theme Colors
    const primaryColor: [number, number, number] = [0, 0, 0]; // Pure Black
    const darkColor = [31, 41, 55]; // Gray 800
    const lightGray = [156, 163, 175]; // Gray 400

    // Header - Title & Meta
    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
    doc.rect(0, 0, 210, 40, "F");

    if (logoImgData) {
      const finalWidth = Math.min(logoWidth, 60);
      let format = "PNG";
      const match = logoImgData.match(/^data:image\/([a-zA-Z+]+);base64/);
      if (match && match[1]) {
        const ext = match[1].toUpperCase();
        if (ext === "JPEG" || ext === "JPG") format = "JPEG";
        else if (ext === "WEBP") format = "WEBP";
      }
      doc.addImage(logoImgData, format, 15, 10, finalWidth, logoHeight);

      doc.setTextColor(255, 255, 255);
      doc.setFont("helvetica", "normal");
      doc.setFontSize(9);
      doc.text("ACCOUNT STATEMENT", 15, 32);
    } else {
      doc.setTextColor(255, 255, 255);
      doc.setFont("helvetica", "bold");
      doc.setFontSize(20);
      doc.text(siteName.toUpperCase(), 15, 22);

      doc.setFont("helvetica", "normal");
      doc.setFontSize(9);
      doc.text("ACCOUNT STATEMENT", 15, 32);
    }

    // Date & Time Generated
    const now = new Date();
    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.text(
      `Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`,
      140,
      22,
    );

    // User Details Section
    doc.setTextColor(darkColor[0], darkColor[1], darkColor[2]);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text("Client Information", 15, 52);
    doc.line(15, 54, 195, 54);

    const userName = user ? `${user.first_name} ${user.last_name}` : "Client";

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text(`Name: ${userName}`, 15, 62);
    doc.text(`Email: ${user?.email || "N/A"}`, 15, 68);
    doc.text(`Account ID: ${user?.account_id || "N/A"}`, 15, 74);

    // Partners Section
    if (partners && partners.length > 0) {
      const partnerNames = partners.map((p) => p.name).join(", ");
      doc.setFont("helvetica", "bold");
      doc.text("Partners:", 15, 80);
      doc.setFont("helvetica", "normal");
      doc.text(partnerNames, 33, 80);
    }

    // Balance and Equity
    doc.text(
      `Current Balance: $${Number(user?.balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      120,
      62,
    );
    doc.text(
      `Credit: $${Number(user?.credit_balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      120,
      68,
    );

    const rangeLabel =
      start || end
        ? ` (${start ? start.toLocaleDateString() : "start"} – ${end ? end.toLocaleDateString() : "today"})`
        : "";

    let currentY = 90;

    // Helper to start a new page if we are near the bottom (less than 50mm space left)
    const ensureSpace = (neededSpace: number) => {
      if (currentY + neededSpace > 247) {
        doc.addPage();
        currentY = 20; // Reset Y margin for the new page
      }
    };

    // 1. Trades History Section
    ensureSpace(30);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text(`Trades History${rangeLabel}`, 15, currentY);
    doc.line(15, currentY + 2, 195, currentY + 2);
    currentY += 6;

    const tradesHeaders = [
      [
        "Close Date",
        "Asset",
        "Type",
        "Volume",
        "Open Price",
        "Close Price",
        "PnL ($)",
      ],
    ];
    const tradesBody = closedTrades
      .filter((trade) => inRange(trade.close_time))
      .map((trade) => [
        trade.close_time,
        trade.asset_symbol,
        trade.trade_type.toUpperCase(),
        formatVolume(trade.volume_lots ?? trade.volume),
        // Prices, not money: a "$" prefix at 2dp turned a 1.910221 forex fill
        // into "$1.91" on the client's own exported statement, losing the pip
        // range the trade was actually measured in.
        formatTradePrice(Number(trade.opening_price), trade.asset_category, trade.asset_symbol),
        formatTradePrice(Number(trade.closing_price), trade.asset_category, trade.asset_symbol),
        `${trade.pnl >= 0 ? "+" : ""}$${trade.pnl.toFixed(2)}`,
      ]);

    autoTable(doc, {
      startY: currentY,
      head: tradesHeaders,
      body:
        tradesBody.length > 0
          ? tradesBody
          : [["No trade history found", "", "", "", "", "", ""]],
      theme: "striped",
      headStyles: {
        fillColor: primaryColor,
        textColor: [255, 255, 255],
        fontStyle: "bold",
      },
      styles: {
        fontSize: 9,
        cellPadding: 3,
      },
      alternateRowStyles: {
        fillColor: [243, 244, 246],
      },
      columnStyles: {
        6: { fontStyle: "bold" },
      },
      didDrawCell: (data) => {
        if (data.column.index === 6 && data.cell.section === "body") {
          const val = data.cell.text[0];
          if (val.startsWith("+")) {
            doc.setTextColor(34, 197, 94); // Green 500
          } else if (val.startsWith("-")) {
            doc.setTextColor(239, 68, 68); // Red 500
          }
        }
      },
    });

    currentY =
      (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable
        .finalY + 15;

    // 2. Deposit Transactions Section
    ensureSpace(30);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text(`Deposit Transactions${rangeLabel}`, 15, currentY);
    doc.line(15, currentY + 2, 195, currentY + 2);
    currentY += 6;

    const depositHeaders = [["Date", "Amount", "Account", "Status"]];
    const depositBody = deposits
      .filter((tx) => inRange(tx.date))
      .map((tx) => [
        tx.date,
        tx.amount,
        tx.account.toUpperCase(),
        tx.status.toUpperCase(),
      ]);

    autoTable(doc, {
      startY: currentY,
      head: depositHeaders,
      body:
        depositBody.length > 0
          ? depositBody
          : [["No deposit transactions found", "", "", ""]],
      theme: "striped",
      headStyles: {
        fillColor: primaryColor,
        textColor: [255, 255, 255],
        fontStyle: "bold",
      },
      styles: {
        fontSize: 9,
        cellPadding: 3,
      },
      alternateRowStyles: {
        fillColor: [243, 244, 246],
      },
    });

    currentY =
      (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable
        .finalY + 15;

    // 3. Withdrawal Transactions Section
    ensureSpace(30);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text(`Withdrawal Transactions${rangeLabel}`, 15, currentY);
    doc.line(15, currentY + 2, 195, currentY + 2);
    currentY += 6;

    const withdrawalHeaders = [["Date", "Amount", "Account", "Status"]];
    const withdrawalBody = withdrawals
      .filter((tx) => inRange(tx.date))
      .map((tx) => [
        tx.date,
        tx.amount,
        tx.account.toUpperCase(),
        tx.status.toUpperCase(),
      ]);

    autoTable(doc, {
      startY: currentY,
      head: withdrawalHeaders,
      body:
        withdrawalBody.length > 0
          ? withdrawalBody
          : [["No withdrawal transactions found", "", "", ""]],
      theme: "striped",
      headStyles: {
        fillColor: primaryColor,
        textColor: [255, 255, 255],
        fontStyle: "bold",
      },
      styles: {
        fontSize: 9,
        cellPadding: 3,
      },
      alternateRowStyles: {
        fillColor: [243, 244, 246],
      },
    });

    // Footer - Page Numbering
    const totalPages = doc.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
      doc.setPage(i);
      doc.setFontSize(8);
      doc.setTextColor(lightGray[0], lightGray[1], lightGray[2]);
      doc.text(`Page ${i} of ${totalPages}`, 105, 285, { align: "center" });
      doc.text("Confidential - For Internal Use Only", 15, 285);
    }

    doc.save(
      `${siteName.replace(/\s+/g, "_")}_Statement_${now.toISOString().split("T")[0]}.pdf`,
    );
  };

  return (
    <div className="w-full">
      {/* Hidden preloaded logo — browser loads it exactly like Logo.tsx so it's ready for PDF capture */}
      <img
        ref={logoImgRef}
        src={siteSettings?.logo || siteSettings?.logo_sm}
        alt=""
        aria-hidden
        className="absolute opacity-0 pointer-events-none w-px h-px -top-px -left-px"
      />
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <h2 className="text-lg font-medium">Transaction History</h2>
        <Button
          variant="default"
          className="bg-blue-600 hover:bg-blue-700 text-white flex items-center gap-2 group transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
          onClick={() => setIsDateRangeOpen(true)}
        >
          <Download className="h-4 w-4 transition-transform duration-300 group-hover:translate-y-0.5 group-hover:scale-110" />
          <span>Download Statement</span>
        </Button>
      </div>

      {isLoading ? (
        <div className="text-center py-20">
          <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-muted-foreground text-sm">
            Loading transactions...
          </p>
        </div>
      ) : error ? (
        <div className="text-center py-10 text-destructive bg-destructive/10 rounded-lg border border-destructive/20">
          {error}
        </div>
      ) : (
        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="flex w-full md:w-auto bg-card/40 border border-border/40 h-auto p-1 mb-8 rounded-xl gap-1 overflow-hidden shadow-inner">
            <TabsTrigger
              value="all"
              className="flex-1 md:px-8 py-2.5 rounded-lg font-medium text-muted-foreground bg-transparent data-[state=active]:!bg-black/40 data-[state=active]:text-primary data-[state=active]:border data-[state=active]:border-border/30 transition-all flex items-center justify-center gap-2 group border-transparent shadow-none"
            >
              <History className="h-4 w-4 text-muted-foreground group-data-[state=active]:text-primary" />
              <span className="text-sm">Order Book / Trade History</span>
            </TabsTrigger>
            <TabsTrigger
              value="deposit"
              className="flex-1 md:px-8 py-2.5 rounded-lg font-medium text-muted-foreground bg-transparent data-[state=active]:!bg-black/40 data-[state=active]:text-foreground data-[state=active]:border data-[state=active]:border-border/30 transition-all flex items-center justify-center gap-2 group border-transparent shadow-none"
            >
              <ArrowDownCircle className="h-4 w-4 text-muted-foreground group-data-[state=active]:text-green-500" />
              <span className="text-sm">Deposits</span>
            </TabsTrigger>
            <TabsTrigger
              value="withdrawal"
              className="flex-1 md:px-8 py-2.5 rounded-lg font-medium text-muted-foreground bg-transparent data-[state=active]:!bg-black/40 data-[state=active]:text-foreground data-[state=active]:border data-[state=active]:border-border/30 transition-all flex items-center justify-center gap-2 group border-transparent shadow-none"
            >
              <ArrowUpCircle className="h-4 w-4 text-muted-foreground group-data-[state=active]:text-red-500" />
              <span className="text-sm">Withdrawals</span>
            </TabsTrigger>
          </TabsList>

          <TabsContent value="all" className="mt-0 focus-visible:ring-0">
            {isLoadingClosed ? (
              <div className="text-center py-20">
                <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground text-sm">
                  Loading trades...
                </p>
              </div>
            ) : (
              <>
                <TradeList trades={closedTrades} />
                {hasMoreClosedTrades() && (
                  <div className="mt-6 flex justify-center">
                    <Button
                      variant="outline"
                      disabled={isLoadingClosed}
                      onClick={() => fetchMoreClosedTrades()}
                    >
                      {isLoadingClosed ? "Loading…" : "Load More Trades"}
                    </Button>
                  </div>
                )}
              </>
            )}
          </TabsContent>

          <TabsContent value="deposit" className="mt-0 focus-visible:ring-0">
            <TransactionList transactions={deposits} />
          </TabsContent>

          <TabsContent value="withdrawal" className="mt-0 focus-visible:ring-0">
            <TransactionList transactions={withdrawals} />
          </TabsContent>
        </Tabs>
      )}

      {/* Date Range Modal */}
      <Dialog open={isDateRangeOpen} onOpenChange={setIsDateRangeOpen}>
        <DialogContent className="sm:max-w-md bg-[#0F141C] border border-white/20 shadow-2xl backdrop-blur-xl rounded-2xl p-6 text-white">
          <DialogHeader className="space-y-0">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/20 text-blue-400 ring-4 ring-blue-500/10">
                <CalendarDays className="h-5 w-5" />
              </div>
              <div className="space-y-0.5 text-left">
                <DialogTitle className="text-base font-bold text-white">
                  Select Statement Range
                </DialogTitle>
                <DialogDescription className="text-xs text-white/90">
                  Choose presets or specify custom date boundaries
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          <div className="space-y-5 py-3">
            {/* Presets segment */}
            <div className="space-y-2">
              <span className="text-[10px] font-bold text-white uppercase tracking-wider">
                Quick Presets
              </span>
              <div className="grid grid-cols-4 gap-2">
                {(["all", "6m", "3m", "30d"] as const).map((preset) => {
                  const label =
                    preset === "all"
                      ? "All Time"
                      : preset === "6m"
                        ? "6 Months"
                        : preset === "3m"
                          ? "3 Months"
                          : "30 Days";
                  return (
                    <button
                      key={preset}
                      type="button"
                      onClick={() => setPreset(preset)}
                      className="text-xs py-2 px-2 rounded-xl border border-white/30 hover:border-white/70 hover:bg-white/10 active:scale-95 transition-all text-white bg-white/5 shadow-sm font-medium"
                    >
                      {label}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Custom inputs */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1.5 text-left">
                <Label
                  htmlFor="start-date"
                  className="text-[10px] font-bold text-white uppercase tracking-wider"
                >
                  From Date
                </Label>
                <Input
                  id="start-date"
                  type="date"
                  value={startDate}
                  max={endDate || undefined}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="bg-white/5 border border-white/30 focus:border-white/60 focus:ring-4 focus:ring-white/5 rounded-xl text-xs py-5 h-auto transition-all w-full cursor-pointer hover:border-white/50 text-white"
                />
              </div>
              <div className="space-y-1.5 text-left">
                <Label
                  htmlFor="end-date"
                  className="text-[10px] font-bold text-white uppercase tracking-wider"
                >
                  To Date
                </Label>
                <Input
                  id="end-date"
                  type="date"
                  value={endDate}
                  min={startDate || undefined}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="bg-white/5 border border-white/30 focus:border-white/60 focus:ring-4 focus:ring-white/5 rounded-xl text-xs py-5 h-auto transition-all w-full cursor-pointer hover:border-white/50 text-white"
                />
              </div>
            </div>
          </div>

          <DialogFooter className="flex items-center justify-end gap-3 mt-2 pt-4 border-t border-white/10 sm:justify-end">
            <Button
              className="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 border border-blue-500/50 hover:border-blue-400 text-white rounded-xl px-6 font-semibold text-xs h-9 shadow-md hover:shadow-lg transition-all duration-300"
              disabled={isGenerating}
              onClick={async () => {
                setIsGenerating(true);
                try {
                  await handleDownloadStatement(startDate, endDate);
                } finally {
                  setIsGenerating(false);
                  setIsDateRangeOpen(false);
                }
              }}
            >
              {isGenerating ? (
                <span className="flex items-center gap-2">
                  <span className="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                  Generating…
                </span>
              ) : (
                <span className="flex items-center gap-2">
                  <Download className="h-4 w-4" />
                  Download PDF
                </span>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default TransactionHistory;

import { useNavigate } from "react-router-dom";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { X, CreditCard, TrendingUp, Shield } from "lucide-react";

interface KYCDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onClose?: () => void;
}

interface Feature {
  title: string;
  description: string;
  icon: React.ReactNode;
}

export function KYCDialog({ open, onOpenChange, onClose }: KYCDialogProps) {
  const navigate = useNavigate();

  const features: Feature[] = [
    {
      title: "Card Deposits",
      description:
        "Securely link your credit or debit cards for instant account funding.",
      icon: (
        <div className="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
          <CreditCard className="w-6 h-6 text-blue-500" />
        </div>
      ),
    },
    {
      title: "Higher Transaction Limits",
      description:
        "Increase your daily withdrawal and deposit caps by up to 500%.",
      icon: (
        <div className="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
          <TrendingUp className="w-6 h-6 text-orange-500" />
        </div>
      ),
    },
    {
      title: "Enhanced Security",
      description:
        "Protect your account with industry-standard compliance and fraud prevention.",
      icon: (
        <div className="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center">
          <Shield className="w-6 h-6 text-emerald-500" />
        </div>
      ),
    },
  ];

  const handleStartVerification = () => {
    onOpenChange(false);
    onClose?.();
    navigate("/main/verification");
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg p-0 overflow-hidden border-0 bg-card">
        <button
          onClick={() => onOpenChange(false)}
          className="absolute top-3 right-3 z-10 bg-card hover:bg-card/80 rounded-full p-1.5 transition-colors border border-border"
        >
          <X className="w-4 h-4 text-muted-foreground" />
        </button>

        <div className="w-full">
          {/* Header Image */}
          <div className="relative w-full h-56 bg-gradient-to-br from-accent/20 to-accent/10 flex items-center justify-center border-b border-border">
            <div className="text-center">
              <div className="w-16 h-16 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg
                  className="w-8 h-8 text-accent"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <p className="text-sm text-muted-foreground">
                Identity Verification
              </p>
            </div>
          </div>

          {/* Content */}
          <div className="p-6 space-y-6">
            <div>
              <h2 className="text-2xl font-bold text-foreground mb-2">
                Unlock full access
              </h2>

              <p className="text-sm text-muted-foreground leading-relaxed">
                To deposit funds via Visa or Mastercard and access higher
                transaction limits, we need to quickly verify your identity.
              </p>
            </div>

            {/* Features Grid */}
            <div className="space-y-3">
              {features.map((feature) => (
                <div
                  key={feature.title}
                  className="flex gap-3 p-3 border border-border rounded-lg hover:border-accent/50 hover:bg-card/50 transition-colors"
                >
                  <div className="shrink-0 mt-0.5">{feature.icon}</div>
                  <div className="flex-1">
                    <h3 className="font-semibold text-foreground mb-0.5 text-sm">
                      {feature.title}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                      {feature.description}
                    </p>
                  </div>
                </div>
              ))}
            </div>

            {/* CTA Button */}
            <Button
              onClick={handleStartVerification}
              className="w-full bg-accent hover:bg-accent/90 text-primary-foreground py-6 text-sm font-semibold rounded-lg transition-colors"
            >
              Start Verification →
            </Button>

            {/* Footer Text */}
            <p className="text-xs text-muted-foreground text-center flex items-center justify-center gap-1.5">
              <svg
                className="w-3.5 h-3.5 text-emerald-500"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clipRule="evenodd"
                />
              </svg>
              Takes about 2-3 minutes to complete
            </p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

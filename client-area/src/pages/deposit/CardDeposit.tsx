import { useState } from "react";
import { motion } from "framer-motion";
import { DollarSign, Loader2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { toast } from "@/components/ui/sonner";
import axiosInstance from "@/lib/axios";
import { AxiosError } from "axios";
import { WireTransferConfirmationModal } from "@/components/withdrawal/WireTransferConfirmationModal.tsx";

export default function CardDeposit({
                                      onDepositSuccess,
                                    }: {
  onDepositSuccess?: () => void;
}) {
  const [cardDetails, setCardDetails] = useState({
    name: "",
    number: "",
    expiry: "",
    cvv: "",
    amount: "",
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [focusedField, setFocusedField] = useState<string | null>(null);

  console.log('focusedField', focusedField)

  const [showConfirmationModal, setShowConfirmationModal] = useState(false);

  const handleInputChange = (field: string, value: string) => {
    // Apply formatting for specific fields
    let formattedValue = value;

    if (field === "number") {
      // Format card number to have spaces every 4 digits
      formattedValue = value.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();
    } else if (field === "expiry") {
      // Format expiry date as MM/YY
      formattedValue = value
          .replace(/\D/g, '')
          .replace(/(\d{2})(\d{0,2})/, (_, p1, p2) => p2 ? `${p1}/${p2}` : p1);
    }

    setCardDetails({
      ...cardDetails,
      [field]: formattedValue,
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Form validation
    if (!cardDetails.name.trim()) {
      toast.error("Please enter the cardholder name");
      return;
    }

    // Card number, expiry and CVV are no longer collected here -- they are
    // entered on the payment provider's own hosted checkout page.

    // Amount validation
    if (!cardDetails.amount || parseFloat(cardDetails.amount) <= 0) {
      toast.error("Please enter a valid amount");
      return;
    }

    // If validation passes, show the confirmation modal
    setShowConfirmationModal(true);
  };

  const handleConfirm = async () => {
    if (isSubmitting) return;

    setIsSubmitting(true);
    try {
      const response = await axiosInstance.post("/user/deposit/store", {
        amount: cardDetails.amount,
        method: "card",
        card_holder_name: cardDetails.name,
      });

      // Hosted checkout: the deposit exists but nothing has been paid yet, so
      // hand the browser over instead of reporting success.
      const redirectUrl = response?.data?.data?.redirect_url;

      if (redirectUrl) {
        toast.success("Taking you to secure checkout");
        window.location.assign(redirectUrl);

        return;
      }

      toast.success("Deposit request submitted successfully");
      onDepositSuccess?.(); // Call the success callback
      // Reset form
      setCardDetails({
        name: "",
        number: "",
        expiry: "",
        cvv: "",
        amount: "",
      });
      setShowConfirmationModal(false);
    } catch (error) {
      const axiosError = error as AxiosError<{ message: string }>;
      toast.error(
          axiosError.response?.data?.message || "Failed to submit deposit request"
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
      <div className="flex items-center justify-center p-4">
        <div className="w-full max-w-4xl grid grid-cols-1 xl:grid-cols-2 gap-8">
          {/* Credit Card Preview */}
          <div className="flex items-center justify-center">
            <motion.div
                className="w-full max-w-md aspect-[1.6/1] relative rounded-2xl overflow-hidden shadow-lg"
                whileHover={{ scale: 1.02 }}
                transition={{ duration: 0.3 }}
            >
              <div className="absolute inset-0 bg-gradient-to-b from-muted to-gray-400 rounded-2xl">
                <div className="absolute bottom-0 w-full h-1/2 bg-gray-600 rounded-b-2xl" />
              </div>

              {/* Card Chip */}
              <div className="absolute top-[10%] left-[10%]">
                <svg
                    width="50"
                    height="40"
                    viewBox="0 0 50 40"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                  <rect
                      x="1"
                      y="1"
                      width="48"
                      height="38"
                      rx="4"
                      stroke="white"
                      strokeWidth="2"
                  />
                  <rect
                      x="10"
                      y="15"
                      width="30"
                      height="10"
                      rx="5"
                      stroke="white"
                      strokeWidth="2"
                  />
                  <line
                      x1="25"
                      y1="5"
                      x2="25"
                      y2="35"
                      stroke="white"
                      strokeWidth="2"
                  />
                </svg>
              </div>

              {/* No card number to mirror -- it is never typed on this page. */}
              <div className="absolute top-[40%] left-[10%] right-[10%]">
                <p className="text-xs text-muted-foreground mb-1">card number</p>
                <p className="text-base sm:text-lg md:text-xl lg:text-2xl font-mono tracking-wider truncate">
                  #### #### #### ####
                </p>
              </div>

              {/* Cardholder Name */}
              <div className="absolute bottom-[10%] left-[10%] max-w-[50%]">
                <p className="text-xs text-muted-foreground mb-1">
                  cardholder name
                </p>
                <p className="text-xs sm:text-sm md:text-md font-mono truncate">
                  {cardDetails.name || "YOUR NAME"}
                </p>
              </div>

              {/* Expiration */}
              <div className="absolute bottom-[10%] right-[10%] text-right">
                <p className="text-xs text-muted-foreground mb-1">expiration</p>
                <div className="flex items-center justify-end">
                  <p className="text-[8px] sm:text-[10px] mr-1">VALID THRU</p>
                  <p className="text-xs sm:text-sm md:text-md font-mono">MM/YY</p>
                </div>
              </div>
            </motion.div>
          </div>

          {/* Payment Form */}
          <div className="flex flex-col justify-center space-y-4 sm:space-y-6">
            <div className="space-y-2">
              <Label htmlFor="name">Name</Label>
              <motion.div whileHover={{ scale: 1.01 }} whileTap={{ scale: 0.99 }}>
                <Input
                    id="name"
                    className="bg-background/10 border-primary/20 text-muted-foreground focus:border-primary/50 transition-all"
                    value={cardDetails.name}
                    onChange={(e) => handleInputChange("name", e.target.value)}
                    onFocus={() => setFocusedField("name")}
                    onBlur={() => setFocusedField(null)}
                />
              </motion.div>
            </div>

            {/* Card number, expiry and CVV inputs removed on purpose. They
                are entered on the payment provider's PCI-compliant checkout
                page, never on this platform. */}
            <div className="rounded-lg border border-primary/20 bg-background/10 px-4 py-3 text-sm text-muted-foreground">
              You will be taken to a secure checkout page to enter your card details and
              complete the payment. Your balance is credited once the payment is confirmed.
            </div>

            <div className="space-y-2">
              <Label htmlFor="amount">Amount</Label>
              <div className="flex space-x-4">
                <div className="relative flex-1">
                  <motion.div
                      whileHover={{ scale: 1.01 }}
                      whileTap={{ scale: 0.99 }}
                      className="relative"
                  >
                    <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                    <Input
                        id="amount"
                        type="number"
                        className="bg-background/10 border-primary/20 text-muted-foreground focus:border-primary/50 transition-all pl-10"
                        placeholder="0.00"
                        value={cardDetails.amount}
                        onChange={(e) =>
                            handleInputChange("amount", e.target.value)
                        }
                        onFocus={() => setFocusedField("amount")}
                        onBlur={() => setFocusedField(null)}
                    />
                  </motion.div>
                </div>
              </div>
            </div>

            <motion.div
                whileHover={{ scale: 1.03 }}
                whileTap={{ scale: 0.97 }}
                className="pt-4"
            >
              <Button
                  className="w-full"
                  onClick={handleSubmit}
                  disabled={isSubmitting}
              >
                {isSubmitting ? (
                    <>
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                      Processing...
                    </>
                ) : (
                    "Continue"
                )}
              </Button>
            </motion.div>
          </div>
        </div>

        <WireTransferConfirmationModal
            open={showConfirmationModal}
            isCard={true}
            onOpenChange={setShowConfirmationModal}
            onConfirm={handleConfirm}
            isSubmitting={isSubmitting}
        />
      </div>
  );
}

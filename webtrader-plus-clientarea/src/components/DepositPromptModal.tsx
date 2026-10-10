import React, { useState, useEffect, useRef } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
// import { motion, AnimatePresence } from "framer-motion";
import { ArrowRight } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { useSearchParams } from "react-router-dom";
import useSiteSettingsStore from "@/store/siteSettingStore";

interface DepositPromptModalProps {
  onDeposit: (method: "card" | "crypto") => void;
}

const DepositPromptModal: React.FC<DepositPromptModalProps> = ({
  onDeposit,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedMethod, setSelectedMethod] = useState<"card" | "crypto">(
    "card",
  );
  const [searchParams] = useSearchParams();
  const wantsDeposit = searchParams.has("deposit");
  const autoPrompted = useRef(false);
  // Tenant module, from CRM-Classic > Settings > Modules. Undefined means the
  // settings have not arrived yet, which is not the same as "off" -- treat only
  // an explicit false as off.
  const enabled =
    useSiteSettingsStore((state) => state.settings?.deposit_prompt_enabled) !== false;

  useEffect(() => {
    if (!enabled) {
      setIsOpen(false);
      return;
    }

    if (wantsDeposit) {
      setIsOpen(true);
      return;
    }

    if (autoPrompted.current) return;
    autoPrompted.current = true;

    const checkDeposits = async () => {
      try {
        const response = await axiosInstance.get("/user/deposits");
        const deposits = response.data?.data?.data || [];

        // Only show if user has no deposits
        if (deposits.length === 0) {
          const timer = setTimeout(() => {
            setIsOpen(true);
          }, 2000);
          return () => clearTimeout(timer);
        }
      } catch (error) {
        console.error("Error checking deposits:", error);
      }
    };

    checkDeposits();
  }, [wantsDeposit, enabled]);

  const handleDeposit = () => {
    setIsOpen(false);
    onDeposit(selectedMethod);
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent
        onPointerDownOutside={(e) => e.preventDefault()}
        className="sm:max-w-[550px] p-0 overflow-hidden border-[#CBDBF5] bg-[#F8F9FF] shadow-xl rounded-[20px] gap-0 [&>div:first-child]:p-0"
      >
        <div className="relative">
          {/* Header */}
          <DialogHeader className="p-5 text-left border-b border-[#CBDBF5]">
            <DialogTitle className="text-[#00234B] text-lg font-bold">
              Fund Your Account
            </DialogTitle>
          </DialogHeader>

          <div className="flex flex-col items-center px-6 pb-6">
            {/* Illustration */}
            <div className="w-full flex justify-center my-6">
              <img
                src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1779356239/image_2_tqr3jo.png"
                alt="Deposit illustration"
                className="w-32 h-32 object-contain"
              />
            </div>

            {/* Headline */}
            <div className="text-center space-y-2 mb-6">
              <h3 className="text-[#00234B] text-xl font-bold">
                Start Profiting Now!
              </h3>
              <p className="text-[#64748B] text-[14px] leading-relaxed max-w-[350px] mx-auto">
                Funding your account is a{" "}
                <span className="text-[#00234B] font-bold">
                  necessary process
                </span>{" "}
                to get you profitable.
              </p>
            </div>

            {/* Payment Method Selection */}
            <div className="w-full space-y-3">
              <p className="text-[#64748B] text-[10px] font-bold tracking-wider uppercase opacity-80">
                SELECT PAYMENT METHOD
              </p>

              <div className="space-y-3">
                {/* Credit/Debit Card */}
                <button
                  onClick={() => setSelectedMethod("card")}
                  className={`w-full flex items-center justify-between p-3.5 rounded-lg border-2 transition-all ${
                    selectedMethod === "card"
                      ? "border-[#00234B] bg-[#EFF4FF]"
                      : "border-[#CBDBF5] bg-white"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-md bg-white border border-[#CBDBF5] flex items-center justify-center shadow-sm">
                      <img
                        src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1779371845/image_4_zkvwmc.png"
                        alt="Card"
                        className="w-5 h-5 object-contain"
                      />
                    </div>
                    <div className="text-left">
                      <p className="text-[#00234B] text-sm font-bold">
                        Credit / Debit Card
                      </p>
                      <p className="text-[#64748B] text-[11px]">KYC Required</p>
                    </div>
                  </div>
                  <div
                    className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                      selectedMethod === "card"
                        ? "border-[#00234B]"
                        : "border-[#CBDBF5]"
                    }`}
                  >
                    {selectedMethod === "card" && (
                      <div className="w-2 h-2 rounded-full bg-[#00234B]" />
                    )}
                  </div>
                </button>

                {/* Crypto */}
                <button
                  onClick={() => setSelectedMethod("crypto")}
                  className={`w-full flex items-center justify-between p-3.5 rounded-lg border-2 transition-all ${
                    selectedMethod === "crypto"
                      ? "border-[#00234B] bg-[#EFF4FF]"
                      : "border-[#CBDBF5] bg-white"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-md bg-white border border-[#CBDBF5] flex items-center justify-center shadow-sm">
                      <img
                        src="https://res.cloudinary.com/dyp8gtllq/image/upload/v1779371846/image_5_irs9lc.png"
                        alt="Crypto"
                        className="w-5 h-5 object-contain"
                      />
                    </div>
                    <div className="text-left">
                      <p className="text-[#00234B] text-sm font-bold">Crypto</p>
                      <p className="text-[#64748B] text-[11px]">
                        BTC, ETH, USDT accepted
                      </p>
                    </div>
                  </div>
                  <div
                    className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                      selectedMethod === "crypto"
                        ? "border-[#00234B]"
                        : "border-[#CBDBF5]"
                    }`}
                  >
                    {selectedMethod === "crypto" && (
                      <div className="w-2 h-2 rounded-full bg-[#00234B]" />
                    )}
                  </div>
                </button>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="bg-[#EFF4FF] p-5 border-t border-[#CBDBF5]">
            <Button
              onClick={handleDeposit}
              className="w-full h-12 rounded-lg text-white text-base font-bold bg-[#23BF08] hover:bg-[#1ea307] transition-all flex items-center justify-center gap-2 shadow-sm"
            >
              Proceed to Deposit
              <ArrowRight className="w-4 h-4" />
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default DepositPromptModal;

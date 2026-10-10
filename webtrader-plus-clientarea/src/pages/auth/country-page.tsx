import { useEffect, useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { toast } from "@/components/ui/sonner";
import { ChevronDown, MapPin } from "lucide-react";
import ReactCountryFlag from "react-country-flag";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Button } from "@/components/ui/button";
import Logo from "@/components/Logo";
import { countries } from "@/constants/countries";

export default function CountryResidencePage() {
  const navigate = useNavigate();
  const { search } = useLocation();
  const [registrationData, setRegistrationData] = useState<{
    email?: string;
    password?: string;
  } | null>(null);
  const [countryOfResidence, setCountryOfResidence] = useState("united-states");
  const [nationality, setNationality] = useState("united-states");
  const [agreePrivacy, setAgreePrivacy] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    const data = localStorage.getItem("registration_data");
    if (!data) {
      toast.error("Please complete the registration process first");
      navigate(`/signup${search}`);
      return;
    }

    const parsedData = JSON.parse(data);

    console.log("parsedData", parsedData);

    setCountryOfResidence(parsedData?.country ?? "united-states");
    setNationality(parsedData?.country ?? "united-states");

    setRegistrationData(parsedData);
  }, [navigate]);

  const handleContinue = () => {
    if (!agreePrivacy) {
      toast.error("Please agree to the Privacy Policy");
      return;
    }

    setIsLoading(true);

    const updatedData = {
      ...registrationData,
      countryOfResidence,
      nationality,
      agreePrivacy,
    };

    localStorage.setItem("registration_data", JSON.stringify(updatedData));

    navigate(`/register${search}`);
  };

  return (
    <div className="min-h-screen bg-[#0A1A2A]_ flex flex-col">
      <div className="w-full bg-[#0C1E32] text-white/80 py-4 px-4 text-center text-sm">
        Trading CFDs carries a high level of risk to your capital, and you
        should only trade with money you can afford to lose.
      </div>

      {/* Navigation */}
      <div className="w-full border-t-gray-400 border-t bg-[#0C1E32] px-4 md:px-8 py-3 md:py-3 flex justify-between items-center">
        <Logo />
        <Button variant="ghost" className="text-white hover:bg-white/10">
          <svg className="w-5 h-5 mr-2" viewBox="0 0 36 36">
            <rect fill="#00247D" width="36" height="27" />
            <path d="M0,0 L36,27 M36,0 L0,27" stroke="#fff" strokeWidth="5.4" />
            <path
              d="M0,0 L36,27 M36,0 L0,27"
              stroke="#cf142b"
              strokeWidth="3.6"
            />
            <path d="M18,0 V27 M0,13.5 H36" stroke="#fff" strokeWidth="9" />
            <path
              d="M18,0 V27 M0,13.5 H36"
              stroke="#cf142b"
              strokeWidth="5.4"
            />
          </svg>
          EN
          <ChevronDown className="h-4 w-4 ml-1" />
        </Button>
      </div>

      {/* Main content */}
      <div className="flex-1 bg-white flex flex-col items-center px-4 md:px-4 py-8">
        {/* Progress indicator */}
        <div className="w-full max-w-xl  mb-12">
          <div className="flex items-center justify-center gap-2">
            <div className="flex-col items-center gap-2">
              <div className="h-2 w-40 bg-[#FF5B22] rounded-full"></div>
              <span className="text-trading-dark text-sm">Set Up</span>
            </div>
            <div className="flex-col items-center gap-2">
              <div className="h-2 w-40 bg-gray-950/10 rounded-full"></div>
              <span className="text-gray-700/50 text-sm ">
                Financial Profile
              </span>
            </div>
            <div className="flex-col items-center gap-2">
              <div className="h-2 w-40 bg-gray-950/10 rounded-full"></div>
              <span className="text-gray-700/50 text-sm">ID Check</span>
            </div>
          </div>
        </div>

        {/* Form content */}
        <div className="w-full max-w-xl bg-white rounded-3xl p-8">
          <div className="flex justify-center- mb-6">
            <div className="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center">
              <MapPin className="w-10 h-10 text-gray-400" />
            </div>
          </div>

          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            Let's get you set up!
          </h1>
          <p className="text-gray-600 mb-8">
            Please select your Country of Residence and Nationality. We may
            request supporting documentation during the application process.
          </p>

          <div className="space-y-6">
            <div>
              <label className="text-gray-700 text-sm mb-2 block">
                Country of Residence
              </label>
              <Select
                value={countryOfResidence}
                onValueChange={setCountryOfResidence}
              >
                <SelectTrigger className="w-full bg-white border-gray-200 text-white rounded-lg py-6 px-4">
                  <SelectValue>
                    {(() => {
                      const selectedCountry = countries.find(
                        (c) => c.value === countryOfResidence,
                      );
                      return selectedCountry ? (
                        <div className="flex items-center gap-2">
                          <ReactCountryFlag
                            countryCode={selectedCountry.code}
                            svg
                            style={{ fontSize: "1.5em" }}
                          />
                          <span>{selectedCountry.label}</span>
                        </div>
                      ) : (
                        "Select country"
                      );
                    })()}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent className="bg-white border-gray-200">
                  {countries.map((country) => (
                    <SelectItem
                      key={country.value}
                      value={country.value}
                      className="text-gray-900 bg-white hover:bg-gray-100"
                    >
                      <div className="flex items-center gap-2">
                        <ReactCountryFlag
                          countryCode={country.code}
                          svg
                          style={{ fontSize: "1.5em" }}
                        />
                        <span>{country.label}</span>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <label className="text-gray-700 text-sm mb-2 block">
                Nationality
              </label>
              <Select value={nationality} onValueChange={setNationality}>
                <SelectTrigger className="w-full bg-white border-gray-200 text-white rounded-lg py-6 px-4">
                  <SelectValue>
                    {(() => {
                      const selectedCountry = countries.find(
                        (c) => c.value === nationality,
                      );
                      return selectedCountry ? (
                        <div className="flex items-center gap-2">
                          <ReactCountryFlag
                            countryCode={selectedCountry.code}
                            svg
                            style={{ fontSize: "1.5em" }}
                          />
                          <span>{selectedCountry.label}</span>
                        </div>
                      ) : (
                        "Select nationality"
                      );
                    })()}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent className="bg-white border-gray-200">
                  {countries.map((country) => (
                    <SelectItem
                      key={country.value}
                      value={country.value}
                      className="text-gray-900 hover:bg-gray-100"
                    >
                      <div className="flex items-center gap-2">
                        <ReactCountryFlag
                          countryCode={country.code}
                          svg
                          style={{ fontSize: "1.5em" }}
                        />
                        <span>{country.label}</span>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="bg-[#00A98D]/90 text-white px-6 py-4 rounded-lg text-sm">
              You will be onboarded with the Trade Nation LTD, registered in
              England & Wales under company number 07073413, is authorised and
              regulated by the Financial Conduct Authority under firm reference
              number 525164.
            </div>

            <div className="flex items-start gap-2">
              <Checkbox
                id="privacy"
                checked={agreePrivacy}
                onCheckedChange={(checked) =>
                  setAgreePrivacy(checked as boolean)
                }
                className="border-[#FF5B22] data-[state=checked]:bg-[#FF5B22] data-[state=checked]:border-[#FF5B22] mt-1"
              />
              <label
                htmlFor="privacy"
                className="text-gray-700 text-sm leading-tight"
              >
                I agree to the{" "}
                <span className="text-gray-900 font-bold">Privacy Policy</span>{" "}
                and I am over 18.
              </label>
            </div>

            <Button
              onClick={handleContinue}
              disabled={isLoading || !agreePrivacy}
              className="w-full bg-[#0A1A2A] hover:bg-[#0A1A2A]/90 text-white rounded-full py-6 text-lg font-semibold mt-6"
            >
              {isLoading ? "Processing..." : "Start Registration"}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

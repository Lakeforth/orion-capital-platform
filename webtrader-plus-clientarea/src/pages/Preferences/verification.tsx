import { useState, useRef, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { CheckCircle2, Image as LucideImage, X, Loader, Camera, Upload, IdCard, Check, Info } from "lucide-react";
import { Link } from "react-router-dom";
import Webcam from "react-webcam";
import { KYC_PARTNERS } from "@/constants/kyc-partners";
import axiosInstance from "@/lib/axios";
import useUserStore from "@/store/userStore";
import { toast } from "@/components/ui/sonner";
import { AxiosError } from "axios";


export default function KYCVerifyPage() {
  const [currentStep, setCurrentStep] = useState(0); // 0: ID, 1: Residence, 2: Awaiting
  const [selectedIdType, setSelectedIdType] = useState<string>("passport");
  const [frontFile, setFrontFile] = useState<File | null>(null);
  const [backFile, setBackFile] = useState<File | null>(null);
  const [residenceFile, setResidenceFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [showWebcamFront, setShowWebcamFront] = useState(false);
  const [showWebcamBack, setShowWebcamBack] = useState(false);
  const [dragActiveFront, setDragActiveFront] = useState(false);
  const [dragActiveBack, setDragActiveBack] = useState(false);
  const [dragActiveResidence, setDragActiveResidence] = useState(false);

  const user = useUserStore((state) => state.user);
  const isVerified = user?.verification_status === "approved";

  useEffect(() => {
    if (isVerified) setCurrentStep(2);
  }, [isVerified]);

  const fileInputFrontRef = useRef<HTMLInputElement>(null);
  const fileInputBackRef = useRef<HTMLInputElement>(null);
  const fileInputResidenceRef = useRef<HTMLInputElement>(null);
  const webcamFrontRef = useRef<Webcam>(null);
  const webcamBackRef = useRef<Webcam>(null);

  const idTypes = [
    {
      id: "passport",
      label: "Passport",
      description: "International travel document",
    },
    {
      id: "drivers",
      label: "Driver's License",
      description: "State issued license",
    },
    { id: "national", label: "National ID", description: "Government ID card" },
  ];

  // File handling functions
  const handleFileSelect = (file: File, type: "front" | "back" | "residence") => {
    if (file && file.type.startsWith("image/")) {
      if (type === "front") {
        setFrontFile(file);
      } else if (type === "back") {
        setBackFile(file);
      } else if (type === "residence") {
        setResidenceFile(file);
      }
    }
  };

  const handleDrag = (e: React.DragEvent, type: "front" | "back" | "residence") => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      if (type === "front") setDragActiveFront(true);
      else if (type === "back") setDragActiveBack(true);
      else if (type === "residence") setDragActiveResidence(true);
    } else if (e.type === "dragleave") {
      if (type === "front") setDragActiveFront(false);
      else if (type === "back") setDragActiveBack(false);
      else if (type === "residence") setDragActiveResidence(false);
    }
  };

  const handleDrop = (e: React.DragEvent, type: "front" | "back" | "residence") => {
    e.preventDefault();
    e.stopPropagation();
    if (type === "front") setDragActiveFront(false);
    else if (type === "back") setDragActiveBack(false);
    else if (type === "residence") setDragActiveResidence(false);

    const files = e.dataTransfer.files;
    if (files && files[0]) {
      handleFileSelect(files[0], type);
    }
  };

  const handleBrowseClick = (type: "front" | "back" | "residence") => {
    if (type === "front") {
      fileInputFrontRef.current?.click();
    } else if (type === "back") {
      fileInputBackRef.current?.click();
    } else if (type === "residence") {
      fileInputResidenceRef.current?.click();
    }
  };

  const handleFileInputChange = (
    e: React.ChangeEvent<HTMLInputElement>,
    type: "front" | "back" | "residence",
  ) => {
    const files = e.target.files;
    if (files && files[0]) {
      handleFileSelect(files[0], type);
    }
  };

  const captureWebcam = (type: "front" | "back") => {
    let webcam;
    if (type === "front") webcam = webcamFrontRef.current;
    else if (type === "back") webcam = webcamBackRef.current;

    if (webcam) {
      const imageSrc = webcam.getScreenshot();
      if (imageSrc) {
        // Convert base64 to File
        fetch(imageSrc)
          .then((res) => res.blob())
          .then((blob) => {
            const file = new File(
              [blob],
              `${type}.jpg`,
              { type: "image/jpeg" },
            );
            handleFileSelect(file, type);
            if (type === "front") setShowWebcamFront(false);
            else if (type === "back") setShowWebcamBack(false);
          });
      }
    }
  };

  const handleContinue = async () => {
    if (currentStep === 0) {
      const isPassport = selectedIdType === "passport";
      if (!frontFile || (!isPassport && !backFile)) {
        alert(isPassport ? "Please upload your passport bio-data page" : "Please upload both front and back ID documents");
        return;
      }
      setCurrentStep(1);
      return;
    }

    if (currentStep === 1) {
      if (!residenceFile) {
        alert("Please upload proof of residence document");
        return;
      }

      setIsUploading(true);
      try {
        const idFrontForm = new FormData();
        idFrontForm.append("type", "id");
        idFrontForm.append("id_front", frontFile!);
        await axiosInstance.post("/update/kyc", idFrontForm, { headers: { "Content-Type": "multipart/form-data" } });

        if (selectedIdType !== "passport" && backFile) {
          const idBackForm = new FormData();
          idBackForm.append("type", "id");
          idBackForm.append("id_back", backFile);
          await axiosInstance.post("/update/kyc", idBackForm, { headers: { "Content-Type": "multipart/form-data" } });
        }

        const addressForm = new FormData();
        addressForm.append("type", "proof_of_address");
        addressForm.append("document", residenceFile!);
        await axiosInstance.post("/update/kyc", addressForm, { headers: { "Content-Type": "multipart/form-data" } });

        setCurrentStep(2);
      } catch (error) {
        const axiosError = error as AxiosError<{ message: string }>;
        const msg = axiosError.response?.data?.message || (error as Error).message || "Upload failed. Please try again.";
        toast.error(msg);
      } finally {
        setIsUploading(false);
      }
    }
  };

  return (
    <main className=" bg-background flex flex-col items-center justify-center pt-6">
      <div className={`${currentStep === 2 ? 'max-w-5xl' : 'max-w-4xl'} w-full mx-auto px-4 transition-all duration-500`}>
        {currentStep === 2 ? (
          // Awaiting Verification View
          <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3 bg-card border-2 border-border rounded-2xl p-5 shadow-sm">
            <div className="relative w-24 h-24">
              <div className="absolute inset-0 bg-accent rounded-full opacity-10"></div>
              <div className="absolute inset-0 flex items-center justify-center">
                {isVerified ? (
                  <CheckCircle2 className="w-12 h-12 text-emerald-500" />
                ) : (
                  <Loader className="w-12 h-12 text-accent animate-spin" />
                )}
              </div>
            </div>
            <div className="text-center space-y-2">
              <h2 className="text-xl font-black text-foreground">
                {isVerified ? "Verification Approved" : "Awaiting Verification"}
              </h2>
              <p className="text-sm max-w-sm mx-auto leading-relaxed text-foreground font-bold">
                {isVerified
                  ? "Your identity verification has been approved."
                  : "Your documents are being reviewed. This typically takes 1-2 business days. We'll notify you via email once the verification is complete."}
              </p>
            </div>
            {!isVerified && <div className="w-full max-w-xs bg-muted/20 border-2 border-border rounded-xl p-3.5 space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="text-muted-foreground font-black">ID Type:</span>
                <span className="text-foreground font-black capitalize text-xs">
                  {selectedIdType}
                </span>
              </div>
              <div className="flex items-center justify-between text-xs">
                <span className="text-muted-foreground font-black">ID Front:</span>
                <span className="text-foreground font-black truncate max-w-[100px] text-xs">
                  {frontFile?.name}
                </span>
              </div>
              {selectedIdType !== "passport" && (
                <div className="flex items-center justify-between text-xs">
                  <span className="text-muted-foreground font-black">ID Back:</span>
                  <span className="text-foreground font-black truncate max-w-[100px] text-xs">
                    {backFile?.name}
                  </span>
                </div>
              )}
              <div className="flex items-center justify-between text-xs pt-1.5 border-t border-border">
                <span className="text-muted-foreground font-black">Address:</span>
                <span className="text-foreground font-black truncate max-w-[100px] text-xs">
                  {residenceFile?.name}
                </span>
              </div>
            </div>}
            <div className="w-full pt-4 border-t-2 border-border mt-2 text-center space-y-4">
              <div className="space-y-1">
                <h3 className="text-[10px] font-black text-foreground uppercase tracking-widest bg-accent/10 py-0.5 px-3 rounded-full inline-block">Integrated KYC Ecosystem</h3>
                <p className="text-xs max-w-sm mx-auto leading-relaxed text-foreground font-bold">
                  Your identity verification status is synchronized with our global partners.
                </p>
              </div>

              <div className="flex flex-nowrap md:grid md:grid-cols-10 gap-3 sm:gap-4 py-2 overflow-x-auto md:overflow-x-visible no-scrollbar justify-start md:justify-items-center">
                {KYC_PARTNERS.map((partner) => (
                  <div key={partner.name} className="flex flex-col items-center gap-1 group cursor-default flex-shrink-0 w-[65px] sm:w-auto">
                    <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-muted/20 border border-border/50 flex items-center justify-center p-2 group-hover:border-accent/40 group-hover:bg-accent/5 transition-all duration-300">
                      <img 
                        src={partner.logo} 
                        alt={partner.name} 
                        className="max-w-full max-h-full object-contain filter grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-500" 
                      />
                    </div>
                    <span className="text-[10px] font-black text-foreground group-hover:text-accent transition-colors uppercase tracking-tight text-center">
                      {partner.name}
                    </span>
                  </div>
                ))}
              </div>

              <div className="pt-2">
                <Link 
                  to="/main/kyc-partners" 
                  className="inline-flex items-center gap-2 text-xs font-black text-accent hover:text-accent/80 transition-colors uppercase tracking-wider py-2 px-6 bg-accent/5 rounded-full border border-accent/20"
                >
                  Explore Partner Network
                  <Info className="w-4 h-4" />
                </Link>
              </div>
            </div>

            <Link to="/" className="pt-2">
              <Button size="lg" className="bg-accent text-slate-900 hover:bg-emerald-600 hover:text-white px-8 py-3.5 rounded-xl font-black text-sm transition-all shadow-lg">
                Back to Dashboard
              </Button>
            </Link>
          </div>
        ) : currentStep === 1 ? (
          // Proof of Residence View
          <div className="space-y-4">
            <div className="mb-0">
              <h2 className="text-lg font-black text-foreground mb-0">Proof of Residence</h2>
              <p className="text-xs text-foreground font-bold leading-relaxed max-w-2xl">
                Provide a document that confirms your current address.
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-6">
              <div className="md:col-span-2 space-y-4">
                <div className="bg-card border-2 border-border rounded-2xl p-3 sm:p-4 space-y-4 shadow-sm">
                  <div>
                    <h3 className="text-lg font-bold text-foreground mb-6">Upload Document</h3>
                    <input
                      ref={fileInputResidenceRef}
                      type="file"
                      accept="image/*"
                      onChange={(e) => handleFileInputChange(e, "residence")}
                      className="hidden"
                    />
                    <div
                      className={`border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition-all cursor-pointer ${
                        dragActiveResidence
                          ? "border-accent bg-accent/5 scale-[1.01]"
                          : "border-accent/20 hover:border-accent/40 hover:bg-accent/5"
                      }`}
                      onDragEnter={(e) => handleDrag(e, "residence")}
                      onDragLeave={(e) => handleDrag(e, "residence")}
                      onDragOver={(e) => handleDrag(e, "residence")}
                      onDrop={(e) => handleDrop(e, "residence")}
                      onClick={() => handleBrowseClick("residence")}
                    >
                      {residenceFile ? (
                        <div className="flex items-center gap-3 bg-accent/5 p-2 rounded-xl border border-accent/20 text-left animate-in fade-in zoom-in duration-300">
                          <div className="relative h-16 w-24 sm:h-20 sm:w-32 shrink-0">
                            <img
                              src={URL.createObjectURL(residenceFile)}
                              alt="Residence Proof"
                              className="h-full w-full object-cover rounded-lg shadow-md"
                            />
                            <div className="absolute inset-0 rounded-lg border border-accent/20 pointer-events-none" />
                          </div>
                          <div className="flex-1 min-w-0 space-y-0.5">
                            <p className="text-xs font-black text-foreground truncate">{residenceFile.name}</p>
                            <p className="text-[10px] text-muted-foreground font-bold">
                              {(residenceFile.size / 1024 / 1024).toFixed(2)} MB • Ready
                            </p>
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                setResidenceFile(null);
                              }}
                              className="text-[10px] text-destructive font-black hover:bg-destructive/10 px-2 py-1 rounded transition-colors flex items-center gap-1 mt-1"
                            >
                              <X className="w-3 h-3" />
                              Remove
                            </button>
                          </div>
                        </div>
                      ) : (
                        <div className="space-y-4 sm:space-y-5">
                          <div className="w-12 h-12 sm:w-16 sm:h-16 bg-accent/10 rounded-2xl flex items-center justify-center mx-auto">
                            <Upload className="w-6 h-6 sm:w-8 sm:h-8 text-accent" />
                          </div>
                          <div>
                            <p className="text-base font-black text-foreground">Click to upload or drag</p>
                            <p className="text-[10px] text-foreground font-bold mt-0.5">
                              JPG or PNG (max. 10MB)
                            </p>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>

                </div>
              </div>

              {/* Right Column - Info Panels */}
              <div className="space-y-3">
                <div className="bg-card border-2 border-border rounded-2xl p-4 shadow-sm">
                  <div className="flex items-center gap-2 mb-3">
                    <div className="bg-accent p-1 rounded-lg">
                       <Check className="w-3.5 h-3.5 text-slate-900" />
                    </div>
                    <h4 className="text-sm font-black text-foreground">Accepted Documents</h4>
                  </div>
                  <ul className="space-y-1.5">
                    {[
                      "Utility Bill (Gas, Water, Electric)",
                      "Bank or Credit Card Statement",
                      "Tax Assessment",
                      "Lease Agreement"
                    ].map((item, i) => (
                      <li key={i} className="flex items-center gap-2">
                        <div className="w-1 h-1 rounded-full bg-accent flex-shrink-0" />
                        <span className="text-xs text-foreground font-bold">{item}</span>
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="bg-orange-500/10 border-2 border-orange-500/20 rounded-2xl p-4 shadow-sm">
                  <div className="flex items-center gap-2 mb-2 text-orange-600 dark:text-orange-400">
                    <Info className="w-4 h-4" />
                    <h4 className="text-sm font-black">Note</h4>
                  </div>
                  <p className="text-xs text-orange-700 dark:text-orange-300 font-black leading-tight">
                    Doc must be within 3 months and show full name & address.
                  </p>
                </div>

                <div className="relative rounded-2xl overflow-hidden border-2 border-border aspect-[21/9] group shadow-sm bg-muted/10">
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent z-10" />
                  <img 
                    src="https://images.unsplash.com/photo-1554224155-1696413565d3?q=80&w=500&auto=format&fit=crop" 
                    alt="Sample Document"
                    className="w-full h-full object-cover grayscale brightness-75 transition-all duration-700 group-hover:scale-105"
                  />
                  <div className="absolute bottom-2.5 left-4 right-4 z-20">
                    <p className="text-[10px] font-black text-white leading-tight">
                       Make sure all 4 corners are visible.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t-2 border-border">
              <Button 
                variant="outline"
                onClick={() => setCurrentStep(0)}
                className="bg-card border-2 border-border text-foreground font-black text-xs hover:bg-muted hover:border-foreground transition-all order-2 sm:order-1 px-5 py-2.5 rounded-xl h-auto"
              >
                Back to ID
              </Button>
              <div className="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto order-1 sm:order-2">
                <Button
                  onClick={() => setCurrentStep(0)}
                  variant="outline"
                  className="bg-muted border-2 border-border text-foreground hover:bg-muted/80 py-2.5 px-5 h-auto text-xs font-black rounded-xl w-full sm:w-auto"
                >
                  Back
                </Button>
                <Button
                  onClick={handleContinue}
                  disabled={!residenceFile || isUploading}
                  className={`py-2.5 px-8 h-auto text-xs font-black rounded-xl shadow-lg transition-all w-full sm:w-auto ${
                    residenceFile && !isUploading
                      ? "bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
                      : "bg-muted text-muted-foreground cursor-not-allowed opacity-60"
                  }`}
                >
                  {isUploading ? (
                    <span className="flex items-center gap-2">
                      <Loader className="w-3.5 h-3.5 animate-spin" />
                      Uploading...
                    </span>
                  ) : "Submit"}
                </Button>
              </div>
            </div>
          </div>
        ) : (
          // Step 0: ID Verification View
          <>
            <div className="mb-4">
              <h2 className="text-lg font-black text-foreground mb-0">
                Verify identity
              </h2>
              <p className="text-xs text-foreground font-bold leading-relaxed max-w-2xl">
                Please provide a valid government ID.
              </p>
            </div>

            <div className="mb-8">
              <p className="text-[10px] font-black text-foreground mb-2 uppercase tracking-wider bg-accent/10 py-1 px-3 rounded-full inline-block">
                ID Type
              </p>
              <div className="flex flex-col sm:flex-row gap-3">
                {idTypes.map((idType) => (
                  <button
                    key={idType.id}
                    onClick={() => setSelectedIdType(idType.id)}
                    className={`flex-1 p-3.5 sm:p-4 border-2 rounded-2xl transition-all text-left relative overflow-hidden group ${
                      selectedIdType === idType.id
                        ? "border-accent bg-accent/5"
                        : "border-border bg-card hover:border-accent/30"
                    }`}
                  >
                    <div className="flex items-center justify-between mb-3">
                      <div className={`p-1.5 rounded-lg transition-colors ${selectedIdType === idType.id ? 'bg-accent text-primary-foreground' : 'bg-muted text-muted-foreground'}`}>
                        <IdCard className="w-5 h-5" />
                      </div>
                      {selectedIdType === idType.id ? (
                        <div className="w-5 h-5 bg-accent rounded-full flex items-center justify-center shadow-md">
                          <Check className="w-3 h-3 text-primary-foreground" />
                        </div>
                      ) : (
                        <div className="w-5 h-5 border-2 border-border rounded-full transition-colors group-hover:border-accent/40"></div>
                      )}
                    </div>
                    <p className="text-sm font-black text-foreground">
                      {idType.label}
                    </p>
                    <p className="text-xs text-foreground font-bold">
                      {idType.description}
                    </p>
                  </button>
                ))}
              </div>
            </div>

            <div className="mb-6">
              <p className="text-[10px] font-black text-foreground mb-2 uppercase tracking-wider bg-accent/10 py-1 px-3 rounded-full inline-block">
                Upload
              </p>
              
              <input
                ref={fileInputFrontRef}
                type="file"
                accept="image/*"
                onChange={(e) => handleFileInputChange(e, "front")}
                className="hidden"
              />
              <input
                ref={fileInputBackRef}
                type="file"
                accept="image/*"
                onChange={(e) => handleFileInputChange(e, "back")}
                className="hidden"
              />

              <div className={`grid ${selectedIdType === "passport" ? "grid-cols-1 max-w-xl mx-auto" : "grid-cols-1 sm:grid-cols-2"} gap-4 sm:gap-6`}>
                <div
                  className={`border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition-all cursor-pointer relative overflow-hidden group ${
                    dragActiveFront
                      ? "border-accent bg-accent/5 scale-[1.02]"
                      : "border-accent/20 hover:border-accent/40 hover:bg-accent/5"
                  }`}
                  onDragEnter={(e) => handleDrag(e, "front")}
                  onDragLeave={(e) => handleDrag(e, "front")}
                  onDragOver={(e) => handleDrag(e, "front")}
                  onDrop={(e) => handleDrop(e, "front")}
                  onClick={() => handleBrowseClick("front")}
                >
                  {frontFile ? (
                    <div className="flex items-center gap-3 bg-accent/5 p-2 rounded-xl border border-accent/20 text-left animate-in fade-in zoom-in duration-300">
                      <div className="relative h-16 w-24 sm:h-20 sm:w-32 shrink-0">
                        <img
                          src={URL.createObjectURL(frontFile)}
                          alt="Front"
                          className="h-full w-full object-cover rounded-lg shadow-md"
                        />
                        <div className="absolute inset-0 rounded-lg border border-accent/20 pointer-events-none" />
                      </div>
                      <div className="flex-1 min-w-0 space-y-0.5">
                        <p className="text-xs font-black text-foreground truncate">{frontFile.name}</p>
                        <p className="text-[10px] text-muted-foreground font-bold">Front Side • Ready</p>
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            setFrontFile(null);
                          }}
                          className="text-[10px] text-destructive font-black hover:bg-destructive/10 px-2 py-1 rounded transition-colors flex items-center gap-1 mt-1"
                        >
                          <X className="w-3 h-3" />
                          Remove
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="space-y-3 py-2">
                      <div className="w-9 h-9 sm:w-10 sm:h-10 bg-accent/10 rounded-xl flex items-center justify-center mx-auto transition-transform group-hover:translate-y-[-2px]">
                        <LucideImage className="w-5 h-5 text-accent" />
                      </div>
                      <div>
                        <p className="text-sm sm:text-base font-black text-foreground">
                          {selectedIdType === "passport" ? "Passport Page" : "ID Front"}
                        </p>
                        <p className="text-[9px] text-foreground font-bold mb-2 px-2">
                          Drag and drop or take photo.
                        </p>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-2 px-2">
                        <Button
                          onClick={(e) => { e.stopPropagation(); handleBrowseClick("front"); }}
                          className="flex-1 bg-accent text-slate-900 hover:bg-accent/90 border-none text-[9px] font-black py-2 rounded-lg transition-all w-full shadow-sm"
                        >
                          <Upload className="w-3 h-3 mr-1" />
                          Browse
                        </Button>
                        <Button
                          onClick={(e) => { e.stopPropagation(); setShowWebcamFront(true); }}
                          variant="outline"
                          className="flex-1 border-2 border-border hover:border-accent hover:bg-accent/5 text-foreground text-[9px] font-black py-2 rounded-lg w-full"
                        >
                          <Camera className="w-3 h-3 mr-1" />
                          Snap
                        </Button>
                      </div>
                    </div>
                  )}
                </div>

                {selectedIdType !== "passport" && (
                  <div
                    className={`border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition-all cursor-pointer relative overflow-hidden group ${
                      dragActiveBack
                        ? "border-accent bg-accent/5 scale-[1.02]"
                        : "border-accent/20 hover:border-accent/40 hover:bg-accent/5"
                    }`}
                    onDragEnter={(e) => handleDrag(e, "back")}
                    onDragLeave={(e) => handleDrag(e, "back")}
                    onDragOver={(e) => handleDrag(e, "back")}
                    onDrop={(e) => handleDrop(e, "back")}
                    onClick={() => handleBrowseClick("back")}
                  >
                    {backFile ? (
                      <div className="flex items-center gap-3 bg-accent/5 p-2 rounded-xl border border-accent/20 text-left animate-in fade-in zoom-in duration-300">
                        <div className="relative h-16 w-24 sm:h-20 sm:w-32 shrink-0">
                          <img
                            src={URL.createObjectURL(backFile)}
                            alt="Back"
                            className="h-full w-full object-cover rounded-lg shadow-md"
                          />
                          <div className="absolute inset-0 rounded-lg border border-accent/20 pointer-events-none" />
                        </div>
                        <div className="flex-1 min-w-0 space-y-0.5">
                          <p className="text-xs font-black text-foreground truncate">{backFile.name}</p>
                          <p className="text-[10px] text-muted-foreground font-bold">Back Side • Ready</p>
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              setBackFile(null);
                            }}
                            className="text-[10px] text-destructive font-black hover:bg-destructive/10 px-2 py-1 rounded transition-colors flex items-center gap-1 mt-1"
                          >
                            <X className="w-3 h-3" />
                            Remove
                          </button>
                        </div>
                      </div>
                    ) : (
                    <div className="space-y-3 py-2">
                      <div className="w-9 h-9 sm:w-10 sm:h-10 bg-accent/10 rounded-xl flex items-center justify-center mx-auto transition-transform group-hover:translate-y-[-2px]">
                        <LucideImage className="w-5 h-5 text-accent" />
                      </div>
                      <div>
                        <p className="text-sm sm:text-base font-black text-foreground">
                          ID Back
                        </p>
                        <p className="text-[9px] text-foreground font-bold mb-2 px-2">
                          Drag and drop or take photo.
                        </p>
                      </div>
                      <div className="flex flex-col sm:flex-row gap-2 px-2">
                        <Button
                          onClick={(e) => { e.stopPropagation(); handleBrowseClick("back"); }}
                          className="flex-1 bg-accent text-slate-900 hover:bg-accent/90 border-none text-[9px] font-black py-2 rounded-lg transition-all w-full shadow-sm"
                        >
                          <Upload className="w-3 h-3 mr-1" />
                          Browse
                        </Button>
                        <Button
                          onClick={(e) => { e.stopPropagation(); setShowWebcamBack(true); }}
                          variant="outline"
                          className="flex-1 border-2 border-border hover:border-accent hover:bg-accent/5 text-foreground text-[9px] font-black py-2 rounded-lg w-full"
                        >
                          <Camera className="w-3 h-3 mr-1" />
                          Snap
                        </Button>
                      </div>
                    </div>
                    )}
                  </div>
                )}
              </div>
            </div>



            <div className="bg-emerald-500/10 border-2 border-emerald-500/30 rounded-2xl p-4 mb-4 flex gap-4 items-center">
              <div className="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
              </div>
              <div className="flex-1">
                <p className="text-xs font-black text-emerald-600 uppercase tracking-wide">
                  Your data is protected
                </p>
                <p className="text-xs text-foreground font-bold mt-0.5 leading-relaxed">
                  We use institutional-grade encryption to secure your documents.
                </p>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center justify-center gap-2 pt-3 border-t-2 border-border">
              <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <Link to="/" className="w-full sm:w-auto">
                  <Button
                    variant="outline"
                    className="bg-card border-2 border-border text-foreground hover:bg-muted py-2 px-5 h-auto text-xs font-black rounded-xl w-full"
                  >
                    Cancel
                  </Button>
                </Link>
                <Button
                  onClick={handleContinue}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-8 h-auto text-xs font-black rounded-xl shadow-md transition-all hover:scale-[1.02] w-full sm:w-auto"
                >
                  Continue
                </Button>
              </div>
            </div>
          </>
        )}
        
        {showWebcamFront && (
          <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-background rounded-2xl max-w-lg w-full p-5 sm:p-8 space-y-6 shadow-2xl border border-border">
              <div className="flex items-center justify-between">
                <h3 className="text-2xl font-black text-foreground">
                  Capture ID Front
                </h3>
                <button
                  onClick={() => setShowWebcamFront(false)}
                  className="p-2 hover:bg-muted rounded-full transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
              <div className="rounded-2xl overflow-hidden border-2 border-accent/20 bg-muted aspect-video flex items-center justify-center relative">
                <Webcam
                  ref={webcamFrontRef}
                  screenshotFormat="image/jpeg"
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="flex gap-4">
                <Button
                  onClick={() => setShowWebcamFront(false)}
                  size="lg"
                  className="flex-1 bg-muted text-foreground hover:bg-muted/80 h-auto py-4 rounded-xl"
                >
                  Cancel
                </Button>
                <Button
                  onClick={() => captureWebcam("front")}
                  size="lg"
                  className="flex-1 bg-accent text-primary-foreground hover:bg-accent/90 h-auto py-4 rounded-xl shadow-lg shadow-accent/20"
                >
                  Capture Photo
                </Button>
              </div>
            </div>
          </div>
        )}

        {showWebcamBack && (
          <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-background rounded-2xl max-w-lg w-full p-5 sm:p-8 space-y-6 shadow-2xl border border-border">
              <div className="flex items-center justify-between">
                <h3 className="text-2xl font-black text-foreground">
                   Capture ID Back
                </h3>
                <button
                  onClick={() => setShowWebcamBack(false)}
                  className="p-2 hover:bg-muted rounded-full transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
              <div className="rounded-2xl overflow-hidden border-2 border-accent/20 bg-muted aspect-video flex items-center justify-center relative">
                <Webcam
                  ref={webcamBackRef}
                  screenshotFormat="image/jpeg"
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="flex gap-4">
                <Button
                  onClick={() => setShowWebcamBack(false)}
                  size="lg"
                  className="flex-1 bg-muted text-foreground hover:bg-muted/80 h-auto py-4 rounded-xl"
                >
                  Cancel
                </Button>
                <Button
                  onClick={() => captureWebcam("back")}
                  size="lg"
                  className="flex-1 bg-accent text-primary-foreground hover:bg-accent/90 h-auto py-4 rounded-xl shadow-lg shadow-accent/20"
                >
                  Capture Photo
                </Button>
              </div>
            </div>
          </div>
        )}

      </div>
    </main>
  );
}

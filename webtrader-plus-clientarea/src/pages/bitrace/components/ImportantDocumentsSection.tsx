import { useRef } from "react"
import { CheckCircle2, FileText, Upload } from "lucide-react"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"
import { BitraceDocument } from "@/services/bitrace/bitrace-types"
import { useUploadBitraceDocumentMutation } from "@/services/bitrace/bitrace-queries"
import { getDocumentLabel } from "../utils/bitrace-mappers"

type ImportantDocumentsSectionProps = {
  documents?: BitraceDocument[]
  caseId?: string
}

const ImportantDocumentsSection = ({
  documents = [],
  caseId,
}: ImportantDocumentsSectionProps) => {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const uploadMutation = useUploadBitraceDocumentMutation(caseId ?? "")

  const handleUploadClick = () => {
    if (!caseId) return
    fileInputRef.current?.click()
  }

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (!file || !caseId) return

    try {
      await uploadMutation.mutateAsync({ file, name: file.name })
      toast.success("Document uploaded successfully")
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to upload document")
    } finally {
      event.target.value = ""
    }
  }

  if (documents.length === 0 && !caseId) {
    return null
  }

  return (
    <section>
      <div className="flex items-center justify-between gap-4 mb-4">
        <h2 className="text-[#0A1628] text-[17px] font-bold">
          Important Documents
        </h2>
        {caseId && (
          <>
            <input
              ref={fileInputRef}
              type="file"
              accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
              className="hidden"
              onChange={handleFileChange}
              aria-label="Upload document"
            />
            <button
              type="button"
              onClick={handleUploadClick}
              disabled={uploadMutation.isPending}
              className="inline-flex items-center gap-2 px-3 py-2 text-[#2563EB] text-[13px] font-semibold border border-[#BFDBFE] rounded-lg hover:bg-[#F0F7FF] disabled:opacity-60"
            >
              <Upload className="w-4 h-4" />
              {uploadMutation.isPending ? "Uploading..." : "Upload"}
            </button>
          </>
        )}
      </div>

      {documents.length === 0 ? (
        <div className="bg-white rounded-xl border border-dashed border-[#E2E8F0] p-8 text-center">
          <FileText className="w-10 h-10 text-[#94A3B8] mx-auto mb-3" />
          <p className="text-[#6B7A8D] text-[13px]">
            No documents yet. Upload supporting files for your case.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          {documents.map((doc, index) => {
            const { title, subtitle, verified } = getDocumentLabel(doc, index)
            const href = doc.file_path

            return (
              <div
                key={doc.id ?? `${title}-${index}`}
                className="bg-white rounded-xl p-5 shadow-[0_1px_4px_rgba(0,0,0,0.06)] border border-[#EEF1F5]"
              >
                <div className="w-10 h-10 rounded-lg bg-[#F4F6F9] flex items-center justify-center mb-4">
                  <FileText className="w-[18px] h-[18px] text-[#6B7A8D]" />
                </div>
                {href ? (
                  <a
                    href={href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-[#2563EB] text-[13px] font-bold mb-0.5 hover:underline block"
                  >
                    {title}
                  </a>
                ) : (
                  <h3 className="text-[#0A1628] text-[13px] font-bold mb-0.5">
                    {title}
                  </h3>
                )}
                <p className="text-[#94A3B8] text-[12px] mb-3">{subtitle}</p>
                {verified && (
                  <div className="flex items-center gap-1.5">
                    <CheckCircle2 className="w-4 h-4 text-[#22C55E]" />
                    <span className="text-[#22C55E] text-[12px] font-medium">
                      Uploaded
                    </span>
                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}
    </section>
  )
}

export default ImportantDocumentsSection

import { useRef } from "react"
import { CheckCircle2, FileText, Upload } from "lucide-react"
import { toast } from "@/components/ui/sonner"
import { AxiosError } from "axios"
import { useBitraceCaseQuery, useUploadBitraceDocumentMutation } from "@/services/bitrace/bitrace-queries"
import { getDocumentLabel } from "../../utils/bitrace-mappers"

type Props = {
  caseId: string
  isWithdrawn?: boolean
}

const CaseDocumentsTab = ({ caseId, isWithdrawn = false }: Props) => {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const { data: caseData, isLoading, isError, refetch } = useBitraceCaseQuery(caseId)
  const uploadMutation = useUploadBitraceDocumentMutation(caseId)
  const documents = caseData?.documents ?? []

  const handleUploadClick = () => {
    if (isWithdrawn) return
    fileInputRef.current?.click()
  }

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (!file) return

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

  if (isLoading) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 animate-pulse h-[240px]" />
    )
  }

  if (isError) {
    return (
      <div className="bg-white rounded-xl border border-[#EEF1F5] p-8 text-center">
        <p className="text-[#6B7A8D] text-[14px] mb-4">Unable to load documents.</p>
        <button
          type="button"
          onClick={() => refetch()}
          className="text-[#2563EB] text-[13px] font-semibold hover:underline"
        >
          Try again
        </button>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-xl border border-[#EEF1F5] p-6">
      <div className="flex items-center justify-between gap-4 mb-6">
        <h2 className="text-[#0A1628] text-[15px] font-bold">Case Documents</h2>
        {!isWithdrawn && (
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
              className="inline-flex items-center gap-2 px-4 py-2 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg transition-colors"
            >
              <Upload className="w-4 h-4" />
              {uploadMutation.isPending ? "Uploading..." : "Upload document"}
            </button>
          </>
        )}
      </div>

      {documents.length === 0 ? (
        <div className="text-center py-10 border border-dashed border-[#E2E8F0] rounded-xl">
          <FileText className="w-10 h-10 text-[#94A3B8] mx-auto mb-3" />
          <p className="text-[#6B7A8D] text-[14px] mb-1">No documents uploaded yet</p>
          <p className="text-[#94A3B8] text-[12px]">
            Upload supporting documents such as bank statements or wallet screenshots.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {documents.map((doc, index) => {
            const { title, subtitle, verified } = getDocumentLabel(doc, index)
            const href = doc.file_path

            return (
              <div
                key={doc.id ?? `${title}-${index}`}
                className="bg-[#F8FAFC] border border-[#EEF1F5] rounded-xl p-5"
              >
                <div className="w-10 h-10 rounded-lg bg-white flex items-center justify-center mb-4 border border-[#EEF1F5]">
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
                  <h3 className="text-[#0A1628] text-[13px] font-bold mb-0.5">{title}</h3>
                )}
                <p className="text-[#94A3B8] text-[12px] mb-3">{subtitle}</p>
                {verified && (
                  <div className="flex items-center gap-1.5">
                    <CheckCircle2 className="w-4 h-4 text-[#22C55E]" />
                    <span className="text-[#22C55E] text-[12px] font-medium">Uploaded</span>
                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}

export default CaseDocumentsTab

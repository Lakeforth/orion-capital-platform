import { useState } from "react"
import { useNavigate } from "react-router-dom"
import { X } from "lucide-react"
import { AxiosError } from "axios"
import { toast } from "@/components/ui/sonner"
import { useCreateRecoveryCaseMutation } from "@/services/recovery/recovery-queries"
import { getBitraceCaseDetailPath } from "../bitrace-routes"

type Props = {
  open: boolean
  onClose: () => void
}

const CreateCaseModal = ({ open, onClose }: Props) => {
  const navigate = useNavigate()
  const mutation = useCreateRecoveryCaseMutation()
  const [title, setTitle] = useState("")
  const [notes, setNotes] = useState("")

  if (!open) return null

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    const trimmedTitle = title.trim()
    if (!trimmedTitle) {
      toast.error("Please enter a case title")
      return
    }

    try {
      const newCase = await mutation.mutateAsync({
        title: trimmedTitle,
        notes: notes.trim() || undefined,
      })
      toast.success("Your new case has been opened")
      setTitle("")
      setNotes("")
      onClose()
      navigate(getBitraceCaseDetailPath(newCase.id))
    } catch (err) {
      const e = err as AxiosError<{ message?: string }>
      toast.error(e.response?.data?.message || "Failed to create case")
    }
  }

  const handleClose = () => {
    if (mutation.isPending) return
    onClose()
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
      role="dialog"
      aria-modal="true"
      aria-labelledby="create-case-title"
    >
      <div className="bg-white rounded-xl border border-[#EEF1F5] shadow-xl w-full max-w-md p-6 relative">
        <button
          type="button"
          onClick={handleClose}
          className="absolute top-4 right-4 text-[#94A3B8] hover:text-[#6B7A8D]"
          aria-label="Close"
        >
          <X className="w-5 h-5" />
        </button>

        <h2 id="create-case-title" className="text-[#0A1628] text-[17px] font-bold mb-1">
          Open a new case
        </h2>
        <p className="text-[#6B7A8D] text-[13px] mb-5">
          Describe what happened and our team will review your case and activate the next steps.
        </p>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="case-title" className="block text-[#94A3B8] text-[11px] font-semibold uppercase tracking-wide mb-1.5">
              Case title
            </label>
            <input
              id="case-title"
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g. Unauthorized wire transfer"
              className="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg text-[13px] text-[#0A1628] placeholder:text-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB]"
              maxLength={255}
            />
          </div>

          <div>
            <label htmlFor="case-notes" className="block text-[#94A3B8] text-[11px] font-semibold uppercase tracking-wide mb-1.5">
              Notes (optional)
            </label>
            <textarea
              id="case-notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Brief description of what happened..."
              rows={3}
              className="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg text-[13px] text-[#0A1628] placeholder:text-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] resize-none"
            />
          </div>

          <div className="flex gap-3 pt-2">
            <button
              type="submit"
              disabled={mutation.isPending}
              className="flex-1 px-4 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg transition-colors"
            >
              {mutation.isPending ? "Creating..." : "Create case"}
            </button>
            <button
              type="button"
              onClick={handleClose}
              disabled={mutation.isPending}
              className="px-4 py-2.5 text-[#6B7A8D] text-[13px] font-medium hover:underline"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default CreateCaseModal

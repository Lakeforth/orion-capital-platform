import { useBitraceCasesQuery } from "@/services/bitrace/bitrace-queries"
import { useBitraceEnabled } from "../useBitraceEnabled"

export const useBitracePrimaryCase = () => {
  const bitraceEnabled = useBitraceEnabled()
  const { data: cases = [], isLoading } = useBitraceCasesQuery(bitraceEnabled)

  return {
    primaryCase: cases[0] ?? null,
    cases,
    isLoading,
  }
}

import { create } from "zustand"

type BitraceSupportWidgetState = {
  isOpen: boolean
  isMinimized: boolean
  open: () => void
  close: () => void
  toggle: () => void
  minimize: () => void
  restore: () => void
}

const useBitraceSupportWidgetStore = create<BitraceSupportWidgetState>((set) => ({
  isOpen: false,
  isMinimized: false,
  open: () => set({ isOpen: true, isMinimized: false }),
  close: () => set({ isOpen: false, isMinimized: false }),
  toggle: () =>
    set((state) => ({
      isOpen: !state.isOpen,
      isMinimized: false,
    })),
  minimize: () => set({ isMinimized: true }),
  restore: () => set({ isMinimized: false }),
}))

export const openBitraceSupportChat = () => {
  useBitraceSupportWidgetStore.getState().open()
}

export default useBitraceSupportWidgetStore

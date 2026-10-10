import useDarkModeStore from "@/store/darkModeStore";

export function StatusBar() {
  const isDarkMode = useDarkModeStore((state) => state.isDarkMode);

  return (
    <div
      className={`${
        isDarkMode
          ? "bg-slate-800 border-slate-600 text-slate-200"
          : "bg-slate-200 border-slate-400 text-slate-800"
      } border-t px-4 py-1 flex items-center justify-between text-xs`}
    >
      <div className="flex items-center gap-4">
        <span className="font-medium">Default</span>
      </div>
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-1">
          <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
          <span>📶</span>
        </div>
        <span className="font-medium">230 / 15MB</span>
      </div>
    </div>
  );
}

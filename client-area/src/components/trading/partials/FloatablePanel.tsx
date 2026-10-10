import { useEffect, useRef, useState, type PointerEvent as ReactPointerEvent, type ReactNode } from "react";
import { GripHorizontal, Maximize2, MoveDiagonal2, PanelTopClose } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface FloatablePanelProps {
  children: ReactNode;
  className?: string;
  floatingClassName?: string;
  label: string;
  initialPosition: { x: number; y: number };
  resizable?: boolean;
  onFloatingChange?: (floating: boolean) => void;
}

export default function FloatablePanel({
  children,
  className,
  floatingClassName,
  label,
  initialPosition,
  resizable = false,
  onFloatingChange,
}: FloatablePanelProps) {
  const [floating, setFloating] = useState(false);
  const [position, setPosition] = useState(initialPosition);
  const [floatingSize, setFloatingSize] = useState<{ width: number; height: number } | null>(null);
  const dragOffset = useRef({ x: 0, y: 0 });
  const dragging = useRef(false);
  const resizing = useRef(false);
  const resizeStart = useRef({ x: 0, y: 0, width: 0, height: 0 });
  const panelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const move = (event: PointerEvent) => {
      if (dragging.current) {
        const maxX = Math.max(8, window.innerWidth - 280);
        const maxY = Math.max(8, window.innerHeight - 120);
        setPosition({
          x: Math.min(Math.max(8, event.clientX - dragOffset.current.x), maxX),
          y: Math.min(Math.max(8, event.clientY - dragOffset.current.y), maxY),
        });
      }
      if (resizing.current) {
        setFloatingSize({
          width: Math.min(
            Math.max(620, resizeStart.current.width + event.clientX - resizeStart.current.x),
            window.innerWidth - position.x - 8,
          ),
          height: Math.min(
            Math.max(240, resizeStart.current.height + event.clientY - resizeStart.current.y),
            window.innerHeight - position.y - 8,
          ),
        });
      }
    };
    const stop = () => {
      dragging.current = false;
      resizing.current = false;
    };
    window.addEventListener("pointermove", move);
    window.addEventListener("pointerup", stop);
    return () => {
      window.removeEventListener("pointermove", move);
      window.removeEventListener("pointerup", stop);
    };
  }, [position.x, position.y]);

  const startDrag = (event: ReactPointerEvent<HTMLDivElement>) => {
    if (!floating || event.button !== 0) return;
    dragging.current = true;
    dragOffset.current = {
      x: event.clientX - position.x,
      y: event.clientY - position.y,
    };
    event.currentTarget.setPointerCapture(event.pointerId);
  };

  const startResize = (event: ReactPointerEvent<HTMLButtonElement>) => {
    if (!floating || !resizable || !panelRef.current) return;
    event.preventDefault();
    event.stopPropagation();
    const bounds = panelRef.current.getBoundingClientRect();
    resizing.current = true;
    resizeStart.current = {
      x: event.clientX,
      y: event.clientY,
      width: bounds.width,
      height: bounds.height,
    };
    event.currentTarget.setPointerCapture(event.pointerId);
  };

  const toggleFloating = () => {
    setFloating((value) => {
      if (value) setFloatingSize(null);
      const nextValue = !value;
      onFloatingChange?.(nextValue);
      return nextValue;
    });
  };

  return (
    <div
      ref={panelRef}
      className={cn(
        "relative",
        className,
        floating && "fixed z-[60] overflow-auto rounded-md border border-border bg-background shadow-2xl",
        floating && floatingClassName,
      )}
      style={floating ? {
        left: position.x,
        top: position.y,
        width: floatingSize?.width,
        height: floatingSize?.height,
      } : undefined}
    >
      <div
        className={cn(
          "absolute right-1 top-1 z-[70] flex items-center gap-1",
          floating && "left-0 right-0 top-0 h-8 cursor-move justify-between border-b border-border bg-background/95 px-2",
        )}
        onPointerDown={startDrag}
        title={floating ? `Drag ${label}` : undefined}
      >
        {floating && <GripHorizontal className="h-4 w-4 text-muted-foreground" />}
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="h-7 w-7 bg-background/80"
          aria-label={floating ? `Dock ${label}` : `Float ${label}`}
          title={floating ? `Dock ${label}` : `Float ${label}`}
          onPointerDown={(event) => event.stopPropagation()}
          onClick={toggleFloating}
        >
          {floating ? <PanelTopClose className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
        </Button>
      </div>
      <div className={cn(floating && "h-full pt-8", floating && resizable && "pb-9")}>
        {children}
      </div>
      {floating && resizable && (
        <button
          type="button"
          className="absolute bottom-0 right-0 z-[80] flex h-8 w-8 cursor-nwse-resize items-end justify-end rounded-tl bg-primary/20 p-1 text-primary hover:bg-primary/30"
          aria-label={`Resize ${label}`}
          title={`Drag to resize ${label}`}
          onPointerDown={startResize}
        >
          <MoveDiagonal2 className="h-4 w-4" />
        </button>
      )}
    </div>
  );
}

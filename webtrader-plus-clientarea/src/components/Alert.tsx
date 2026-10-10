import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { AlertCircle, CheckCircle, AlertTriangle, Info, X } from "lucide-react"

import { cn } from "@/lib/utils"

const alertVariants = cva(
    "relative w-full rounded-lg border p-4 shadow-md [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4",
    {
        variants: {
            variant: {
                default: "bg-muted/80 border-border text-foreground",
                destructive:
                    "bg-destructive/90 border-destructive text-destructive-foreground dark:bg-destructive/80",
                success:
                    "bg-green-500/90 border-green-600 text-white dark:bg-green-500/80",
                warning:
                    "bg-yellow-500/90 border-yellow-600 text-white dark:bg-yellow-500/80",
            },
        },
        defaultVariants: {
            variant: "default",
        },
    }
)

const Alert = React.forwardRef<
    HTMLDivElement,
    React.HTMLAttributes<HTMLDivElement> & VariantProps<typeof alertVariants>
>(({ className, variant, ...props }, ref) => (
    <div
        ref={ref}
        role="alert"
        className={cn(alertVariants({ variant }), className)}
        {...props}
    />
))
Alert.displayName = "Alert"

const AlertTitle = React.forwardRef<
    HTMLParagraphElement,
    React.HTMLAttributes<HTMLHeadingElement>
>(({ className, ...props }, ref) => (
    <h5
        ref={ref}
        className={cn("mb-1 font-medium leading-none tracking-tight", className)}
        {...props}
    />
))
AlertTitle.displayName = "AlertTitle"

const AlertDescription = React.forwardRef<
    HTMLParagraphElement,
    React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn("text-sm [&_p]:leading-relaxed", className)}
        {...props}
    />
))
AlertDescription.displayName = "AlertDescription"

// Create an Alert with icon component that automatically includes the appropriate icon
interface AlertWithIconProps extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof alertVariants> {
    title?: string;
    description?: React.ReactNode;
    onClose?: () => void;
}

const AlertWithIcon = React.forwardRef<HTMLDivElement, AlertWithIconProps>(
    ({ className, variant, title, description, onClose, ...props }, ref) => {
        const IconComponent = React.useMemo(() => {
            switch(variant) {
                case 'destructive': return AlertCircle;
                case 'success': return CheckCircle;
                case 'warning': return AlertTriangle;
                default: return Info;
            }
        }, [variant]);

        return (
            <Alert
                ref={ref}
                variant={variant}
                className={cn("pr-10", className)}
                {...props}
            >
                <IconComponent className="h-5 w-5" />
                {title && <AlertTitle>{title}</AlertTitle>}
                {description && <AlertDescription>{description}</AlertDescription>}
                {onClose && (
                    <button
                        onClick={onClose}
                        className="absolute top-3 right-3 rounded-full p-1 text-foreground/80 opacity-70 transition-opacity hover:opacity-100 cursor-pointer"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                )}
            </Alert>
        );
    }
);
AlertWithIcon.displayName = "AlertWithIcon";

export { Alert, AlertTitle, AlertDescription, AlertWithIcon }

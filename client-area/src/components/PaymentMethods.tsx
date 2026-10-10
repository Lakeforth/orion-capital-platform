import { useLocation, Link } from "react-router-dom";

interface PaymentMethod {
  id: string;
  name: string;
  icon: React.ReactNode;
  processingTime: string;
  path: string;
}

interface PaymentMethodsProps {
  paymentMethods: PaymentMethod[];
}

const PaymentMethods: React.FC<PaymentMethodsProps> = ({ paymentMethods }) => {
  const location = useLocation();

  // Function to check if a method is active
  const isMethodActive = (path: string) => {
    const currentPath = location.pathname;

    // For main categories
    return currentPath === path;
  };

  return (
    <div className="relative ">
      <h3 className="hidden md:block text-sm uppercase tracking-wider text-muted-foreground mb-2 px-2">
        METHODS
      </h3>

      <div className="hidden md:block space-y-1">
        {paymentMethods.map((method) => (
          <Link
            key={method.id}
            to={method.path}
            className={`flex items-center gap-3 py-3 px-4 w-full border-l-4 border-transparent hover:bg-secondary/30 transition-all duration-200 rounded-sm ${
              isMethodActive(method.path)
                ? "border-l-4 border-accent bg-secondary/40"
                : ""
            }`}
          >
            <span className="text-muted-foreground w-7">{method.icon}</span>
            <div className="flex-1 text-left">
              <p className="text-sm font-medium">{method.name}</p>
              <p className="text-xs text-muted-foreground">
                {method.processingTime}
              </p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default PaymentMethods;

import React from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import {
  LayoutDashboard,
  User,
  ArrowUpDown,
  FileCheck,
  Folders,
  MessageSquare,
  PiggyBank,
  Settings,
  LogOut,
} from "lucide-react";
import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";
import useUserStore from "@/store/userStore";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "./ui/button";
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog";
import AutoTraderModal from "./trading/trading-interface-components/auto-trader-modal";
import { clearAuthenticatedSession } from "@/lib/session";

interface SidebarProps {
  onLinkClick: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ onLinkClick }) => {
  const location = useLocation();
  const navigate = useNavigate();
  const user = useUserStore((state) => state.user);

  const handleLogout = () => {
    clearAuthenticatedSession();
    navigate("/main/deposit");
  };

  const navItems = [
    { title: "Dashboard", icon: LayoutDashboard, path: "/main/dashboard" },
    { title: "Personal Information", icon: User, path: "/main/personal" },
    { title: "Withdrawal", icon: ArrowUpDown, path: "/main/withdrawal" },
    { title: "KYC", icon: FileCheck, path: "/main/verification" },
    { title: "Accounts", icon: Folders, path: "/main/accounts" },
    { title: "Live Chat", icon: MessageSquare, path: "/main/chat" },
    { title: "Savings", icon: PiggyBank, path: "/main/savings" },
    { title: "Settings", icon: Settings, path: "/main/settings" },
  ];

  // Function to check if a nav item is active
  const isActive = (path: string) => {
    if (path === "/") {
      return location.pathname === "/";
    }
    return location.pathname.startsWith(path);
  };

  return (
    <aside className="bg-trading-dark h-full w-60 flex flex-col border-r border-trading-darker">
      <div className="flex flex-col items-center text-center p-6 border-b border-trading-darker">
        {" "}
        <Dialog>
          <DialogTrigger asChild>
            <Avatar className="h-20 w-20 relative overflow-hidden cursor-pointer">
              <AvatarImage
                src={user?.avatar}
                alt={`${user?.first_name} ${user?.last_name}`}
                className="object-cover w-full h-full"
                style={{
                  objectPosition: "center",
                }}
              />
              <AvatarFallback className="bg-sidebar-accent text-2xl font-semibold absolute inset-0 flex items-center justify-center">
                {user
                  ? `${user.first_name[0]}${user.last_name[0]}`.toUpperCase()
                  : ""}
              </AvatarFallback>
            </Avatar>
          </DialogTrigger>
          <DialogContent className="w-full h-full flex items-center justify-center">
            <img
              src={user?.avatar}
              className="w-full h-full object-cover p-4"
              alt="Profile"
            />
          </DialogContent>
        </Dialog>
        <h2 className="text-lg font-medium text-trading-light">
          {user ? `${user.first_name} ${user.last_name}` : ""}
        </h2>
        <p className="text-sm text-trading-muted mt-1">
          #{user?.account_id || ""}
        </p>
        <p className="text-xs text-trading-muted/70 mt-1">
          {user?.email || ""}
        </p>
        <div className="grid grid-cols-1 gap-2 w-full mt-4">
          <Link to="/main/deposit">
            <Button onClick={onLinkClick}>Deposit</Button>
          </Link>
          {/* <Button variant="ghost" className="border border-border">
            Autotrader
          </Button> */}
          <div className="border border-primary/50 rounded-md flex justify-center">
            <AutoTraderModal />
          </div>
        </div>
      </div>
      <nav className="flex-1 py-4 overflow-y-auto">
        <ScrollArea>
          <ul className="space-y-1 px-2">
            {navItems.map((item) => (
              <li key={item.title}>
                {" "}
                <Link
                  to={item.path}
                  className={`flex items-center gap-3 px-4 py-3 rounded-md text-trading-light/80 hover:text-trading-light hover:bg-trading-accent/20 transition-all duration-200 group text-sm ${
                    isActive(item.path)
                      ? "bg-trading-accent/30 text-trading-light"
                      : ""
                  }`}
                  onClick={onLinkClick}
                >
                  <item.icon className="h-5 w-5 opacity-70 group-hover:opacity-100 transition-opacity" />
                  <span>{item.title}</span>
                </Link>
              </li>
            ))}
          </ul>
          <ScrollBar orientation="vertical" />
        </ScrollArea>
      </nav>{" "}
      <div className="p-4 border-t border-trading-darker">
        <button
          onClick={handleLogout}
          className="flex items-center gap-3 px-4 py-3 rounded-md text-trading-light/80 hover:text-trading-light hover:bg-trading-accent/20 transition-all duration-200 w-full justify-start group text-sm"
        >
          <LogOut className="h-5 w-5 opacity-70 group-hover:opacity-100 transition-opacity" />
          <span>Log Out</span>
        </button>
      </div>
    </aside>
  );
};

export default Sidebar;

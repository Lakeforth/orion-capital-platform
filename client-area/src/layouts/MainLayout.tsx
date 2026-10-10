import { Outlet } from "react-router-dom";
import Header from "@/components/Header";
import Sidebar from "@/components/Sidebar";
import { useState, useRef, useEffect } from "react";

const MainLayout = () => {
  const [isSidebarVisible, setSidebarVisible] = useState(false);
  const sidebarRef = useRef<HTMLDivElement>(null);

  const toggleSidebar = () => {
    setSidebarVisible(!isSidebarVisible);
  };

  const handleLinkClick = () => {
    setSidebarVisible(false);
  };

  // Scopes the client area's table and field standard (index.css) to these
  // pages, including the dialogs they open, which render outside this tree.
  useEffect(() => {
    document.body.classList.add("client-area");
    return () => document.body.classList.remove("client-area");
  }, []);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        isSidebarVisible &&
        sidebarRef.current &&
        !sidebarRef.current.contains(event.target as Node)
      ) {
        setSidebarVisible(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [isSidebarVisible]);

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <div className="fixed top-0 left-0 right-0 z-20">
        <Header toggleSidebar={toggleSidebar} />
      </div>

      <div className="flex flex-1">
        <div
            ref={sidebarRef}
            className={`fixed top-16 left-0 bottom-0 w-[260px] z-30 transition-transform transform ${
                isSidebarVisible ? "translate-x-0" : "-translate-x-full"
            } md:translate-x-0`}
        >
          <Sidebar onLinkClick={handleLinkClick} />
        </div>

        <main className="flex-1 md:ml-[260px] pt-[60px] overflow-y-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default MainLayout;

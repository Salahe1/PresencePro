import { useEffect, useState } from "react";
import { Outlet } from "react-router-dom";
import { Sidebar } from "../Sidebar";
import { Header } from "../Header";
import "./LayoutPrincipale.css";

const SIDEBAR_STORAGE_KEY = "sidebar_collapsed";

export function LayoutPrincipale() {
  const [isCollapsed, setIsCollapsed] = useState(
    () => localStorage.getItem(SIDEBAR_STORAGE_KEY) === "true",
  );

  useEffect(() => {
    localStorage.setItem(SIDEBAR_STORAGE_KEY, String(isCollapsed));
  }, [isCollapsed]);

  function toggleSidebar() {
    setIsCollapsed((current) => !current);
  }

  return (
    <div className={`layout${isCollapsed ? " layout--sidebar-collapsed" : ""}`}>
      {!isCollapsed && (
        <button
          type="button"
          className="layout__backdrop"
          aria-label="Fermer le menu"
          onClick={toggleSidebar}
        />
      )}

      <Sidebar collapsed={isCollapsed} onToggle={toggleSidebar} />

      <div className="layout__main">
        <Header
          isSidebarCollapsed={isCollapsed}
          onToggleSidebar={toggleSidebar}
        />

        <main className="layout__content">
          <Outlet />
        </main>
      </div>
    </div>
  );
}

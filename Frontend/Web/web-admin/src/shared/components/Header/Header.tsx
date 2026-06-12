import { useNavigate } from "react-router-dom";
import "./Header.css";

type HeaderProps = {
  isSidebarCollapsed: boolean;
  onToggleSidebar: () => void;
};

export function Header({ isSidebarCollapsed, onToggleSidebar }: HeaderProps) {
  const navigate = useNavigate();

  function handleLogout() {
    localStorage.removeItem("access_token");
    navigate("/login");
  }

  return (
    <header className="header">
      <div className="header__leading">
        <button
          type="button"
          className="header__menu-toggle"
          onClick={onToggleSidebar}
          aria-label={isSidebarCollapsed ? "Ouvrir le menu" : "Réduire le menu"}
          aria-expanded={!isSidebarCollapsed}
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
          </svg>
        </button>

        <div className="header__info">
          <p className="header__eyebrow">Espace administrateur</p>
          <h1 className="header__title">Tableau de bord</h1>
        </div>
      </div>

      <div className="header__actions">
        <div className="header__user">
          <span className="header__avatar" aria-hidden="true">
            AD
          </span>
          <div className="header__user-text">
            <span className="header__user-name">Administrateur</span>
            <span className="header__user-role">Gestion RH</span>
          </div>
        </div>

        <button type="button" className="header__logout" onClick={handleLogout}>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M16 17l5-5-5-5M21 12H9" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
          Déconnexion
        </button>
      </div>
    </header>
  );
}

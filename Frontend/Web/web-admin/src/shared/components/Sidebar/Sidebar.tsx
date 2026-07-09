import { NavLink } from "react-router-dom";
import "./Sidebar.css";

const navItems = [
  {
    to: "/dashboard",
    label: "Tableau de bord",
    end: true,
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
        <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    ),
  },
  {
    to: "/employes",
    label: "Employés",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" strokeLinecap="round" strokeLinejoin="round" />
        <circle cx="9" cy="7" r="4" />
        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    ),
  },
  {
    to: "/departements",
    label: "Départements",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
        <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M16 3h-1a4 4 0 0 0-4 4v1M16 3a4 4 0 0 1 4 4v1M16 3a4 4 0 0 0-4-4h-1a4 4 0 0 0-4 4v1M5.5 21h14a2.5 2.5 0 0 1-2.5-2.5v-1.5a2.5 2.5 0 0 1 2.5-2.5h-14a2.5 2.5 0 0 1-2.5 2.5V19a2.5 2.5 0 0 1 2.5 2.5Z" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    ),
  },
];

type SidebarProps = {
  collapsed: boolean;
  onToggle: () => void;
};

export function Sidebar({ collapsed /* , onToggle  */}: SidebarProps) {
  return (
    <aside className={`sidebar${collapsed ? " sidebar--collapsed" : ""}`}>
      <div className="sidebar__brand">
        <div className="sidebar__logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" strokeLinecap="round" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        </div>

        <div className="sidebar__brand-text">
          <span className="sidebar__brand-name">PresencePro</span>
          <span className="sidebar__brand-tagline">Administration RH</span>
        </div>
      </div>

      <nav className="sidebar__nav" aria-label="Navigation principale">
        <p className="sidebar__section-label">Menu principal</p>

        <ul className="sidebar__list">
          {navItems.map((item) => (
            <li key={item.to}>
              <NavLink
                to={item.to}
                end={item.end}
                title={collapsed ? item.label : undefined}
                className={({ isActive }) =>
                  `sidebar__link${isActive ? " sidebar__link--active" : ""}`
                }
              >
                {item.icon}
                <span className="sidebar__link-label">{item.label}</span>
              </NavLink>
            </li>
          ))}
        </ul>
      </nav>

      <div className="sidebar__footer">
        <p className="sidebar__footer-note">
          Tableau de bord interne — accès réservé aux administrateurs.
        </p>

        {/* <button
          type="button"
          className="sidebar__toggle"
          onClick={onToggle}
          aria-label={collapsed ? "Développer le menu" : "Réduire le menu"}
          aria-expanded={!collapsed}
        > 
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            {collapsed ? (
              <path d="M9 18l6-6-6-6" strokeLinecap="round" strokeLinejoin="round" />
            ) : (
              <path d="M15 18l-6-6 6-6" strokeLinecap="round" strokeLinejoin="round" />
            )}
          </svg>
          <span className="sidebar__toggle-label">
            {collapsed ? "Développer" : "Réduire"}
          </span>
        </button>*/}
      </div>
    </aside>
  );
}

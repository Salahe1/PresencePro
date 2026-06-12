import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { getEmployes } from "../../employes/api";
import "./DashboardPage.css";

export function DashboardPage() {
  const { data: employes, isLoading } = useQuery({
    queryKey: ["employes"],
    queryFn: getEmployes,
  });

  const totalEmployes = employes?.length ?? 0;
  const employesActifs = employes?.filter((employe) => employe.actif).length ?? 0;

  return (
    <div className="dashboard-page">
      <section className="dashboard-page__header">
        <div>
          <p className="dashboard-page__eyebrow">Vue d&apos;ensemble</p>
          <h1 className="dashboard-page__title">Tableau de bord</h1>
          <p className="dashboard-page__description">
            Bienvenue sur PresencePro. Consultez un aperçu rapide de votre organisation
            et accédez aux sections clés de l&apos;administration RH.
          </p>
        </div>
      </section>

      <section className="dashboard-page__stats" aria-label="Indicateurs clés">
        <article className="dashboard-page__stat-card flex-container">
          <p className="dashboard-page__stat-label">Total employés</p>
          <strong className="dashboard-page__stat-value">
            {isLoading ? "—" : totalEmployes}
          </strong>
          <span className="dashboard-page__stat-hint">Effectif enregistré</span>
        </article>

        <article className="dashboard-page__stat-card">
          <p className="dashboard-page__stat-label">Employés actifs</p>
          <strong className="dashboard-page__stat-value dashboard-page__stat-value--success">
            {isLoading ? "—" : employesActifs}
          </strong>
          <span className="dashboard-page__stat-hint">Comptes actuellement actifs</span>
        </article>

        <article className="dashboard-page__stat-card">
          <p className="dashboard-page__stat-label">Employés inactifs</p>
          <strong className="dashboard-page__stat-value dashboard-page__stat-value--muted">
            {isLoading ? "—" : totalEmployes - employesActifs}
          </strong>
          <span className="dashboard-page__stat-hint">Hors effectif actif</span>
        </article>
      </section>

      <section className="dashboard-page__panel">
        <div className="dashboard-page__panel-header">
          <h2 className="dashboard-page__panel-title">Accès rapide</h2>
          <p className="dashboard-page__panel-description">
            Poursuivez votre gestion RH depuis les sections principales.
          </p>
        </div>

        <div className="dashboard-page__actions">
          <Link to="/employes" className="dashboard-page__action-card">
            <span className="dashboard-page__action-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" strokeLinecap="round" strokeLinejoin="round" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            <span className="dashboard-page__action-content">
              <strong>Gérer les employés</strong>
              <small>Consulter la liste complète et les statuts</small>
            </span>
          </Link>
        </div>
      </section>
    </div>
  );
}

import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { getAlertes } from "../../alertes/api";
import { getAbsencesByDate } from "../../absences/api";
import { getDepartements } from "../../departements/api";
import { getEmployes } from "../../employes/api";
import { getHorairesTravail } from "../../horaires-travail/api";
import { getPointagesByDate } from "../../pointages/api";
import { getRetardsByDate } from "../../retards/api";
import { formatFrenchDate, formatTime, getTodayIsoDate } from "../../../shared/utils/date";
import "./DashboardPage.css";

const today = getTodayIsoDate();

function formatPointageType(type: string | null): string {
  if (type === "entre") {
    return "Entrée";
  }

  if (type === "sortie") {
    return "Sortie";
  }

  return "Pointage";
}

function formatAlerteType(type: string | null): string {
  if (type === "retard") {
    return "Retard";
  }

  if (type === "absence") {
    return "Absence";
  }

  if (type === "anomalie") {
    return "Anomalie";
  }

  return "Alerte";
}

function formatAbsenceStatut(statut: string): string {
  return statut === "justifie" ? "Justifiée" : "Non justifiée";
}

function DashboardSkeleton() {
  return (
    <div className="dashboard-page" aria-busy="true" aria-label="Chargement du tableau de bord">
      <div className="dashboard-page__skeleton dashboard-page__skeleton--eyebrow" />
      <div className="dashboard-page__skeleton dashboard-page__skeleton--title" />
      <div className="dashboard-page__skeleton dashboard-page__skeleton--description" />

      <div className="dashboard-page__stats">
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={index} className="dashboard-page__skeleton dashboard-page__skeleton--stat" />
        ))}
      </div>

      <div className="dashboard-page__grid">
        <div className="dashboard-page__skeleton dashboard-page__skeleton--panel" />
        <div className="dashboard-page__skeleton dashboard-page__skeleton--panel" />
      </div>
    </div>
  );
}

export function DashboardPage() {
  const employesQuery = useQuery({ queryKey: ["employes"], queryFn: getEmployes });
  const departementsQuery = useQuery({ queryKey: ["departements"], queryFn: getDepartements });
  const horairesQuery = useQuery({ queryKey: ["horaires-travail"], queryFn: getHorairesTravail });
  const alertesQuery = useQuery({ queryKey: ["alertes"], queryFn: getAlertes });
  const pointagesQuery = useQuery({
    queryKey: ["pointages", today],
    queryFn: () => getPointagesByDate(today),
  });
  const retardsQuery = useQuery({
    queryKey: ["retards", today],
    queryFn: () => getRetardsByDate(today),
  });
  const absencesQuery = useQuery({
    queryKey: ["absences", today],
    queryFn: () => getAbsencesByDate(today),
  });

  const isLoading =
    employesQuery.isLoading ||
    departementsQuery.isLoading ||
    horairesQuery.isLoading ||
    alertesQuery.isLoading ||
    pointagesQuery.isLoading ||
    retardsQuery.isLoading ||
    absencesQuery.isLoading;

  const employes = employesQuery.data ?? [];
  const departements = departementsQuery.data ?? [];
  const horairesTravail = horairesQuery.data ?? [];
  const alertes = alertesQuery.data ?? [];
  const pointages = pointagesQuery.data ?? [];
  const retards = retardsQuery.data ?? [];
  const absences = absencesQuery.data ?? [];

  const totalEmployes = employes.length;
  const employesActifs = employes.filter((employe) => employe.actif).length;
  const employesInactifs = totalEmployes - employesActifs;
  const unreadAlertes = alertes.filter((alerte) => alerte.statut === "non_lu");

  const departmentBreakdown = useMemo(() => {
    const counts = new Map<string, number>();

    for (const employe of employes) {
      const label = employe.departement?.label ?? "Sans département";
      counts.set(label, (counts.get(label) ?? 0) + 1);
    }

    return Array.from(counts.entries())
      .map(([label, count]) => ({ label, count }))
      .sort((left, right) => right.count - left.count);
  }, [employes]);

  const maxDepartmentCount = departmentBreakdown[0]?.count ?? 0;

  const recentPointages = useMemo(
    () =>
      [...pointages]
        .sort((left, right) => right.timeStamp.localeCompare(left.timeStamp))
        .slice(0, 6),
    [pointages]
  );

  const recentRetards = useMemo(
    () =>
      [...retards]
        .sort((left, right) => right.dureeRetardMinutes - left.dureeRetardMinutes)
        .slice(0, 5),
    [retards]
  );

  const recentAbsences = useMemo(() => absences.slice(0, 5), [absences]);

  const recentAlertes = useMemo(
    () => alertes.filter((alerte) => alerte.statut === "non_lu").slice(0, 5),
    [alertes]
  );

  const unassignedEmployees = employes.filter((employe) => !employe.departement).length;

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const hasActivityErrors =
    pointagesQuery.isError || retardsQuery.isError || absencesQuery.isError || alertesQuery.isError;

  return (
    <div className="dashboard-page">
      <section className="dashboard-page__header">
        <div>
          <p className="dashboard-page__eyebrow">Vue d&apos;ensemble</p>
          <h1 className="dashboard-page__title">Tableau de bord</h1>
          <p className="dashboard-page__description">
            Suivez l&apos;activité RH du jour, la répartition des équipes et les alertes à traiter.
          </p>
        </div>

        <div className="dashboard-page__today-card">
          <span className="dashboard-page__today-label">Aujourd&apos;hui</span>
          <strong className="dashboard-page__today-date">{formatFrenchDate(today)}</strong>
        </div>
      </section>

      <section className="dashboard-page__stats" aria-label="Indicateurs organisationnels">
        <article className="dashboard-page__stat-card">
          <span className="dashboard-page__stat-icon dashboard-page__stat-icon--primary" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" strokeLinecap="round" strokeLinejoin="round" />
              <circle cx="9" cy="7" r="4" />
            </svg>
          </span>
          <p className="dashboard-page__stat-label">Total employés</p>
          <strong className="dashboard-page__stat-value">{totalEmployes}</strong>
          <span className="dashboard-page__stat-hint">Effectif enregistré</span>
        </article>

        <article className="dashboard-page__stat-card">
          <span className="dashboard-page__stat-icon dashboard-page__stat-icon--success" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path d="M20 6 9 17l-5-5" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
          <p className="dashboard-page__stat-label">Employés actifs</p>
          <strong className="dashboard-page__stat-value dashboard-page__stat-value--success">
            {employesActifs}
          </strong>
          <span className="dashboard-page__stat-hint">{employesInactifs} inactif{employesInactifs > 1 ? "s" : ""}</span>
        </article>

        <article className="dashboard-page__stat-card">
          <span className="dashboard-page__stat-icon dashboard-page__stat-icon--violet" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M16 3h-1a4 4 0 0 0-4 4v1M16 3a4 4 0 0 1 4 4v1" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
          <p className="dashboard-page__stat-label">Départements</p>
          <strong className="dashboard-page__stat-value">{departements.length}</strong>
          <span className="dashboard-page__stat-hint">
            {unassignedEmployees > 0
              ? `${unassignedEmployees} employé${unassignedEmployees > 1 ? "s" : ""} sans département`
              : "Tous les employés sont assignés"}
          </span>
        </article>

        <article className="dashboard-page__stat-card">
          <span className="dashboard-page__stat-icon dashboard-page__stat-icon--warning" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path d="M12 9v4M12 17h.01" strokeLinecap="round" />
              <path d="M10.3 3.6 2.4 17.2A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.8L13.7 3.6a2 2 0 0 0-3.4 0Z" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
          <p className="dashboard-page__stat-label">Alertes non lues</p>
          <strong className="dashboard-page__stat-value dashboard-page__stat-value--warning">
            {unreadAlertes.length}
          </strong>
          <span className="dashboard-page__stat-hint">{horairesTravail.length} horaires configurés</span>
        </article>
      </section>

      <section className="dashboard-page__activity" aria-label="Activité du jour">
        <div className="dashboard-page__activity-header">
          <div>
            <h2 className="dashboard-page__panel-title">Activité du jour</h2>
            <p className="dashboard-page__panel-description">
              Pointages, retards et absences enregistrés pour aujourd&apos;hui.
            </p>
          </div>
        </div>

        {hasActivityErrors && (
          <p className="dashboard-page__inline-error" role="alert">
            Certaines données du jour n&apos;ont pas pu être chargées. Les indicateurs affichés peuvent être incomplets.
          </p>
        )}

        <div className="dashboard-page__activity-stats">
          <article className="dashboard-page__activity-stat">
            <strong>{pointages.length}</strong>
            <span>Pointages</span>
          </article>
          <article className="dashboard-page__activity-stat dashboard-page__activity-stat--warning">
            <strong>{retards.length}</strong>
            <span>Retards</span>
          </article>
          <article className="dashboard-page__activity-stat dashboard-page__activity-stat--danger">
            <strong>{absences.length}</strong>
            <span>Absences</span>
          </article>
        </div>
      </section>

      <div className="dashboard-page__grid">
        <section className="dashboard-page__panel">
          <div className="dashboard-page__panel-header">
            <h2 className="dashboard-page__panel-title">Derniers pointages</h2>
            <p className="dashboard-page__panel-description">Les entrées et sorties les plus récentes.</p>
          </div>

          {recentPointages.length > 0 ? (
            <ul className="dashboard-page__feed">
              {recentPointages.map((pointage) => (
                <li key={pointage.id} className="dashboard-page__feed-item">
                  <span
                    className={`dashboard-page__feed-badge ${
                      pointage.type === "sortie"
                        ? "dashboard-page__feed-badge--muted"
                        : "dashboard-page__feed-badge--success"
                    }`}
                  >
                    {formatPointageType(pointage.type)}
                  </span>
                  <div className="dashboard-page__feed-copy">
                    <strong>{pointage.employe}</strong>
                    <small>{formatTime(pointage.timeStamp.split(" ")[1] ?? pointage.timeStamp)}</small>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="dashboard-page__empty">Aucun pointage enregistré aujourd&apos;hui.</p>
          )}
        </section>

        <section className="dashboard-page__panel">
          <div className="dashboard-page__panel-header">
            <h2 className="dashboard-page__panel-title">Retards et absences</h2>
            <p className="dashboard-page__panel-description">Événements à surveiller pour la journée.</p>
          </div>

          {recentRetards.length === 0 && recentAbsences.length === 0 ? (
            <p className="dashboard-page__empty">Aucun retard ni absence signalé aujourd&apos;hui.</p>
          ) : (
            <ul className="dashboard-page__feed">
              {recentRetards.map((retard) => (
                <li key={`retard-${retard.id}`} className="dashboard-page__feed-item">
                  <span className="dashboard-page__feed-badge dashboard-page__feed-badge--warning">
                    Retard
                  </span>
                  <div className="dashboard-page__feed-copy">
                    <strong>{retard.employeNom}</strong>
                    <small>
                      {retard.dureeRetardMinutes} min · prévu {formatTime(retard.heurePrevue)} · arrivée{" "}
                      {formatTime(retard.heureArrivee)}
                    </small>
                  </div>
                </li>
              ))}

              {recentAbsences.map((absence) => (
                <li key={`absence-${absence.id}`} className="dashboard-page__feed-item">
                  <span className="dashboard-page__feed-badge dashboard-page__feed-badge--danger">
                    Absence
                  </span>
                  <div className="dashboard-page__feed-copy">
                    <strong>{absence.employe?.nomComplet ?? "Employé inconnu"}</strong>
                    <small>{formatAbsenceStatut(absence.statut)}</small>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>

      <div className="dashboard-page__grid">
        <section className="dashboard-page__panel">
          <div className="dashboard-page__panel-header">
            <h2 className="dashboard-page__panel-title">Alertes à traiter</h2>
            <p className="dashboard-page__panel-description">
              {unreadAlertes.length > 0
                ? `${unreadAlertes.length} alerte${unreadAlertes.length > 1 ? "s" : ""} en attente de lecture.`
                : "Toutes les alertes ont été lues."}
            </p>
          </div>

          {recentAlertes.length > 0 ? (
            <ul className="dashboard-page__feed">
              {recentAlertes.map((alerte) => (
                <li key={alerte.id} className="dashboard-page__feed-item dashboard-page__feed-item--alert">
                  <span className="dashboard-page__feed-badge dashboard-page__feed-badge--alert">
                    {formatAlerteType(alerte.type)}
                  </span>
                  <div className="dashboard-page__feed-copy">
                    <strong>{alerte.employe?.nomComplet ?? "Employé inconnu"}</strong>
                    <small>{alerte.message ?? "Nouvelle alerte à consulter"}</small>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="dashboard-page__empty">Aucune alerte non lue pour le moment.</p>
          )}
        </section>

        <section className="dashboard-page__panel">
          <div className="dashboard-page__panel-header">
            <h2 className="dashboard-page__panel-title">Répartition par département</h2>
            <p className="dashboard-page__panel-description">Effectifs assignés par service.</p>
          </div>

          {departmentBreakdown.length > 0 ? (
            <ul className="dashboard-page__breakdown">
              {departmentBreakdown.map((item) => (
                <li key={item.label} className="dashboard-page__breakdown-item">
                  <div className="dashboard-page__breakdown-top">
                    <span>{item.label}</span>
                    <strong>{item.count}</strong>
                  </div>
                  <div className="dashboard-page__breakdown-bar" aria-hidden="true">
                    <span
                      style={{
                        width: `${maxDepartmentCount > 0 ? (item.count / maxDepartmentCount) * 100 : 0}%`,
                      }}
                    />
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="dashboard-page__empty">Aucun employé enregistré pour le moment.</p>
          )}
        </section>
      </div>

      <section className="dashboard-page__panel">
        <div className="dashboard-page__panel-header">
          <h2 className="dashboard-page__panel-title">Accès rapide</h2>
          <p className="dashboard-page__panel-description">
            Accédez aux sections principales de l&apos;administration RH.
          </p>
        </div>

        <div className="dashboard-page__actions">
          <Link to="/employes" className="dashboard-page__action-card">
            <span className="dashboard-page__action-icon dashboard-page__action-icon--blue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" strokeLinecap="round" strokeLinejoin="round" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            <span className="dashboard-page__action-content">
              <strong>Gérer les employés</strong>
              <small>{totalEmployes} employés · {employesActifs} actifs</small>
            </span>
          </Link>

          <Link to="/departements" className="dashboard-page__action-card">
            <span className="dashboard-page__action-icon dashboard-page__action-icon--violet" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M16 3h-1a4 4 0 0 0-4 4v1M16 3a4 4 0 0 1 4 4v1" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            <span className="dashboard-page__action-content">
              <strong>Gérer les départements</strong>
              <small>{departements.length} départements · {horairesTravail.length} horaires</small>
            </span>
          </Link>
        </div>
      </section>
    </div>
  );
}

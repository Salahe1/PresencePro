import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { DataTable, type DataTableColumn } from "../../../shared/components/DataTable";
import { SelectInput, TextInput } from "../../../shared/components/Form";
import type { Retard } from "../../../shared/types/retard";
import { getRetardsByDate } from "../api";
import "./RetardsPage.css";

type JustificationFilter = "all" | "justified" | "unjustified";

const justificationFilterOptions = [
  { value: "all", label: "Tous les retards" },
  { value: "justified", label: "Justifies" },
  { value: "unjustified", label: "Non justifies" },
];

function getTodayIsoDate(): string {
  return new Date().toISOString().slice(0, 10);
}

function formatDate(dateIso: string): string {
  const date = new Date(`${dateIso}T00:00:00`);

  return new Intl.DateTimeFormat("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(date);
}

function formatTime(time: string): string {
  return time.slice(0, 5);
}

function formatDuration(minutes: number): string {
  if (minutes < 60) {
    return `${minutes} min`;
  }

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  return remainingMinutes > 0 ? `${hours} h ${remainingMinutes} min` : `${hours} h`;
}

function getInitials(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}

function RetardsPageSkeleton() {
  return (
    <section className="retards-page" aria-busy="true" aria-label="Chargement des retards">
      <header className="retards-page__header">
        <div className="retards-page__header-copy">
          <div className="retards-page__skeleton retards-page__skeleton--eyebrow" />
          <div className="retards-page__skeleton retards-page__skeleton--title" />
          <div className="retards-page__skeleton retards-page__skeleton--description" />
        </div>
      </header>

      <div className="retards-page__stats">
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={index} className="retards-page__skeleton retards-page__skeleton--stat" />
        ))}
      </div>

      <div className="retards-page__skeleton retards-page__skeleton--panel" />
    </section>
  );
}

export function RetardsPage() {
  const [selectedDate, setSelectedDate] = useState(getTodayIsoDate);
  const [searchQuery, setSearchQuery] = useState("");
  const [justificationFilter, setJustificationFilter] = useState<JustificationFilter>("all");

  const {
    data: retards,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ["retards", selectedDate],
    queryFn: () => getRetardsByDate(selectedDate),
  });

  const filteredRetards = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    return (retards ?? []).filter((retard) => {
      const matchesSearch =
        !query ||
        retard.employeNom.toLowerCase().includes(query) ||
        retard.employeMatricule.toLowerCase().includes(query) ||
        (retard.commentaire ?? "").toLowerCase().includes(query);

      const matchesJustification =
        justificationFilter === "all" ||
        (justificationFilter === "justified" && retard.justifie) ||
        (justificationFilter === "unjustified" && !retard.justifie);

      return matchesSearch && matchesJustification;
    });
  }, [retards, searchQuery, justificationFilter]);

  const totalRetards = retards?.length ?? 0;
  const justifiedRetards = retards?.filter((retard) => retard.justifie).length ?? 0;
  const unjustifiedRetards = totalRetards - justifiedRetards;
  const totalDelayMinutes =
    retards?.reduce((total, retard) => total + retard.dureeRetardMinutes, 0) ?? 0;

  if (isLoading) {
    return <RetardsPageSkeleton />;
  }

  if (isError) {
    return (
      <section className="retards-page">
        <div className="retards-page__error-state" role="alert">
          <span className="retards-page__error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v5M12 16h.01" strokeLinecap="round" />
            </svg>
          </span>
          <h2>Impossible de charger les retards</h2>
          <p>Verifiez votre connexion puis reessayez dans quelques instants.</p>
        </div>
      </section>
    );
  }

  const retardsColumns: DataTableColumn<Retard>[] = [
    {
      key: "employe",
      header: "Employe",
      className: "retards-page__employee-cell",
      render: (retard) => (
        <div className="retards-page__employee">
          <span className="retards-page__avatar" aria-hidden="true">
            {getInitials(retard.employeNom)}
          </span>
          <span className="retards-page__employee-copy">
            <strong>{retard.employeNom}</strong>
            <small>{retard.employeMatricule}</small>
          </span>
        </div>
      ),
    },
    {
      key: "date",
      header: "Date",
      className: "retards-page__date-cell",
      render: (retard) => formatDate(retard.dateJour),
    },
    {
      key: "heures",
      header: "Horaires",
      render: (retard) => (
        <span className="retards-page__time-range">
          {formatTime(retard.heurePrevue)} {"->"} {formatTime(retard.heureArrivee)}
        </span>
      ),
    },
    {
      key: "duree",
      header: "Duree",
      render: (retard) => formatDuration(retard.dureeRetardMinutes),
    },
    {
      key: "justifie",
      header: "Justification",
      className: "retards-page__status-cell",
      render: (retard) => (
        <span
          className={`retards-page__status ${
            retard.justifie ? "retards-page__status--justified" : "retards-page__status--unjustified"
          }`}
        >
          {retard.justifie ? "Justifie" : "Non justifie"}
        </span>
      ),
    },
    {
      key: "commentaire",
      header: "Commentaire",
      render: (retard) => retard.commentaire ?? "Aucun commentaire",
    },
  ];

  return (
    <section className="retards-page">
      <header className="retards-page__header">
        <div className="retards-page__header-copy">
          <p className="retards-page__eyebrow">Presence</p>
          <h1 className="retards-page__title">Retards</h1>
          <p className="retards-page__description">
            Consultez les retards detectes par date, duree et statut de justification.
          </p>
        </div>
      </header>

      <div className="retards-page__stats" aria-label="Statistiques des retards">
        <article className="retards-page__stat-card">
          <p className="retards-page__stat-label">Total du jour</p>
          <strong className="retards-page__stat-value">{totalRetards}</strong>
          <span className="retards-page__stat-hint">{formatDate(selectedDate)}</span>
        </article>

        <article className="retards-page__stat-card">
          <p className="retards-page__stat-label">Non justifies</p>
          <strong className="retards-page__stat-value retards-page__stat-value--danger">
            {unjustifiedRetards}
          </strong>
          <span className="retards-page__stat-hint">A verifier</span>
        </article>

        <article className="retards-page__stat-card">
          <p className="retards-page__stat-label">Justifies</p>
          <strong className="retards-page__stat-value retards-page__stat-value--success">
            {justifiedRetards}
          </strong>
          <span className="retards-page__stat-hint">Avec motif</span>
        </article>

        <article className="retards-page__stat-card">
          <p className="retards-page__stat-label">Temps perdu</p>
          <strong className="retards-page__stat-value retards-page__stat-value--warning">
            {formatDuration(totalDelayMinutes)}
          </strong>
          <span className="retards-page__stat-hint">Cumul des retards</span>
        </article>
      </div>

      <div className="retards-page__list-panel">
        <div className="retards-page__list-header">
          <div>
            <h2 className="retards-page__panel-title">Liste des retards</h2>
            <p className="retards-page__panel-description">
              {filteredRetards.length} resultat{filteredRetards.length > 1 ? "s" : ""}
              {searchQuery.trim() || justificationFilter !== "all" ? " pour vos filtres" : " au total"}
            </p>
          </div>
        </div>

        <div className="retards-page__toolbar">
          <TextInput
            label="Date"
            name="selectedDate"
            type="date"
            value={selectedDate}
            onChange={(event) => setSelectedDate(event.target.value)}
          />

          <SelectInput
            label="Justification"
            name="justificationFilter"
            value={justificationFilter}
            onChange={(event) => setJustificationFilter(event.target.value as JustificationFilter)}
            options={justificationFilterOptions}
          />

          <label className="retards-page__search">
            <span className="retards-page__search-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" strokeLinecap="round" />
              </svg>
            </span>
            <input
              type="search"
              value={searchQuery}
              onChange={(event) => setSearchQuery(event.target.value)}
              placeholder="Rechercher par nom, matricule ou commentaire..."
              aria-label="Rechercher un retard"
            />
          </label>
        </div>

        <DataTable
          columns={retardsColumns}
          data={filteredRetards}
          getRowKey={(retard) => retard.id}
          emptyMessage={
            searchQuery.trim() || justificationFilter !== "all"
              ? "Aucun retard ne correspond a vos filtres."
              : "Aucun retard detecte pour cette date."
          }
        />
      </div>
    </section>
  );
}

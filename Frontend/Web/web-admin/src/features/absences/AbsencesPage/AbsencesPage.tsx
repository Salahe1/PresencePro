import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { DataTable, type DataTableColumn } from "../../../shared/components/DataTable";
import { SelectInput, TextInput } from "../../../shared/components/Form";
import type { Absence } from "../../../shared/types/absence";
import { getAbsencesByDate } from "../api";
import "./AbsencesPage.css";

type TypeFilter = "all" | "typed" | "unknown";

const typeFilterOptions = [
  { value: "all", label: "Tous les types" },
  { value: "typed", label: "Type renseigne" },
  { value: "unknown", label: "Type non renseigne" },
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

function formatTime(time: string | null): string {
  if (!time) {
    return "--:--";
  }

  return time.slice(0, 5);
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

function getAbsenceTypeLabel(absence: Absence): string {
  if (absence.typeAbsence?.trim()) {
    return absence.typeAbsence;
  }

  return "Absence automatique";
}

function AbsencesPageSkeleton() {
  return (
    <section className="absences-page" aria-busy="true" aria-label="Chargement des absences">
      <header className="absences-page__header">
        <div className="absences-page__header-copy">
          <div className="absences-page__skeleton absences-page__skeleton--eyebrow" />
          <div className="absences-page__skeleton absences-page__skeleton--title" />
          <div className="absences-page__skeleton absences-page__skeleton--description" />
        </div>
      </header>

      <div className="absences-page__stats">
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={index} className="absences-page__skeleton absences-page__skeleton--stat" />
        ))}
      </div>

      <div className="absences-page__skeleton absences-page__skeleton--panel" />
    </section>
  );
}

export function AbsencesPage() {
  const [selectedDate, setSelectedDate] = useState(getTodayIsoDate);
  const [searchQuery, setSearchQuery] = useState("");
  const [typeFilter, setTypeFilter] = useState<TypeFilter>("all");

  const {
    data: absences,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ["absences", selectedDate],
    queryFn: () => getAbsencesByDate(selectedDate),
  });

  const filteredAbsences = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    return (absences ?? []).filter((absence) => {
      const employeName = absence.employe?.nomComplet ?? "";
      const matricule = absence.employe?.matricule ?? "";
      const typeLabel = getAbsenceTypeLabel(absence);

      const matchesSearch =
        !query ||
        employeName.toLowerCase().includes(query) ||
        matricule.toLowerCase().includes(query) ||
        typeLabel.toLowerCase().includes(query);

      const matchesType =
        typeFilter === "all" ||
        (typeFilter === "typed" && Boolean(absence.typeAbsence?.trim())) ||
        (typeFilter === "unknown" && !absence.typeAbsence?.trim());

      return matchesSearch && matchesType;
    });
  }, [absences, searchQuery, typeFilter]);

  const totalAbsences = absences?.length ?? 0;
  const typedAbsences = absences?.filter((absence) => absence.typeAbsence?.trim()).length ?? 0;
  const untypedAbsences = totalAbsences - typedAbsences;
  const uniqueEmployeeCount = useMemo(() => {
    const employeeIds = new Set<number>();

    for (const absence of absences ?? []) {
      if (absence.employe?.id != null) {
        employeeIds.add(absence.employe.id);
      }
    }

    return employeeIds.size;
  }, [absences]);

  if (isLoading) {
    return <AbsencesPageSkeleton />;
  }

  if (isError) {
    return (
      <section className="absences-page">
        <div className="absences-page__error-state" role="alert">
          <span className="absences-page__error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v5M12 16h.01" strokeLinecap="round" />
            </svg>
          </span>
          <h2>Impossible de charger les absences</h2>
          <p>Verifiez votre connexion puis reessayez dans quelques instants.</p>
        </div>
      </section>
    );
  }

  const absencesColumns: DataTableColumn<Absence>[] = [
    {
      key: "employe",
      header: "Employe",
      className: "absences-page__employee-cell",
      render: (absence) => {
        const name = absence.employe?.nomComplet ?? "Employe inconnu";

        return (
          <div className="absences-page__employee">
            <span className="absences-page__avatar" aria-hidden="true">
              {getInitials(name)}
            </span>
            <span className="absences-page__employee-copy">
              <strong>{name}</strong>
              <small>{absence.employe?.matricule ?? "Sans matricule"}</small>
            </span>
          </div>
        );
      },
    },
    {
      key: "date",
      header: "Date",
      className: "absences-page__date-cell",
      render: (absence) => formatDate(absence.date),
    },
    {
      key: "type",
      header: "Type",
      render: (absence) => getAbsenceTypeLabel(absence),
    },
    {
      key: "plage",
      header: "Plage prevue",
      render: (absence) => {
        if (!absence.heureDebutPrevue && !absence.heureFinPrevue) {
          return <span className="absences-page__muted-text">Non renseignee</span>;
        }

        return (
          <span className="absences-page__time-range">
            {formatTime(absence.heureDebutPrevue)} - {formatTime(absence.heureFinPrevue)}
          </span>
        );
      },
    },
    {
      key: "statut",
      header: "Statut systeme",
      className: "absences-page__status-cell",
      render: (absence) => (
        <span className="absences-page__status">
          {absence.statut || "Automatique"}
        </span>
      ),
    },
  ];

  return (
    <section className="absences-page">
      <header className="absences-page__header">
        <div className="absences-page__header-copy">
          <p className="absences-page__eyebrow">Presence</p>
          <h1 className="absences-page__title">Absences</h1>
          <p className="absences-page__description">
            Suivez les absences detectees automatiquement par date, employe et plage horaire.
          </p>
        </div>
      </header>

      <div className="absences-page__stats" aria-label="Statistiques des absences">
        <article className="absences-page__stat-card">
          <p className="absences-page__stat-label">Total du jour</p>
          <strong className="absences-page__stat-value">{totalAbsences}</strong>
          <span className="absences-page__stat-hint">{formatDate(selectedDate)}</span>
        </article>

        <article className="absences-page__stat-card">
          <p className="absences-page__stat-label">Employes absents</p>
          <strong className="absences-page__stat-value absences-page__stat-value--warning">
            {uniqueEmployeeCount}
          </strong>
          <span className="absences-page__stat-hint">Personnes concernees</span>
        </article>

        <article className="absences-page__stat-card">
          <p className="absences-page__stat-label">Avec type</p>
          <strong className="absences-page__stat-value absences-page__stat-value--success">
            {typedAbsences}
          </strong>
          <span className="absences-page__stat-hint">Type renseigne</span>
        </article>

        <article className="absences-page__stat-card">
          <p className="absences-page__stat-label">Sans type</p>
          <strong className="absences-page__stat-value absences-page__stat-value--accent">
            {untypedAbsences}
          </strong>
          <span className="absences-page__stat-hint">Absences automatiques</span>
        </article>
      </div>

      <div className="absences-page__list-panel">
        <div className="absences-page__list-header">
          <div>
            <h2 className="absences-page__panel-title">Liste des absences automatiques</h2>
            <p className="absences-page__panel-description">
              {filteredAbsences.length} resultat{filteredAbsences.length > 1 ? "s" : ""}
              {searchQuery.trim() || typeFilter !== "all" ? " pour vos filtres" : " au total"}
            </p>
          </div>
        </div>

        <div className="absences-page__toolbar">
          <TextInput
            label="Date"
            name="selectedDate"
            type="date"
            value={selectedDate}
            onChange={(event) => setSelectedDate(event.target.value)}
          />

          <SelectInput
            label="Type"
            name="typeFilter"
            value={typeFilter}
            onChange={(event) => setTypeFilter(event.target.value as TypeFilter)}
            options={typeFilterOptions}
          />

          <label className="absences-page__search">
            <span className="absences-page__search-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" strokeLinecap="round" />
              </svg>
            </span>
            <input
              type="search"
              value={searchQuery}
              onChange={(event) => setSearchQuery(event.target.value)}
              placeholder="Rechercher par nom, matricule ou type..."
              aria-label="Rechercher une absence"
            />
          </label>
        </div>

        <DataTable
          columns={absencesColumns}
          data={filteredAbsences}
          getRowKey={(absence) => absence.id}
          emptyMessage={
            searchQuery.trim() || typeFilter !== "all"
              ? "Aucune absence ne correspond a vos filtres."
              : "Aucune absence detectee pour cette date."
          }
        />
      </div>
    </section>
  );
}

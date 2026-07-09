import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { DataTable, type DataTableColumn } from "../../../shared/components/DataTable";
import { FormPanel, SelectInput, SubmitButton, TextInput } from "../../../shared/components/Form";
import type { Departement } from "../../../shared/types/departement";
import type { HoraireTravail } from "../../../shared/types/horaireTravail";
import { getEmployes } from "../../employes/api";
import { getHorairesTravail } from "../../horaires-travail/api";
import { createDepartement, deleteDepartement, getDepartements, updateDepartement } from "../api";
import "./DepartementsPage.css";

function getInitials(label: string | null): string {
  if (!label?.trim()) {
    return "?";
  }

  return label
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0])
    .join("")
    .toUpperCase();
}

function formatPlagesHoraires(horaire: HoraireTravail | undefined): string {
  if (!horaire?.plagesHoraires.length) {
    return "Aucune plage définie";
  }

  return [...horaire.plagesHoraires]
    .sort((left, right) => left.ordre - right.ordre)
    .map((plage) => `${plage.heureDebut} - ${plage.heureFin}`)
    .join(" · ");
}

function formatToleranceRetard(tolerance: string | undefined): string {
  if (!tolerance) {
    return "—";
  }

  const [hours = "0", minutes = "0"] = tolerance.split(":");
  const totalMinutes = Number(hours) * 60 + Number(minutes);

  if (totalMinutes === 0) {
    return "Aucune tolérance";
  }

  return `${totalMinutes} min de tolérance`;
}

function HorairePreview({ horaire }: { horaire: HoraireTravail | undefined }) {
  if (!horaire) {
    return null;
  }

  return (
    <div className="departements-page__horaire-preview" aria-live="polite">
      <p className="departements-page__horaire-preview-title">
        {horaire.label ?? "Horaire sans nom"}
      </p>
      <p className="departements-page__horaire-preview-plages">
        {formatPlagesHoraires(horaire)}
      </p>
      <p className="departements-page__horaire-preview-meta">
        {formatToleranceRetard(horaire.toleranceRetard)}
      </p>
    </div>
  );
}

function DepartementsPageSkeleton() {
  return (
    <section className="departements-page" aria-busy="true" aria-label="Chargement des départements">
      <header className="departements-page__header">
        <div className="departements-page__header-copy">
          <div className="departements-page__skeleton departements-page__skeleton--eyebrow" />
          <div className="departements-page__skeleton departements-page__skeleton--title" />
          <div className="departements-page__skeleton departements-page__skeleton--description" />
        </div>
      </header>

      <div className="departements-page__stats">
        {Array.from({ length: 3 }).map((_, index) => (
          <div key={index} className="departements-page__stat-card departements-page__stat-card--loading">
            <div className="departements-page__skeleton departements-page__skeleton--stat-label" />
            <div className="departements-page__skeleton departements-page__skeleton--stat-value" />
          </div>
        ))}
      </div>

      <div className="departements-page__layout">
        <div className="departements-page__skeleton departements-page__skeleton--panel" />
        <div className="departements-page__skeleton departements-page__skeleton--panel departements-page__skeleton--panel-tall" />
      </div>
    </section>
  );
}

export function DepartementsPage() {
  const {
    data: departements,
    isLoading,
    isError,
  } = useQuery({ queryKey: ["departements"], queryFn: getDepartements });

  const {
    data: horairesTravail,
    isLoading: isHorairesLoading,
    isError: isHorairesError,
  } = useQuery({ queryKey: ["horaires-travail"], queryFn: getHorairesTravail });

  const { data: employes } = useQuery({ queryKey: ["employes"], queryFn: getEmployes });

  const [label, setLabel] = useState("");
  const [horaireTravailId, setHoraireTravailId] = useState("");
  const [searchQuery, setSearchQuery] = useState("");
  const [editingDepartement, setEditingDepartement] = useState<Departement | null>(null);
  const [editingLabel, setEditingLabel] = useState("");
  const [editingHoraireTravailId, setEditingHoraireTravailId] = useState("");
  const [deletingDepartementId, setDeletingDepartementId] = useState<number | null>(null);
  const queryClient = useQueryClient();

  const horairesById = useMemo(() => {
    const map = new Map<number, HoraireTravail>();

    for (const horaire of horairesTravail ?? []) {
      map.set(horaire.id, horaire);
    }

    return map;
  }, [horairesTravail]);

  const horaireOptions = useMemo(
    () =>
      (horairesTravail ?? []).map((horaire) => ({
        value: horaire.id,
        label: horaire.label ?? `Horaire #${horaire.id}`,
      })),
    [horairesTravail]
  );

  const selectedCreateHoraire = horaireTravailId
    ? horairesById.get(Number(horaireTravailId))
    : undefined;

  const selectedEditHoraire = editingHoraireTravailId
    ? horairesById.get(Number(editingHoraireTravailId))
    : undefined;

  const employeeCountByDepartment = useMemo(() => {
    const counts = new Map<number, number>();

    for (const employe of employes ?? []) {
      const departementId = employe.departement?.id;

      if (departementId == null) {
        continue;
      }

      counts.set(departementId, (counts.get(departementId) ?? 0) + 1);
    }

    return counts;
  }, [employes]);

  const filteredDepartements = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    if (!query) {
      return departements ?? [];
    }

    return (departements ?? []).filter((departement) => {
      const labelMatch = (departement.label ?? "").toLowerCase().includes(query);
      const horaireMatch = (departement.horaireTravail?.label ?? "")
        .toLowerCase()
        .includes(query);

      return labelMatch || horaireMatch;
    });
  }, [departements, searchQuery]);

  const assignedEmployeesCount = useMemo(() => {
    return Array.from(employeeCountByDepartment.values()).reduce((total, count) => total + count, 0);
  }, [employeeCountByDepartment]);

  const createMutation = useMutation({
    mutationFn: createDepartement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departements"] });
      setLabel("");
      setHoraireTravailId("");
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      id,
      label: nextLabel,
      horaireTravailId: nextHoraireTravailId,
    }: {
      id: number;
      label: string;
      horaireTravailId: number;
    }) => updateDepartement(id, { label: nextLabel, horaireTravailId: nextHoraireTravailId }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departements"] });
      cancelEdit();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteDepartement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departements"] });
      setDeletingDepartementId(null);
    },
    onError: () => {
      setDeletingDepartementId(null);
    },
  });

  if (isLoading || isHorairesLoading) {
    return <DepartementsPageSkeleton />;
  }

  if (isError || isHorairesError) {
    return (
      <section className="departements-page">
        <div className="departements-page__error-state" role="alert">
          <span className="departements-page__error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v5M12 16h.01" strokeLinecap="round" />
            </svg>
          </span>
          <h2>Impossible de charger les départements</h2>
          <p>Vérifiez votre connexion puis réessayez dans quelques instants.</p>
        </div>
      </section>
    );
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault();

    const trimmedLabel = label.trim();
    const parsedHoraireTravailId = Number(horaireTravailId);

    if (!trimmedLabel || !parsedHoraireTravailId) {
      return;
    }

    createMutation.mutate({ label: trimmedLabel, horaireTravailId: parsedHoraireTravailId });
  }

  function startEdit(departement: Departement) {
    setDeletingDepartementId(null);
    setEditingDepartement(departement);
    setEditingLabel(departement.label ?? "");
    setEditingHoraireTravailId(String(departement.horaireTravail?.id ?? ""));
  }

  function cancelEdit() {
    setEditingDepartement(null);
    setEditingLabel("");
    setEditingHoraireTravailId("");
  }

  function handleUpdateSubmit(event: FormEvent) {
    event.preventDefault();

    const trimmedLabel = editingLabel.trim();
    const parsedHoraireTravailId = Number(editingHoraireTravailId);

    if (!editingDepartement || !trimmedLabel || !parsedHoraireTravailId) {
      return;
    }

    updateMutation.mutate({
      id: editingDepartement.id,
      label: trimmedLabel,
      horaireTravailId: parsedHoraireTravailId,
    });
  }

  function requestDelete(departement: Departement) {
    cancelEdit();
    setDeletingDepartementId(departement.id);
  }

  function cancelDelete() {
    setDeletingDepartementId(null);
  }

  function confirmDelete(departement: Departement) {
    deleteMutation.mutate(departement.id);
  }

  const isMutationPending = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending;

  const departementsColumns: DataTableColumn<Departement>[] = [
    {
      key: "label",
      header: "Département",
      className: "departements-page__name-cell",
      render: (departement) => {
        const isEditing = editingDepartement?.id === departement.id;
        const isDeleting = deletingDepartementId === departement.id;
        const employeeCount = employeeCountByDepartment.get(departement.id) ?? 0;

        return (
          <div
            className={`departements-page__department ${
              isEditing ? "departements-page__department--editing" : ""
            } ${isDeleting ? "departements-page__department--deleting" : ""}`}
          >
            <span className="departements-page__avatar" aria-hidden="true">
              {getInitials(departement.label)}
            </span>
            <div className="departements-page__department-copy">
              <strong>{departement.label ?? "Sans nom"}</strong>
              <span>
                {employeeCount === 0
                  ? "Aucun employé assigné"
                  : `${employeeCount} employé${employeeCount > 1 ? "s" : ""}`}
              </span>
            </div>
          </div>
        );
      },
    },
    {
      key: "horaire",
      header: "Horaire de travail",
      className: "departements-page__horaire-cell",
      render: (departement) => {
        const horaire = departement.horaireTravail
          ? horairesById.get(departement.horaireTravail.id)
          : undefined;

        return (
          <div className="departements-page__horaire">
            <strong>{departement.horaireTravail?.label ?? "Non assigné"}</strong>
            <span>{formatPlagesHoraires(horaire)}</span>
          </div>
        );
      },
    },
    {
      key: "employees",
      header: "Effectif",
      className: "departements-page__count-cell",
      render: (departement) => {
        const employeeCount = employeeCountByDepartment.get(departement.id) ?? 0;

        return (
          <span
            className={`departements-page__count-badge ${
              employeeCount > 0
                ? "departements-page__count-badge--active"
                : "departements-page__count-badge--empty"
            }`}
          >
            {employeeCount}
          </span>
        );
      },
    },
    {
      key: "actions",
      header: "Actions",
      className: "departements-page__actions-cell",
      render: (departement) => {
        const isEditing = editingDepartement?.id === departement.id;
        const isDeleting = deletingDepartementId === departement.id;

        if (isEditing) {
          return null;
        }

        if (isDeleting) {
          return (
            <div className="departements-page__delete-confirm">
              <p>Supprimer ce département ?</p>
              <div className="departements-page__row-actions">
                <button
                  type="button"
                  className="departements-page__action-button departements-page__action-button--danger"
                  onClick={() => confirmDelete(departement)}
                  disabled={deleteMutation.isPending}
                >
                  {deleteMutation.isPending ? "Suppression..." : "Confirmer"}
                </button>
                <button
                  type="button"
                  className="departements-page__action-button"
                  onClick={cancelDelete}
                  disabled={deleteMutation.isPending}
                >
                  Annuler
                </button>
              </div>
            </div>
          );
        }

        return (
          <div className="departements-page__row-actions">
            <button
              type="button"
              className="departements-page__action-button departements-page__action-button--icon"
              onClick={() => startEdit(departement)}
              disabled={isMutationPending}
              aria-label={`Modifier ${departement.label ?? "ce département"}`}
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              Modifier
            </button>
            <button
              type="button"
              className="departements-page__action-button departements-page__action-button--danger departements-page__action-button--icon"
              onClick={() => requestDelete(departement)}
              disabled={isMutationPending}
              aria-label={`Supprimer ${departement.label ?? "ce département"}`}
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              Supprimer
            </button>
          </div>
        );
      },
    },
  ];

  return (
    <section className="departements-page">
      <header className="departements-page__header">
        <div className="departements-page__header-copy">
          <p className="departements-page__eyebrow">Organisation</p>
          <h1 className="departements-page__title">Départements</h1>
          <p className="departements-page__description">
            Structurez votre entreprise par équipes et services. Chaque département est rattaché
            à un horaire de travail qui définit les plages et la tolérance de retard.
          </p>
        </div>
      </header>

      <div className="departements-page__stats" aria-label="Statistiques des départements">
        <article className="departements-page__stat-card">
          <p className="departements-page__stat-label">Total départements</p>
          <strong className="departements-page__stat-value">{departements?.length ?? 0}</strong>
          <span className="departements-page__stat-hint">Services enregistrés</span>
        </article>

        <article className="departements-page__stat-card">
          <p className="departements-page__stat-label">Horaires disponibles</p>
          <strong className="departements-page__stat-value">{horairesTravail?.length ?? 0}</strong>
          <span className="departements-page__stat-hint">Modèles d'horaires configurés</span>
        </article>

        <article className="departements-page__stat-card">
          <p className="departements-page__stat-label">Employés assignés</p>
          <strong className="departements-page__stat-value departements-page__stat-value--accent">
            {assignedEmployeesCount}
          </strong>
          <span className="departements-page__stat-hint">Répartis dans les départements</span>
        </article>
      </div>

      <div className="departements-page__layout">
        <div className="departements-page__list-panel">
          <div className="departements-page__list-header">
            <div>
              <h2 className="departements-page__panel-title">Liste des départements</h2>
              <p className="departements-page__panel-description">
                {filteredDepartements.length} résultat{filteredDepartements.length > 1 ? "s" : ""}
                {searchQuery.trim() ? " pour votre recherche" : " au total"}
              </p>
            </div>

            <label className="departements-page__search">
              <span className="departements-page__search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" strokeLinecap="round" />
                </svg>
              </span>
              <input
                type="search"
                value={searchQuery}
                onChange={(event) => setSearchQuery(event.target.value)}
                placeholder="Rechercher un département ou un horaire..."
                aria-label="Rechercher un département"
              />
            </label>
          </div>

          {deleteMutation.isError && (
            <p className="departements-page__page-error" role="alert">
              Impossible de supprimer ce département. Il est peut-être encore utilisé par des employés.
            </p>
          )}

          <DataTable
            columns={departementsColumns}
            data={filteredDepartements}
            getRowKey={(departement) => departement.id}
            emptyMessage={
              searchQuery.trim()
                ? "Aucun département ne correspond à votre recherche."
                : "Aucun département trouvé. Ajoutez votre premier département à droite."
            }
          />
        </div>

        <aside className="departements-page__form-aside">
          {editingDepartement ? (
            <FormPanel
              title="Modifier le département"
              description={`Mise à jour de « ${editingDepartement.label ?? "Sans nom"} ».`}
              onSubmit={handleUpdateSubmit}
              className="departements-page__stacked-form"
              actions={
                <button
                  type="button"
                  className="departements-page__secondary-button"
                  onClick={cancelEdit}
                  disabled={updateMutation.isPending}
                >
                  Annuler
                </button>
              }
            >
              <TextInput
                label="Nom du département"
                name="editingLabel"
                type="text"
                value={editingLabel}
                onChange={(event) => setEditingLabel(event.target.value)}
                disabled={updateMutation.isPending}
                required
              />

              <SelectInput
                label="Horaire de travail"
                name="editingHoraireTravailId"
                value={editingHoraireTravailId}
                onChange={(event) => setEditingHoraireTravailId(event.target.value)}
                options={horaireOptions}
                placeholderOption="Sélectionner un horaire"
                disabled={updateMutation.isPending || horaireOptions.length === 0}
                required
              />

              <HorairePreview horaire={selectedEditHoraire} />

              <SubmitButton
                isLoading={updateMutation.isPending}
                loadingLabel="Mise à jour..."
                disabled={!editingHoraireTravailId}
              >
                Enregistrer
              </SubmitButton>

              {updateMutation.isError && (
                <p className="departements-page__form-error" role="alert">
                  Impossible de modifier ce département.
                </p>
              )}
            </FormPanel>
          ) : (
            <FormPanel
              title="Nouveau département"
              description="Associez un service à un horaire de travail pour définir ses plages horaires."
              onSubmit={handleSubmit}
              className="departements-page__stacked-form"
            >
              <TextInput
                label="Nom du département"
                name="label"
                type="text"
                placeholder="Ex: Ressources humaines"
                value={label}
                onChange={(event) => setLabel(event.target.value)}
                disabled={createMutation.isPending}
                required
              />

              <SelectInput
                label="Horaire de travail"
                name="horaireTravailId"
                value={horaireTravailId}
                onChange={(event) => setHoraireTravailId(event.target.value)}
                options={horaireOptions}
                placeholderOption="Sélectionner un horaire"
                disabled={createMutation.isPending || horaireOptions.length === 0}
                required
              />

              <HorairePreview horaire={selectedCreateHoraire} />

              <SubmitButton
                isLoading={createMutation.isPending}
                loadingLabel="Ajout..."
                disabled={!horaireTravailId}
              >
                Ajouter le département
              </SubmitButton>

              {horaireOptions.length === 0 && (
                <p className="departements-page__form-error" role="alert">
                  Aucun horaire de travail n'est disponible. Créez d'abord un horaire côté API.
                </p>
              )}

              {createMutation.isError && (
                <p className="departements-page__form-error" role="alert">
                  Impossible d'ajouter ce département.
                </p>
              )}
            </FormPanel>
          )}

          <div className="departements-page__tips">
            <h3>Conseils</h3>
            <ul>
              <li>Chaque département doit être lié à un horaire de travail.</li>
              <li>Les plages horaires définissent les heures d'arrivée et de départ.</li>
              <li>La tolérance de retard est appliquée avant de signaler un retard.</li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
  );
}

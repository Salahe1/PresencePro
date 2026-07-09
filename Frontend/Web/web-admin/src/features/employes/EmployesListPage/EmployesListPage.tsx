import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { DataTable, type DataTableColumn } from "../../../shared/components/DataTable";
import { FormPanel, SelectInput, SubmitButton, TextInput } from "../../../shared/components/Form";
import type { Employe } from "../../../shared/types/employe";
import { getDepartements } from "../../departements/api";
import {
  createEmploye,
  desactiverEmploye,
  getEmployes,
  updateEmploye,
} from "../api";
import "./EmployesListPage.css";

type StatusFilter = "all" | "active" | "inactive";

function getInitials(prenom: string, nom: string): string {
  return `${prenom.charAt(0)}${nom.charAt(0)}`.toUpperCase();
}

function formatFrenchDate(dateIso: string): string {
  const date = new Date(`${dateIso}T00:00:00`);

  return new Intl.DateTimeFormat("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(date);
}

function EmployesListPageSkeleton() {
  return (
    <section className="employes-list-page" aria-busy="true" aria-label="Chargement des employés">
      <header className="employes-list-page__header">
        <div className="employes-list-page__header-copy">
          <div className="employes-list-page__skeleton employes-list-page__skeleton--eyebrow" />
          <div className="employes-list-page__skeleton employes-list-page__skeleton--title" />
          <div className="employes-list-page__skeleton employes-list-page__skeleton--description" />
        </div>
      </header>

      <div className="employes-list-page__stats">
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={index} className="employes-list-page__skeleton employes-list-page__skeleton--stat" />
        ))}
      </div>

      <div className="employes-list-page__layout">
        <div className="employes-list-page__skeleton employes-list-page__skeleton--panel" />
        <div className="employes-list-page__skeleton employes-list-page__skeleton--panel employes-list-page__skeleton--panel-tall" />
      </div>
    </section>
  );
}

const emptyCreateForm = {
  nom: "",
  prenom: "",
  email: "",
  telephone: "",
  matricule: "",
  poste: "",
  dateEmbauche: "",
  departementId: "",
};

export function EmployesListPage() {
  const {
    data: employes,
    isLoading,
    isError,
  } = useQuery({ queryKey: ["employes"], queryFn: getEmployes });

  const { data: departements, isLoading: isDepartementsLoading } = useQuery({
    queryKey: ["departements"],
    queryFn: getDepartements,
  });

  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const [departmentFilter, setDepartmentFilter] = useState("");
  const [selectedEmploye, setSelectedEmploye] = useState<Employe | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [createForm, setCreateForm] = useState(emptyCreateForm);
  const [editForm, setEditForm] = useState(emptyCreateForm);
  const queryClient = useQueryClient();

  const departmentOptions = useMemo(
    () =>
      (departements ?? []).map((departement) => ({
        value: departement.id,
        label: departement.label ?? `Département #${departement.id}`,
      })),
    [departements]
  );

  const departmentFilterOptions = useMemo(
    () => [{ value: "", label: "Tous les départements" }, ...departmentOptions.map((option) => ({
      value: String(option.value),
      label: option.label,
    }))],
    [departmentOptions]
  );

  const filteredEmployes = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    return (employes ?? []).filter((employe) => {
      const matchesSearch =
        !query ||
        `${employe.prenom} ${employe.nom}`.toLowerCase().includes(query) ||
        employe.email.toLowerCase().includes(query) ||
        employe.matricule.toLowerCase().includes(query) ||
        employe.poste.toLowerCase().includes(query) ||
        (employe.departement?.label ?? "").toLowerCase().includes(query);

      const matchesStatus =
        statusFilter === "all" ||
        (statusFilter === "active" && employe.actif) ||
        (statusFilter === "inactive" && !employe.actif);

      const matchesDepartment =
        !departmentFilter || String(employe.departement?.id ?? "") === departmentFilter;

      return matchesSearch && matchesStatus && matchesDepartment;
    });
  }, [employes, searchQuery, statusFilter, departmentFilter]);

  const totalEmployes = employes?.length ?? 0;
  const employesActifs = employes?.filter((employe) => employe.actif).length ?? 0;
  const employesInactifs = totalEmployes - employesActifs;
  const unassignedEmployees = employes?.filter((employe) => !employe.departement).length ?? 0;

  const createMutation = useMutation({
    mutationFn: createEmploye,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["employes"] });
      setCreateForm(emptyCreateForm);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Parameters<typeof updateEmploye>[1] }) =>
      updateEmploye(id, data),
    onSuccess: (updatedEmploye) => {
      queryClient.invalidateQueries({ queryKey: ["employes"] });
      setSelectedEmploye(updatedEmploye);
      setIsEditing(false);
    },
  });

  const desactiverMutation = useMutation({
    mutationFn: desactiverEmploye,
    onSuccess: (updatedEmploye) => {
      queryClient.invalidateQueries({ queryKey: ["employes"] });
      setSelectedEmploye(updatedEmploye);
    },
  });

  if (isLoading || isDepartementsLoading) {
    return <EmployesListPageSkeleton />;
  }

  if (isError) {
    return (
      <section className="employes-list-page">
        <div className="employes-list-page__error-state" role="alert">
          <span className="employes-list-page__error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 8v5M12 16h.01" strokeLinecap="round" />
            </svg>
          </span>
          <h2>Impossible de charger les employés</h2>
          <p>Vérifiez votre connexion puis réessayez dans quelques instants.</p>
        </div>
      </section>
    );
  }

  function selectEmploye(employe: Employe) {
    setSelectedEmploye(employe);
    setIsEditing(false);
    setEditForm({
      nom: employe.nom,
      prenom: employe.prenom,
      email: employe.email,
      telephone: employe.telephone ?? "",
      matricule: employe.matricule,
      poste: employe.poste,
      dateEmbauche: employe.dateEmbauche,
      departementId: String(employe.departement?.id ?? ""),
    });
  }

  function clearSelection() {
    setSelectedEmploye(null);
    setIsEditing(false);
  }

  function handleCreateSubmit(event: FormEvent) {
    event.preventDefault();

    const departementId = Number(createForm.departementId);

    if (!createForm.nom.trim() || !createForm.prenom.trim() || !departementId) {
      return;
    }

    createMutation.mutate({
      nom: createForm.nom.trim(),
      prenom: createForm.prenom.trim(),
      email: createForm.email.trim(),
      telephone: createForm.telephone.trim(),
      matricule: createForm.matricule.trim(),
      poste: createForm.poste.trim(),
      dateEmbauche: createForm.dateEmbauche,
      departementId,
      actif: true,
    });
  }

  function handleUpdateSubmit(event: FormEvent) {
    event.preventDefault();

    if (!selectedEmploye) {
      return;
    }

    const departementId = Number(editForm.departementId);

    if (!editForm.nom.trim() || !editForm.prenom.trim() || !departementId) {
      return;
    }

    updateMutation.mutate({
      id: selectedEmploye.id,
      data: {
        nom: editForm.nom.trim(),
        prenom: editForm.prenom.trim(),
        email: editForm.email.trim(),
        telephone: editForm.telephone.trim(),
        matricule: editForm.matricule.trim(),
        poste: editForm.poste.trim(),
        dateEmbauche: editForm.dateEmbauche,
        departementId,
      },
    });
  }

  function handleDesactiver() {
    if (!selectedEmploye || !selectedEmploye.actif) {
      return;
    }

    const shouldDeactivate = window.confirm(
      `Désactiver le compte de ${selectedEmploye.prenom} ${selectedEmploye.nom} ?`
    );

    if (!shouldDeactivate) {
      return;
    }

    desactiverMutation.mutate(selectedEmploye.id);
  }

  const isMutationPending =
    createMutation.isPending || updateMutation.isPending || desactiverMutation.isPending;

  const employesColumns: DataTableColumn<Employe>[] = [
    {
      key: "employe",
      header: "Employé",
      className: "employes-list-page__name-cell",
      render: (employe) => {
        const isSelected = selectedEmploye?.id === employe.id;

        return (
          <button
            type="button"
            className={`employes-list-page__employee ${
              isSelected ? "employes-list-page__employee--selected" : ""
            }`}
            onClick={() => selectEmploye(employe)}
          >
            <span className="employes-list-page__avatar" aria-hidden="true">
              {getInitials(employe.prenom, employe.nom)}
            </span>
            <span className="employes-list-page__employee-copy">
              <strong>{employe.prenom} {employe.nom}</strong>
              <small>{employe.matricule}</small>
            </span>
          </button>
        );
      },
    },
    {
      key: "contact",
      header: "Contact",
      className: "employes-list-page__contact-cell",
      render: (employe) => (
        <div className="employes-list-page__contact">
          <span>{employe.email}</span>
          <small>{employe.telephone ?? "Téléphone non renseigné"}</small>
        </div>
      ),
    },
    {
      key: "poste",
      header: "Poste",
      render: (employe) => employe.poste,
    },
    {
      key: "departement",
      header: "Département",
      className: "employes-list-page__department-cell",
      render: (employe) => (
        <span
          className={`employes-list-page__department-badge ${
            employe.departement
              ? "employes-list-page__department-badge--assigned"
              : "employes-list-page__department-badge--empty"
          }`}
        >
          {employe.departement?.label ?? "Non assigné"}
        </span>
      ),
    },
    {
      key: "dateEmbauche",
      header: "Embauche",
      className: "employes-list-page__date-cell",
      render: (employe) => formatFrenchDate(employe.dateEmbauche),
    },
    {
      key: "actif",
      header: "Statut",
      className: "employes-list-page__status-cell",
      render: (employe) => (
        <span
          className={`employes-list-page__status ${
            employe.actif
              ? "employes-list-page__status--active"
              : "employes-list-page__status--inactive"
          }`}
        >
          {employe.actif ? "Actif" : "Inactif"}
        </span>
      ),
    },
  ];

  return (
    <section className="employes-list-page">
      <header className="employes-list-page__header">
        <div className="employes-list-page__header-copy">
          <p className="employes-list-page__eyebrow">Ressources humaines</p>
          <h1 className="employes-list-page__title">Employés</h1>
          <p className="employes-list-page__description">
            Consultez l&apos;effectif, filtrez par département ou statut, et gérez les fiches employés.
          </p>
        </div>
      </header>

      <div className="employes-list-page__stats" aria-label="Statistiques des employés">
        <article className="employes-list-page__stat-card">
          <p className="employes-list-page__stat-label">Total employés</p>
          <strong className="employes-list-page__stat-value">{totalEmployes}</strong>
          <span className="employes-list-page__stat-hint">Effectif enregistré</span>
        </article>

        <article className="employes-list-page__stat-card">
          <p className="employes-list-page__stat-label">Actifs</p>
          <strong className="employes-list-page__stat-value employes-list-page__stat-value--success">
            {employesActifs}
          </strong>
          <span className="employes-list-page__stat-hint">Comptes en service</span>
        </article>

        <article className="employes-list-page__stat-card">
          <p className="employes-list-page__stat-label">Inactifs</p>
          <strong className="employes-list-page__stat-value employes-list-page__stat-value--muted">
            {employesInactifs}
          </strong>
          <span className="employes-list-page__stat-hint">Hors effectif actif</span>
        </article>

        <article className="employes-list-page__stat-card">
          <p className="employes-list-page__stat-label">Sans département</p>
          <strong className="employes-list-page__stat-value employes-list-page__stat-value--warning">
            {unassignedEmployees}
          </strong>
          <span className="employes-list-page__stat-hint">À rattacher à un service</span>
        </article>
      </div>

      <div className="employes-list-page__layout">
        <div className="employes-list-page__list-panel">
          <div className="employes-list-page__list-header">
            <div>
              <h2 className="employes-list-page__panel-title">Liste des employés</h2>
              <p className="employes-list-page__panel-description">
                {filteredEmployes.length} résultat{filteredEmployes.length > 1 ? "s" : ""}
                {searchQuery.trim() || statusFilter !== "all" || departmentFilter
                  ? " pour vos filtres"
                  : " au total"}
              </p>
            </div>
          </div>

          <div className="employes-list-page__toolbar">
            <label className="employes-list-page__search">
              <span className="employes-list-page__search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" strokeLinecap="round" />
                </svg>
              </span>
              <input
                type="search"
                value={searchQuery}
                onChange={(event) => setSearchQuery(event.target.value)}
                placeholder="Rechercher par nom, email, matricule..."
                aria-label="Rechercher un employé"
              />
            </label>

            <div className="employes-list-page__filters">
              <SelectInput
                label="Statut"
                name="statusFilter"
                value={statusFilter}
                onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}
                options={[
                  { value: "all", label: "Tous les statuts" },
                  { value: "active", label: "Actifs uniquement" },
                  { value: "inactive", label: "Inactifs uniquement" },
                ]}
              />

              <SelectInput
                label="Département"
                name="departmentFilter"
                value={departmentFilter}
                onChange={(event) => setDepartmentFilter(event.target.value)}
                options={departmentFilterOptions}
              />
            </div>
          </div>

          <DataTable
            columns={employesColumns}
            data={filteredEmployes}
            getRowKey={(employe) => employe.id}
            emptyMessage={
              searchQuery.trim() || statusFilter !== "all" || departmentFilter
                ? "Aucun employé ne correspond à vos filtres."
                : "Aucun employé trouvé. Ajoutez votre premier employé à droite."
            }
          />
        </div>

        <aside className="employes-list-page__aside">
          {selectedEmploye && !isEditing ? (
            <div className="employes-list-page__profile-card">
              <div className="employes-list-page__profile-header">
                <span className="employes-list-page__avatar employes-list-page__avatar--large" aria-hidden="true">
                  {getInitials(selectedEmploye.prenom, selectedEmploye.nom)}
                </span>
                <div>
                  <h2>{selectedEmploye.prenom} {selectedEmploye.nom}</h2>
                  <p>{selectedEmploye.poste}</p>
                </div>
              </div>

              <dl className="employes-list-page__profile-details">
                <div>
                  <dt>Matricule</dt>
                  <dd>{selectedEmploye.matricule}</dd>
                </div>
                <div>
                  <dt>Email</dt>
                  <dd>{selectedEmploye.email}</dd>
                </div>
                <div>
                  <dt>Téléphone</dt>
                  <dd>{selectedEmploye.telephone ?? "—"}</dd>
                </div>
                <div>
                  <dt>Département</dt>
                  <dd>{selectedEmploye.departement?.label ?? "Non assigné"}</dd>
                </div>
                <div>
                  <dt>Date d&apos;embauche</dt>
                  <dd>{formatFrenchDate(selectedEmploye.dateEmbauche)}</dd>
                </div>
                <div>
                  <dt>Statut</dt>
                  <dd>
                    <span
                      className={`employes-list-page__status ${
                        selectedEmploye.actif
                          ? "employes-list-page__status--active"
                          : "employes-list-page__status--inactive"
                      }`}
                    >
                      {selectedEmploye.actif ? "Actif" : "Inactif"}
                    </span>
                  </dd>
                </div>
              </dl>

              <div className="employes-list-page__profile-actions">
                <button
                  type="button"
                  className="employes-list-page__action-button employes-list-page__action-button--primary"
                  onClick={() => setIsEditing(true)}
                  disabled={isMutationPending}
                >
                  Modifier
                </button>
                {selectedEmploye.actif && (
                  <button
                    type="button"
                    className="employes-list-page__action-button employes-list-page__action-button--danger"
                    onClick={handleDesactiver}
                    disabled={isMutationPending}
                  >
                    {desactiverMutation.isPending ? "Désactivation..." : "Désactiver"}
                  </button>
                )}
                <button
                  type="button"
                  className="employes-list-page__action-button"
                  onClick={clearSelection}
                  disabled={isMutationPending}
                >
                  Fermer
                </button>
              </div>
            </div>
          ) : selectedEmploye && isEditing ? (
            <FormPanel
              title="Modifier l'employé"
              description={`Mise à jour de ${selectedEmploye.prenom} ${selectedEmploye.nom}.`}
              onSubmit={handleUpdateSubmit}
              className="employes-list-page__stacked-form"
              actions={
                <button
                  type="button"
                  className="employes-list-page__action-button"
                  onClick={() => setIsEditing(false)}
                  disabled={updateMutation.isPending}
                >
                  Annuler
                </button>
              }
            >
              <TextInput
                label="Prénom"
                name="prenom"
                value={editForm.prenom}
                onChange={(event) => setEditForm((current) => ({ ...current, prenom: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Nom"
                name="nom"
                value={editForm.nom}
                onChange={(event) => setEditForm((current) => ({ ...current, nom: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Email"
                name="email"
                type="email"
                value={editForm.email}
                onChange={(event) => setEditForm((current) => ({ ...current, email: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Téléphone"
                name="telephone"
                value={editForm.telephone}
                onChange={(event) => setEditForm((current) => ({ ...current, telephone: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Matricule"
                name="matricule"
                value={editForm.matricule}
                onChange={(event) => setEditForm((current) => ({ ...current, matricule: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Poste"
                name="poste"
                value={editForm.poste}
                onChange={(event) => setEditForm((current) => ({ ...current, poste: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <TextInput
                label="Date d'embauche"
                name="dateEmbauche"
                type="date"
                value={editForm.dateEmbauche}
                onChange={(event) => setEditForm((current) => ({ ...current, dateEmbauche: event.target.value }))}
                disabled={updateMutation.isPending}
                required
              />
              <SelectInput
                label="Département"
                name="departementId"
                value={editForm.departementId}
                onChange={(event) => setEditForm((current) => ({ ...current, departementId: event.target.value }))}
                options={departmentOptions}
                placeholderOption="Sélectionner un département"
                disabled={updateMutation.isPending || departmentOptions.length === 0}
                required
              />
              <SubmitButton
                isLoading={updateMutation.isPending}
                loadingLabel="Mise à jour..."
                disabled={!editForm.departementId}
              >
                Enregistrer
              </SubmitButton>
              {updateMutation.isError && (
                <p className="employes-list-page__form-error" role="alert">
                  Impossible de modifier cet employé.
                </p>
              )}
            </FormPanel>
          ) : (
            <FormPanel
              title="Nouvel employé"
              description="Ajoutez un collaborateur et rattachez-le à un département."
              onSubmit={handleCreateSubmit}
              className="employes-list-page__stacked-form"
            >
              <TextInput
                label="Prénom"
                name="createPrenom"
                value={createForm.prenom}
                onChange={(event) => setCreateForm((current) => ({ ...current, prenom: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Nom"
                name="createNom"
                value={createForm.nom}
                onChange={(event) => setCreateForm((current) => ({ ...current, nom: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Email"
                name="createEmail"
                type="email"
                value={createForm.email}
                onChange={(event) => setCreateForm((current) => ({ ...current, email: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Téléphone"
                name="createTelephone"
                value={createForm.telephone}
                onChange={(event) => setCreateForm((current) => ({ ...current, telephone: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Matricule"
                name="createMatricule"
                value={createForm.matricule}
                onChange={(event) => setCreateForm((current) => ({ ...current, matricule: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Poste"
                name="createPoste"
                value={createForm.poste}
                onChange={(event) => setCreateForm((current) => ({ ...current, poste: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <TextInput
                label="Date d'embauche"
                name="createDateEmbauche"
                type="date"
                value={createForm.dateEmbauche}
                onChange={(event) => setCreateForm((current) => ({ ...current, dateEmbauche: event.target.value }))}
                disabled={createMutation.isPending}
                required
              />
              <SelectInput
                label="Département"
                name="createDepartementId"
                value={createForm.departementId}
                onChange={(event) => setCreateForm((current) => ({ ...current, departementId: event.target.value }))}
                options={departmentOptions}
                placeholderOption="Sélectionner un département"
                disabled={createMutation.isPending || departmentOptions.length === 0}
                required
              />
              <SubmitButton
                isLoading={createMutation.isPending}
                loadingLabel="Ajout..."
                disabled={!createForm.departementId}
              >
                Ajouter l&apos;employé
              </SubmitButton>
              {departmentOptions.length === 0 && (
                <p className="employes-list-page__form-error" role="alert">
                  Créez d&apos;abord un département avant d&apos;ajouter un employé.
                </p>
              )}
              {createMutation.isError && (
                <p className="employes-list-page__form-error" role="alert">
                  Impossible d&apos;ajouter cet employé.
                </p>
              )}
            </FormPanel>
          )}

          <div className="employes-list-page__tips">
            <h3>Conseils</h3>
            <ul>
              <li>Cliquez sur un employé pour afficher sa fiche détaillée.</li>
              <li>Utilisez les filtres pour isoler un département ou un statut.</li>
              <li>La désactivation conserve l&apos;historique de pointage.</li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
  );
}

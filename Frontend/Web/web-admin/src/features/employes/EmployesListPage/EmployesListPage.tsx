import { useQuery } from "@tanstack/react-query";
import { DataTable, type DataTableColumn } from "../../../shared/components/DataTable";
import type { Employe } from "../../../shared/types/employe";
import { getEmployes } from "../api";
import "./EmployesListPage.css";

const employesColumns: DataTableColumn<Employe>[] = [
  {
    key: "matricule",
    header: "Matricule",
    render: (employe) => employe.matricule,
  },
  {
    key: "nom-complet",
    header: "Nom complet",
    className: "employes-list-page__name-cell",
    render: (employe) => `${employe.prenom} ${employe.nom}`,
  },
  {
    key: "email",
    header: "Email",
    render: (employe) => employe.email,
  },
  {
    key: "poste",
    header: "Poste",
    render: (employe) => employe.poste,
  },
  {
    key: "actif",
    header: "Actif",
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

export function EmployesListPage() {
  const {
    data: employes,
    isLoading,
    isError,
  } = useQuery({ queryKey: ["employes"], queryFn: getEmployes, });

  if (isLoading) {
    return <p className="employes-list-page__empty">Chargement des employés...</p>;
  }

  if (isError) {
    return <p className="employes-list-page__empty">Erreur lors du chargement des employés.</p>;
  }

  return (
    <div className="employes-list-page">
      <section className="employes-list-page__header">
        <div>
          <p className="employes-list-page__eyebrow">Ressources Humaines</p>
          <h1 className="employes-list-page__title">Employés</h1>
          <p className="employes-list-page__description">
            Retrouvez la liste des employés enregistrés avec leur poste, email et statut actif.
          </p>
        </div>

        <div className="employes-list-page__meta">
          <strong>{employes?.length ?? 0}</strong>
          <small>Total des employés</small>
        </div>
      </section>

      <DataTable
        columns={employesColumns}
        data={employes ?? []}
        getRowKey={(employe) => employe.id}
        emptyMessage="Aucun employe trouve."
      />
    </div>
  );
}

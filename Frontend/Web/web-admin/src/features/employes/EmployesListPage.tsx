import { useQuery } from "@tanstack/react-query";
import { getEmployes } from "./api";
import "./EmployesListPage.css";

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
    <main className="employes-list-page">
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

      <div className="employes-list-page__table-card">
        <div className="employes-list-page__table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Matricule</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Poste</th>
                <th>Actif</th>
              </tr>
            </thead>

            <tbody>
              {employes?.map((employe) => (
                <tr key={employe.id}>
                  <td>{employe.matricule}</td>
                  <td className="employes-list-page__name-cell">
                    {employe.prenom} {employe.nom}
                  </td>
                  <td>{employe.email}</td>
                  <td>{employe.poste}</td>
                  <td>
                    <span
                      className={`employes-list-page__status ${
                        employe.actif
                          ? "employes-list-page__status--active"
                          : "employes-list-page__status--inactive"
                      }`}
                    >
                      {employe.actif ? "Actif" : "Inactif"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </main>
  );
}
import { useQuery } from "@tanstack/react-query";
import { getEmployes } from "./api";

export function EmployesListPage() {
  const {
    data: employes,
    isLoading,
    isError,
  } = useQuery({ queryKey: ["employes"], queryFn: getEmployes, });

  if (isLoading) { return <p>Chargement des employés...</p>; }

  if (isError) { return <p>Erreur lors du chargement des employés.</p>; }

  return (
    <main>
      <h1>Employés</h1>

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
              <td>
                {employe.prenom} {employe.nom}
              </td>
              <td>{employe.email}</td>
              <td>{employe.poste}</td>
              <td>{employe.actif ? "Oui" : "Non"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </main>
  );
}
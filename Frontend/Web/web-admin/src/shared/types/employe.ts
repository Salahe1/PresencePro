export type DepartementSummary = {
  id: number;
  label: string;
};

export type Employe = {
  id: number;
  matricule: string;
  nom: string;
  prenom: string;
  email: string;
  telephone: string | null;
  poste: string;
  dateEmbauche: string;
  actif: boolean;
  photo: string | null;
  departement: DepartementSummary | null;
};
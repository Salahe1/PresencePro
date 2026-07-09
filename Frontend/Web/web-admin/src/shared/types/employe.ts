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

export type CreateEmployeDto = {
  nom: string;
  prenom: string;
  email: string;
  telephone: string;
  matricule: string;
  poste: string;
  dateEmbauche: string;
  departementId: number;
  actif?: boolean;
};

export type UpdateEmployeDto = Partial<CreateEmployeDto>;

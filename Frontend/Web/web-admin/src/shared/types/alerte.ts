export type AlerteEmploye = {
  id: number;
  nomComplet: string;
  matricule: string;
};

export type Alerte = {
  id: number;
  type: string | null;
  statut: string | null;
  message: string | null;
  employe: AlerteEmploye | null;
};

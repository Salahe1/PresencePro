export type AbsenceEmploye = {
  id: number;
  matricule: string;
  nomComplet: string;
};

export type Absence = {
  id: number;
  date: string;
  statut: string;
  typeAbsence: string | null;
  ordrePlage: number | null;
  heureDebutPrevue: string | null;
  heureFinPrevue: string | null;
  employe: AbsenceEmploye | null;
};

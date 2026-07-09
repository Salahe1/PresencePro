export type Retard = {
  id: number;
  dateJour: string;
  heurePrevue: string;
  heureArrivee: string;
  justifie: boolean;
  employeNom: string;
  employeMatricule: string;
  dureeRetardMinutes: number;
  commentaire: string | null;
};

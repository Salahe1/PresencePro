export type PlageHoraire = {
  id: number;
  heureDebut: string;
  heureFin: string;
  ordre: number;
};

export type HoraireTravail = {
  id: number;
  label: string | null;
  toleranceRetard: string;
  plagesHoraires: PlageHoraire[];
};

export type HoraireTravailSummary = {
  id: number;
  label: string | null;
};

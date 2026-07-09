import type { HoraireTravailSummary } from "./horaireTravail";

export type Departement = {
  id: number;
  label: string | null;
  horaireTravail: HoraireTravailSummary | null;
};

export type CreateDepartementDto = {
  label: string;
  horaireTravailId: number;
};

export type UpdateDepartementDto = {
  label?: string;
  horaireTravailId?: number;
};

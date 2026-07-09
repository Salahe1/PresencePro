import { http } from "../../shared/api/http";
import type { HoraireTravail } from "../../shared/types/horaireTravail";

export async function getHorairesTravail(): Promise<HoraireTravail[]> {
  const response = await http.get("/horaires-travail");
  return response.data;
}

export async function getHoraireTravail(id: number): Promise<HoraireTravail> {
  const response = await http.get(`/horaires-travail/${id}`);
  return response.data;
}

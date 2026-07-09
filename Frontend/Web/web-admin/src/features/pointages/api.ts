import { http } from "../../shared/api/http";
import type { Pointage } from "../../shared/types/pointage";

export async function getPointagesByDate(date: string): Promise<Pointage[]> {
  const response = await http.get(`/pointages/date/${date}`);
  return response.data;
}

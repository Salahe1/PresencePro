import { http } from "../../shared/api/http";
import type { Absence } from "../../shared/types/absence";

export async function getAbsencesByDate(date: string): Promise<Absence[]> {
  const response = await http.get(`/absences/date/${date}`);
  return response.data;
}

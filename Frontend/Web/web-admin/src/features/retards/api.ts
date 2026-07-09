import { http } from "../../shared/api/http";
import type { Retard } from "../../shared/types/retard";

export async function getRetardsByDate(date: string): Promise<Retard[]> {
  const response = await http.get(`/retards/date/${date}`);
  return response.data;
}

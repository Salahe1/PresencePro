import { http } from "../../shared/api/http";
import type { Alerte } from "../../shared/types/alerte";

export async function getAlertes(): Promise<Alerte[]> {
  const response = await http.get("/alertes");
  return response.data;
}

import { http } from "../../shared/api/http";
import type { Employe } from "../../shared/types/employe";

export async function getEmployes(): Promise<Employe[]> {
  const response = await http.get<Employe[]>("/employes");
  return response.data;
}
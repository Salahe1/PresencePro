import { http } from "../../shared/api/http";
import type { CreateEmployeDto, Employe, UpdateEmployeDto } from "../../shared/types/employe";

export async function getEmployes(): Promise<Employe[]> {
  const response = await http.get<Employe[]>("/employes");
  return response.data;
}

export async function getEmploye(id: number): Promise<Employe> {
  const response = await http.get<Employe>(`/employes/${id}`);
  return response.data;
}

export async function createEmploye(data: CreateEmployeDto): Promise<Employe> {
  const response = await http.post<Employe>("/employes", data);
  return response.data;
}

export async function updateEmploye(id: number, data: UpdateEmployeDto): Promise<Employe> {
  const response = await http.put<Employe>(`/employes/${id}`, data);
  return response.data;
}

export async function desactiverEmploye(id: number): Promise<Employe> {
  const response = await http.patch<Employe>(`/employes/${id}/desactiver`);
  return response.data;
}

export async function deleteEmploye(id: number): Promise<void> {
  await http.delete(`/employes/${id}`);
}

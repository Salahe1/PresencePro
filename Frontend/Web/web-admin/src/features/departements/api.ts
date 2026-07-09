import { http } from "../../shared/api/http";
import type { CreateDepartementDto, Departement, UpdateDepartementDto } from "../../shared/types/departement";

export async function getDepartements(): Promise<Departement[]> {
  const response = await http.get("/departements");
  return response.data;
}

export async function getDepartement(id: number): Promise<Departement> {
  const response = await http.get(`/departements/${id}`);
  return response.data;
}

export async function createDepartement(data: CreateDepartementDto): Promise<Departement> {
    const response = await http.post("/departements", data);
    return response.data;
}

export async function updateDepartement(id: number, data: UpdateDepartementDto): Promise<Departement> {
    const response = await http.put(`/departements/${id}`, data);
    return response.data;
}

export async function deleteDepartement(id: number): Promise<void> {
    await http.delete(`/departements/${id}`);
}

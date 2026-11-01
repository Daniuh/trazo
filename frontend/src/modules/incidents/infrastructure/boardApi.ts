// Repositorio de "Mis incidentes": todos los asignados a la persona de una vez, con los estados a los que se puede mover
// cada uno. Documentado en docs/frontend/incidents.md
import { http } from '@/shared/http'
import type { Incident } from '../domain/incident'
import { toIncident, type IncidentDto } from './mappers'

export interface Board {
  items: Incident[]
  // Había más de los que caben en el tablero
  truncated: boolean
  limit: number
  // Días que se ven los cerrados y cancelados
  finishedDays: number
}

interface BoardResponse {
  data: Omit<IncidentDto, 'description'>[]
  meta: { truncated: boolean; limit: number; finished_days: number }
}

export const boardApi = {
  async mine(): Promise<Board> {
    const { data } = await http.get<BoardResponse>('/incidents/mine')
    return {
      // El tablero no trae la descripción; se completa vacía para reutilizar el mapeo
      items: data.data.map((dto) => toIncident({ ...dto, description: '' })),
      truncated: data.meta.truncated,
      limit: data.meta.limit,
      finishedDays: data.meta.finished_days,
    }
  },
}

// Nombres del orden para las personas: encabezados de la tabla y selector "Ordenar por" de celular y tablet.
// Documentado en docs/frontend/incidents.md
import type { SortField } from '../../domain/listing'

export const COLUMN_LABELS: Record<SortField, string> = {
  code: 'Código',
  created_at: 'Creado',
  due_date: 'Vence',
  updated_at: 'Actualizado',
  priority: 'Prioridad',
  status: 'Estado',
}

// Valor "campo" o "-campo" (descendente), como en la API
export const SORT_CHOICES: { label: string; value: string }[] = [
  { label: 'Más recientes', value: '-created_at' },
  { label: 'Más antiguos', value: 'created_at' },
  { label: 'Vencen antes', value: 'due_date' },
  { label: 'Vencen después', value: '-due_date' },
  { label: 'Prioridad más alta', value: '-priority' },
  { label: 'Prioridad más baja', value: 'priority' },
  { label: 'Actualizados hace poco', value: '-updated_at' },
  { label: 'Estado', value: 'status' },
]

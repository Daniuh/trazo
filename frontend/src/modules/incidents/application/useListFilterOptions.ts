// Opciones de los filtros del listado según los catálogos y los permisos de la persona.
// Quien asigna elige entre todas las personas; un agente, entre "Asignados a mí" y "Sin asignar"; el solicitante no filtra
// por asignado porque solo ve lo suyo. Documentado en docs/frontend/incidents.md
import { computed, onMounted } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { UNASSIGNED } from '../domain/listing'
import { useAssignees } from './useAssignees'
import { useCatalogStore } from './useCatalogStore'

export interface FilterOption {
  label: string
  value: string
}

export function useListFilterOptions() {
  const session = useSessionStore()
  const catalogs = useCatalogStore()
  const assignees = useAssignees()

  const canAssign = computed(() => session.can('incidents.assign'))
  const canSeeDeleted = computed(() => session.can('incidents.restore'))
  const myId = computed(() => session.user?.id ?? null)

  const statuses = computed<FilterOption[]>(() =>
    (catalogs.catalogs?.statuses ?? []).map((status) => ({ label: status.name, value: status.code })),
  )
  const priorities = computed<FilterOption[]>(() =>
    catalogs.priorities.map((priority) => ({ label: priority.name, value: priority.code })),
  )

  const assigneeOptions = computed<FilterOption[]>(() => {
    const unassigned = { label: 'Sin asignar', value: UNASSIGNED }
    if (canAssign.value) {
      return [unassigned, ...assignees.people.value.map((person) => ({ label: person.name, value: person.id }))]
    }
    const options: FilterOption[] = []
    if (session.can('incidents.view.assigned') && myId.value)
      options.push({ label: 'Asignados a mí', value: myId.value })
    if (session.can('incidents.view.unassigned')) options.push(unassigned)
    return options
  })

  onMounted(() => {
    void catalogs.load()
    if (canAssign.value) void assignees.load()
  })

  return { statuses, priorities, assigneeOptions, canSeeDeleted, myId }
}

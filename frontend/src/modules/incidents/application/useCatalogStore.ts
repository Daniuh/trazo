// Catálogos de prioridades y estados, pedidos una sola vez por sesión (la API además responde con ETag).
// Documentado en docs/frontend/incidents.md
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { Catalogs } from '../domain/incident'
import { incidentsApi } from '../infrastructure/incidentsApi'

export const useCatalogStore = defineStore('incident-catalogs', () => {
  const catalogs = ref<Catalogs | null>(null)
  let pending: Promise<Catalogs> | null = null

  // Si varias pantallas lo piden a la vez, comparten la misma petición
  async function load(): Promise<Catalogs> {
    if (catalogs.value) return catalogs.value
    pending ??= incidentsApi.catalogs().finally(() => (pending = null))
    catalogs.value = await pending
    return catalogs.value
  }

  const priorities = computed(() => catalogs.value?.priorities ?? [])
  const priority = (code: string) => priorities.value.find((item) => item.code === code) ?? null
  const isFinalStatus = (code: string) => catalogs.value?.statuses.find((item) => item.code === code)?.isFinal ?? false

  return { catalogs, load, priorities, priority, isFinalStatus }
})

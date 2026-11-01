<!-- Vista Lista de "Mis incidentes": la misma tabla del listado (portátil y monitor) o sus tarjetas (celular y tablet),
     solo con lo asignado a la persona. El tablero ya trae todo, así que ordenar no vuelve a pedir nada a la API.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useViewport } from '@/shared/ui/useViewport'
import { sortBoardItems } from '../../domain/board'
import type { Incident } from '../../domain/incident'
import { DEFAULT_CRITERIA, type SortField } from '../../domain/listing'
import IncidentCards from '../list/IncidentCards.vue'
import IncidentTable from '../list/IncidentTable.vue'

const props = defineProps<{ items: Incident[]; statusOrder: string[] }>()

const router = useRouter()
const { isLaptopUp } = useViewport()

// Lo que vence primero, arriba
const sort = ref<SortField>('due_date')
const descending = ref(false)
const criteria = computed(() => ({ ...DEFAULT_CRITERIA, sort: sort.value, descending: descending.value }))
const sorted = computed(() => sortBoardItems(props.items, sort.value, descending.value, props.statusOrder))

function changeSort(field: SortField, desc: boolean): void {
  sort.value = field
  descending.value = desc
}

const open = (id: string) => void router.push({ name: 'incident-detail', params: { id } })
</script>

<template>
  <IncidentTable
    v-if="isLaptopUp"
    :items="sorted"
    :criteria="criteria"
    :selected-id="null"
    :compact="false"
    hide-assignee
    @open="open"
    @sort="changeSort"
  />
  <IncidentCards v-else :items="sorted" :deleted="false" @open="open" />
</template>

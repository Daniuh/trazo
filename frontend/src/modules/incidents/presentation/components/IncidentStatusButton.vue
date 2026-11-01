<!-- Botón "Cambiar estado" con el menú de estados a los que la persona puede llevar el incidente (next_statuses).
     Lo usan el detalle y la vista previa del listado. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import type { CatalogItem } from '../../domain/incident'

const props = defineProps<{ nextStatuses: CatalogItem[]; busy?: boolean }>()
const emit = defineEmits<{ status: [code: string] }>()

const items = computed(() => props.nextStatuses.map((status) => ({ key: status.code, label: status.name })))
</script>

<template>
  <AppButton :loading="busy" icon="refresh">
    Cambiar estado
    <AppActionMenu :items="items" label="Estados disponibles" @select="emit('status', $event)" />
  </AppButton>
</template>

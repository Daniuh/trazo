<!-- Barra de la auditoría. Portátil y monitor: los filtros en una fila. Celular y tablet: "Filtros (n)", que abre el
     panel con los filtros en borrador y los aplica de una vez. Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppFilterSheet from '@/shared/ui/AppFilterSheet.vue'
import { useViewport } from '@/shared/ui/useViewport'
import {
  activeAuditFilterCount,
  EMPTY_CRITERIA,
  type AuditCatalog,
  type AuditCriteria,
  type AuditTab,
} from '../../domain/audit'
import AuditFilterFields from './AuditFilterFields.vue'

const props = defineProps<{
  tab: AuditTab
  criteria: AuditCriteria
  catalog: AuditCatalog | null
  incidentError?: string
}>()
const emit = defineEmits<{ change: [changes: Partial<AuditCriteria>] }>()

const { isLaptopUp } = useViewport()
const filterCount = computed(() => activeAuditFilterCount(props.criteria))

const sheetOpen = ref(false)
const draft = ref<AuditCriteria>({ ...props.criteria })
watch(sheetOpen, (open) => {
  if (open) draft.value = { ...props.criteria }
})

function applySheet(): void {
  emit('change', draft.value)
  sheetOpen.value = false
}
</script>

<template>
  <AuditFilterFields
    v-if="isLaptopUp"
    :tab="tab"
    :criteria="criteria"
    :catalog="catalog"
    :incident-error="incidentError"
    @change="emit('change', $event)"
  />
  <div v-else>
    <AppButton variant="secondary" icon="filter" @click="sheetOpen = true">
      Filtros{{ filterCount > 0 ? ` (${filterCount})` : '' }}
    </AppButton>
    <AppFilterSheet v-model:open="sheetOpen" @apply="applySheet" @clear="draft = { ...EMPTY_CRITERIA }">
      <AuditFilterFields
        :tab="tab"
        :criteria="draft"
        draft
        :catalog="catalog"
        :incident-error="incidentError"
        @change="draft = { ...draft, ...$event }"
      />
    </AppFilterSheet>
  </div>
</template>

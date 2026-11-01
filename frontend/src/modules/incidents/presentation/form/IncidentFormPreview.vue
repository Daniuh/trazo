<!-- "Así se verá": el incidente con lo que se lleva escrito, en la misma fila de la lista agrupada (prioridad, estado,
     asignado y avance del plazo). Es solo ilustrativa: no se puede tocar. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import type { CatalogItem, PriorityOption } from '../../domain/incident'
import type { IncidentSummary } from '../../domain/listing'
import IncidentLine from '../list/IncidentLine.vue'

const props = defineProps<{
  title: string
  priority: PriorityOption | null
  dueDate: string | null
  status: CatalogItem
  assigneeName: string | null
  // Al editar, el código real; al crear, se asigna al guardar
  code: string | null
}>()

const NO_ACTIONS = {
  update: false,
  changeStatus: false,
  take: false,
  assign: false,
  delete: false,
  restore: false,
  requestReopen: false,
  reopen: false,
  decideReopen: false,
  comment: false,
}

const preview = computed<IncidentSummary | null>(() => {
  if (!props.priority || !props.dueDate) return null
  const now = new Date().toISOString()
  return {
    id: 'preview',
    code: props.code ?? 'Nuevo',
    title: props.title.trim() || 'Tu incidente',
    priority: { code: props.priority.code, name: props.priority.name, level: props.priority.level },
    status: { ...props.status, isFinal: false },
    creator: { id: '', name: '' },
    assignee: props.assigneeName ? { id: 'preview', name: props.assigneeName } : null,
    dueDate: props.dueDate,
    isOverdue: false,
    // Recién creado: el plazo aún no ha corrido
    deadline: { bucket: 'later', timeUsed: 0, resolvedOnTime: null },
    reopenRequested: false,
    resolvedAt: null,
    createdAt: now,
    updatedAt: now,
    deletedAt: null,
    version: 0,
    actions: NO_ACTIONS,
    reopenRequest: null,
    commentsCount: null,
  }
})
</script>

<template>
  <section class="form-preview" aria-labelledby="form-preview-title">
    <h2 id="form-preview-title" class="form-preview__title">Así se verá</h2>
    <!-- La fila es de muestra: no se enfoca, no se abre y los lectores de pantalla la saltan (lo dicho ya está en los campos) -->
    <div v-if="preview" class="form-preview__line" inert aria-hidden="true">
      <IncidentLine :incident="preview" :selected="false" :deleted="false" />
    </div>
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.form-preview {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.form-preview__title {
  margin: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.form-preview__line {
  overflow: hidden;
  border: 1px solid $color-border;
  border-radius: $radius-card;
}
</style>

<!-- Auditoría en tarjetas (celular y tablet): acción, sobre qué, quién y cuándo, y el primer cambio. Tocar una tarjeta
     abre su detalle. Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { formatShortDateTime } from '@/shared/format/dates'
import type { AuditCatalog, AuditEntry } from '../../domain/audit'
import { changeSummary, targetText } from '../auditTexts'

defineProps<{ items: AuditEntry[]; catalog: AuditCatalog | null }>()
const emit = defineEmits<{ open: [id: number] }>()
</script>

<template>
  <ul class="audit-cards">
    <li v-for="entry in items" :key="entry.id">
      <article
        class="audit-cards__card"
        tabindex="0"
        :aria-label="`${entry.type.name}: ${targetText(entry)}`"
        @click="emit('open', entry.id)"
        @keydown.enter="emit('open', entry.id)"
      >
        <p class="audit-cards__meta">{{ formatShortDateTime(entry.createdAt) }} · {{ entry.user.name }}</p>
        <h2 class="audit-cards__title">{{ entry.type.name }}</h2>
        <p class="audit-cards__target">{{ targetText(entry) }}</p>
        <p v-if="changeSummary(entry, catalog)" class="audit-cards__meta">{{ changeSummary(entry, catalog) }}</p>
      </article>
    </li>
  </ul>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.audit-cards {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.audit-cards__card {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    border-color: $color-border-strong;
  }
}

.audit-cards__title,
.audit-cards__target,
.audit-cards__meta {
  margin: 0;
  overflow-wrap: anywhere;
}

.audit-cards__title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.audit-cards__meta {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>

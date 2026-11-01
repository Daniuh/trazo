<!-- Lista agrupada del listado: una sección por grupo con su nombre y su total (con los filtros, no solo la página), que
     se puede plegar. Sin grupo, una sola lista. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import AppIcon from '@/shared/ui/AppIcon.vue'
import { groupSections, type GroupBy } from '../../domain/grouping'
import type { IncidentSummary } from '../../domain/listing'
import IncidentLine from './IncidentLine.vue'

const props = defineProps<{
  items: IncidentSummary[]
  groupBy: GroupBy
  totals: Record<string, number>
  selectedId: string | null
  deleted: boolean
}>()
const emit = defineEmits<{ open: [id: string]; restore: [id: string] }>()

const sections = computed(() => groupSections(props.items, props.groupBy, props.totals))

// Plegadas por clave; al cambiar de criterio se abren todas
const folded = ref(new Set<string>())
watch(
  () => props.groupBy,
  () => (folded.value = new Set()),
)
function toggle(key: string): void {
  const next = new Set(folded.value)
  if (!next.delete(key)) next.add(key)
  folded.value = next
}
</script>

<template>
  <div class="grouped-list">
    <section v-for="section in sections" :key="section.key" class="grouped-list__section">
      <h2 v-if="section.label" class="grouped-list__heading">
        <button
          class="grouped-list__toggle"
          type="button"
          :aria-expanded="!folded.has(section.key)"
          :aria-controls="`grupo-${section.key}`"
          @click="toggle(section.key)"
        >
          <AppIcon
            class="grouped-list__chevron"
            :class="{ 'grouped-list__chevron--folded': folded.has(section.key) }"
            name="chevron-down"
          />
          {{ section.label }}
          <span v-if="section.total !== null" class="grouped-list__count">
            {{ formatNumber(section.total) }}<span class="grouped-list__hidden"> incidentes</span>
          </span>
        </button>
      </h2>
      <ul v-show="!folded.has(section.key)" :id="`grupo-${section.key}`" class="grouped-list__items">
        <li v-for="incident in section.items" :key="incident.id">
          <IncidentLine
            :incident="incident"
            :selected="incident.id === selectedId"
            :deleted="deleted"
            @open="emit('open', $event)"
            @restore="emit('restore', $event)"
          />
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.grouped-list,
.grouped-list__section {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.grouped-list {
  gap: $space-4;
}

// Quasar da a los h2 un alto de línea de 60 px: aquí manda el del texto
.grouped-list__heading {
  margin: 0;
  font-size: $font-size-md;
  line-height: $line-height-tight;
}

.grouped-list__toggle {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  font: inherit;
  font-weight: $font-weight-semibold;
  cursor: pointer;

  &:hover {
    background: $color-surface;
  }
}

.grouped-list__chevron {
  transition: transform $duration-base;
}

.grouped-list__chevron--folded {
  transform: rotate(-90deg);
}

.grouped-list__count {
  padding: 0 $space-2;
  border: 1px solid $color-border;
  border-radius: $radius-pill;
  background: $color-surface;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
}

.grouped-list__hidden {
  @include visually-hidden;
}

// Las filas van juntas en una tarjeta, separadas por una línea
.grouped-list__items {
  overflow: hidden;
  margin: 0;
  padding: 0;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  list-style: none;

  > li + li {
    border-top: 1px solid $color-border;
  }
}
</style>

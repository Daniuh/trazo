<!-- Lista corta de incidentes (próximos a vencer, asignados a mí): código, título, prioridad y cuándo vence.
     Al final, el botón al listado con todos, siempre al pie de la tarjeta. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { OVERDUE_TONE, priorityTone } from '@/modules/incidents/presentation/incidentTones'
import { formatRelative } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { DigestData } from '../../domain/dashboard'

const props = defineProps<{ data: DigestData; empty: string }>()

const more = computed(() => incidentListLocation(props.data.filter))
const due = (iso: string, overdue: boolean) => `${overdue ? 'Venció' : 'Vence'} ${formatRelative(iso)}`
</script>

<template>
  <p v-if="data.items.length === 0" class="digest__empty">{{ empty }}</p>
  <div v-else class="digest-widget">
    <ul class="digest">
      <li v-for="item in data.items" :key="item.id">
        <RouterLink :to="{ name: 'incident-detail', params: { id: item.id } }" class="digest__item">
          <span class="digest__title"
            ><span class="digest__code">{{ item.code }}</span> {{ item.title }}</span
          >
          <span class="digest__meta">
            <AppDot :tone="priorityTone(item.priority.code)">{{ item.priority.name }}</AppDot>
            <AppBadge v-if="item.isOverdue" :tone="OVERDUE_TONE">Vencido</AppBadge>
            <span>{{ due(item.dueDate, item.isOverdue) }}</span>
          </span>
        </RouterLink>
      </li>
    </ul>
    <RouterLink v-if="data.total > data.items.length" :to="more" class="digest__more">
      <span>Ver los {{ formatNumber(data.total) }} en el listado</span>
      <AppIcon name="chevron-right" size="sm" />
    </RouterLink>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// La lista se reparte el alto de la tarjeta y el botón queda siempre al pie
.digest-widget {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.digest {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: space-around;
  margin: 0;
  padding: 0;
  list-style: none;
}

.digest__item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: $space-1 $space-2;
  border-radius: $radius-control;
  color: $color-text;
  text-decoration: none;

  &:hover,
  &:focus-visible {
    background: $color-page;
  }
}

.digest__title {
  overflow: hidden;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.digest__code {
  color: $color-text-secondary;
  font-weight: $font-weight-regular;
}

.digest__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

// Botón al pie: a lo ancho, con la flecha a la derecha, separado de la lista por un fondo suave
.digest__more {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-page;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  text-decoration: none;
  transition:
    border-color $duration-fast,
    background $duration-fast;

  &:hover,
  &:focus-visible {
    border-color: $color-primary;
    background: $color-surface;
  }
}

.digest__empty {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>

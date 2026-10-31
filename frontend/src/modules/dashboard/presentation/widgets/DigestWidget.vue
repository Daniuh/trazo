<!-- Lista corta de incidentes (próximos a vencer, asignados a mí): código, título, prioridad y cuándo vence.
     Al final, el enlace al listado con todos. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { OVERDUE_TONE, priorityTone } from '@/modules/incidents/presentation/incidentTones'
import { formatRelative } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { DigestData } from '../../domain/dashboard'

const props = defineProps<{ data: DigestData; empty: string }>()

const more = computed(() => incidentListLocation(props.data.filter))
const due = (iso: string, overdue: boolean) => `${overdue ? 'Venció' : 'Vence'} ${formatRelative(iso)}`
</script>

<template>
  <p v-if="data.items.length === 0" class="digest__empty">{{ empty }}</p>
  <template v-else>
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
      Ver los {{ formatNumber(data.total) }} en el listado
    </RouterLink>
  </template>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.digest {
  display: flex;
  flex-direction: column;
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

.digest__more {
  display: inline-block;
  margin-top: $space-2;
  padding: $space-1 $space-2;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.digest__empty {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>

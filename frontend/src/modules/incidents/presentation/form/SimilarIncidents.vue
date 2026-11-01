<!-- "¿Ya lo reportaron?": hasta 3 incidentes parecidos al título, con su estado. Cada uno se abre en otra pestaña para no
     perder lo que se está escribiendo. Solo aparece cuando hay parecidos. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import type { IncidentSummary } from '../../domain/listing'
import { statusTone } from '../incidentTones'

defineProps<{ items: IncidentSummary[]; total: number }>()
const router = useRouter()

const href = (id: string) => router.resolve({ name: 'incident-detail', params: { id } }).href
</script>

<template>
  <section v-if="items.length > 0" class="similar" aria-labelledby="similar-title" aria-live="polite">
    <h2 id="similar-title" class="similar__title">
      ¿Ya lo reportaron?
      <span class="similar__count">{{ total === 1 ? '1 parecido' : `${formatNumber(total)} parecidos` }}</span>
    </h2>
    <ul class="similar__list">
      <li v-for="incident in items" :key="incident.id" class="similar__item">
        <span class="similar__code">{{ incident.code }}</span>
        <a class="similar__link" :href="href(incident.id)" target="_blank" rel="noopener">
          {{ incident.title }}<span class="similar__hidden"> (se abre en otra pestaña)</span>
        </a>
        <AppBadge :tone="statusTone(incident.status.code)">{{ incident.status.name }}</AppBadge>
      </li>
    </ul>
    <p class="similar__note">Si es el mismo problema, escríbelo en su conversación en vez de crear otro.</p>
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativa: el texto oculto de cada enlace queda dentro del recuadro
.similar {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-page;
}

.similar__title {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: $space-2;
  margin: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.similar__count {
  color: $color-text-secondary;
  font-weight: $font-weight-regular;
}

.similar__list {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin: 0;
  padding: 0;
  list-style: none;
}

.similar__item {
  display: flex;
  align-items: center;
  gap: $space-2;
  min-width: 0;
  font-size: $font-size-sm;
}

.similar__code {
  flex: none;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-variant-numeric: tabular-nums;
}

.similar__link {
  flex: 1;
  overflow: hidden;
  min-width: 0;
  color: $color-primary;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.similar__note {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.similar__hidden {
  @include visually-hidden;
}
</style>

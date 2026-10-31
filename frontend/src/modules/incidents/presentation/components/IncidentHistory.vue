<!-- Historial del incidente en forma de línea de tiempo, de lo más reciente a lo más antiguo. -->
<!-- Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { formatRelative } from '@/shared/format/dates'
import AppCard from '@/shared/ui/AppCard.vue'
import type { Activity } from '../../domain/incident'
import { describeActivity } from '../historyText'

defineProps<{ activities: Activity[] }>()
</script>

<template>
  <AppCard title="Historial">
    <ol class="incident-history">
      <li v-for="(activity, index) in activities" :key="index" class="incident-history__item">
        <p class="incident-history__text">{{ describeActivity(activity).text }}</p>
        <p v-if="describeActivity(activity).detail" class="incident-history__detail">
          {{ describeActivity(activity).detail }}
        </p>
        <time class="incident-history__time" :datetime="activity.createdAt">
          {{ formatRelative(activity.createdAt) }}
        </time>
      </li>
    </ol>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-history {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.incident-history__item {
  padding-left: $space-3;
  border-left: 2px solid $color-border;
}

.incident-history__text,
.incident-history__detail {
  margin: 0;
}

.incident-history__detail,
.incident-history__time {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>

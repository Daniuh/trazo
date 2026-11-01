<!-- Mes del calendario en portátil y monitor: una tabla de lunes a domingo; cada día muestra hasta 3 incidentes por su
     vencimiento (punto de prioridad, código y título) y "+N más" abre el día completo. Hoy lleva el número en un círculo
     azul. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import AppDot from '@/shared/ui/AppDot.vue'
import type { CalendarDay } from '../../domain/calendar'
import { priorityTone } from '../incidentTones'

defineProps<{ weeks: CalendarDay[][]; caption: string }>()
const emit = defineEmits<{ day: [day: CalendarDay] }>()

const MAX_PER_DAY = 3
const WEEKDAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']
const dayName = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' })
</script>

<template>
  <table class="month">
    <caption class="month__caption">
      {{
        caption
      }}
    </caption>
    <thead>
      <tr>
        <th v-for="name in WEEKDAYS" :key="name" scope="col" class="month__weekday">
          <abbr :title="name">{{ name.slice(0, 3) }}</abbr>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="week in weeks" :key="week[0]?.key">
        <td
          v-for="day in week"
          :key="day.key"
          class="month__day"
          :class="{ 'month__day--out': !day.inMonth, 'month__day--today': day.isToday }"
        >
          <span class="month__number" :aria-label="dayName.format(day.date)">{{ day.date.getDate() }}</span>
          <ul v-if="day.items.length > 0" class="month__items">
            <li v-for="item in day.items.slice(0, MAX_PER_DAY)" :key="item.id">
              <RouterLink
                class="month__item"
                :to="{ name: 'incident-detail', params: { id: item.id } }"
                :title="`${item.code}: ${item.title}`"
              >
                <AppDot :tone="priorityTone(item.priority.code)">
                  <span class="month__item-text">{{ item.code }} · {{ item.title }}</span>
                </AppDot>
              </RouterLink>
            </li>
          </ul>
          <button v-if="day.items.length > MAX_PER_DAY" class="month__more" type="button" @click="emit('day', day)">
            +{{ day.items.length - MAX_PER_DAY }} más
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.month {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  border: 1px solid $color-border;
  background: $color-surface;
}

.month__caption {
  @include visually-hidden;
}

.month__weekday {
  padding: $space-2;
  border-bottom: 1px solid $color-border;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
  text-align: left;

  abbr {
    text-decoration: none;
  }
}

.month__day {
  height: $calendar-cell-min-height;
  padding: $space-2;
  border: 1px solid $color-border;
  vertical-align: top;
}

// Días de los meses vecinos: se ven, pero apagados
.month__day--out {
  background: $color-page;

  .month__number {
    color: $color-text-secondary;
  }
}

.month__number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: $avatar-size;
  height: $avatar-size;
  font-size: $font-size-sm;
}

// Hoy: el número en un círculo azul oscuro de marca con texto blanco (5:1)
.month__day--today .month__number {
  border-radius: 50%;
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-semibold;
}

.month__items {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  margin: $space-1 0 0;
  padding: 0;
  list-style: none;
}

.month__item {
  display: block;
  overflow: hidden;
  color: $color-text;
  font-size: $font-size-xs;
  text-decoration: none;
  white-space: nowrap;

  &:hover {
    text-decoration: underline;
  }
}

// El punto de prioridad con el texto en una línea que se corta si no cabe en el día
.month__item :deep(.app-dot) {
  max-width: 100%;
  font-size: $font-size-xs;
}

// El punto no se encoge aunque el texto no quepa
.month__item :deep(.app-dot__mark) {
  flex: none;
}

.month__item-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.month__more {
  margin-top: $space-1;
  padding: 0;
  border: 0;
  background: none;
  color: $color-primary;
  font: inherit;
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
  cursor: pointer;

  &:hover {
    text-decoration: underline;
  }
}
</style>

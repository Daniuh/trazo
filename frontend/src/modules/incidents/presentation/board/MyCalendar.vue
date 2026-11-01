<!-- Vista Calendario de "Mis incidentes": cada incidente en el día en que vence. Arriba, el mes con "‹ ›" y "Hoy" (el
     mes va en la URL). Portátil y monitor: el mes completo (CalendarMonthGrid); "+N más" abre el día en un panel.
     Celular y tablet: la agenda, solo los días del mes que tienen algo. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useCalendarMonth } from '../../application/useCalendarMonth'
import { agendaDays, monthGrid, monthOf, type CalendarDay } from '../../domain/calendar'
import type { Incident } from '../../domain/incident'
import IncidentRow from './IncidentRow.vue'
import CalendarMonthGrid from './CalendarMonthGrid.vue'

const props = defineProps<{ items: Incident[] }>()

const { isLaptopUp } = useViewport()
const calendar = useCalendarMonth()
const now = new Date()

const monthName = new Intl.DateTimeFormat('es-CO', { month: 'long', year: 'numeric' })
const dayName = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' })
const title = computed(() => monthName.format(new Date(calendar.month.value.year, calendar.month.value.month - 1, 1)))
const isCurrent = computed(() => {
  const current = monthOf(now)
  return current.year === calendar.month.value.year && current.month === calendar.month.value.month
})

const weeks = computed(() => monthGrid(calendar.month.value, props.items, now))
const agenda = computed(() => agendaDays(calendar.month.value, props.items, now))

// "+N más": el día completo en un panel
const openDay = ref<CalendarDay | null>(null)
const dayOpen = computed({
  get: () => openDay.value !== null,
  set: (open) => {
    if (!open) openDay.value = null
  },
})
</script>

<template>
  <section class="my-calendar" aria-label="Calendario por vencimiento">
    <div class="my-calendar__bar">
      <div class="my-calendar__nav">
        <button class="my-calendar__step" type="button" aria-label="Mes anterior" @click="calendar.previous">
          <AppIcon name="chevron-left" />
        </button>
        <h2 class="my-calendar__title" aria-live="polite">{{ title }}</h2>
        <button class="my-calendar__step" type="button" aria-label="Mes siguiente" @click="calendar.next">
          <AppIcon name="chevron-right" />
        </button>
      </div>
      <AppButton variant="secondary" :disabled="isCurrent" @click="calendar.today">Hoy</AppButton>
    </div>

    <CalendarMonthGrid v-if="isLaptopUp" :weeks="weeks" :caption="`Vencimientos de ${title}`" @day="openDay = $event" />

    <template v-else>
      <AppEmptyState
        v-if="agenda.length === 0"
        title="Nada vence este mes"
        description="Cambia de mes con las flechas para ver otros vencimientos."
      />
      <ol v-else class="my-calendar__agenda">
        <li v-for="day in agenda" :key="day.key" class="my-calendar__day">
          <h3 class="my-calendar__day-name">
            <span class="my-calendar__day-text">{{ dayName.format(day.date) }}</span>
            <span v-if="day.isToday" class="my-calendar__today">Hoy</span>
          </h3>
          <ul class="my-calendar__rows">
            <li v-for="item in day.items" :key="item.id"><IncidentRow :item="item" time-only /></li>
          </ul>
        </li>
      </ol>
    </template>

    <AppSheet v-model:open="dayOpen" :title="openDay ? dayName.format(openDay.date) : ''">
      <ul v-if="openDay" class="my-calendar__rows">
        <li v-for="item in openDay.items" :key="item.id"><IncidentRow :item="item" time-only /></li>
      </ul>
    </AppSheet>
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.my-calendar {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.my-calendar__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.my-calendar__nav {
  display: flex;
  align-items: center;
  gap: $space-1;
}

.my-calendar__step {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: $touch-target;
  height: $touch-target;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  cursor: pointer;

  &:hover {
    background: $color-surface;
  }
}

// "octubre de 2026": con mayúscula inicial y ancho fijo para que las flechas no salten entre meses
.my-calendar__title {
  min-width: $calendar-title-width;
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  text-align: center;

  &::first-letter {
    text-transform: uppercase;
  }
}

.my-calendar__agenda,
.my-calendar__rows {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  list-style: none;
}

.my-calendar__agenda {
  gap: $space-5;
}

.my-calendar__rows {
  gap: $space-2;
}

.my-calendar__day-name {
  display: flex;
  align-items: center;
  gap: $space-2;
  margin: 0 0 $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

// "Sábado, 3 de octubre": la mayúscula inicial solo funciona en un bloque, no dentro del flex del título
.my-calendar__day-text {
  display: inline-block;

  &::first-letter {
    text-transform: uppercase;
  }
}

// "Hoy" junto al día: fondo azul oscuro de marca con texto blanco (5:1)
.my-calendar__today {
  padding: 0 $space-2;
  border-radius: $radius-badge;
  background: $color-primary;
  color: $color-on-dark;
  font-size: $font-size-xs;
}
</style>

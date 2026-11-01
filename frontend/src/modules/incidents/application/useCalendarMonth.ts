// Mes que muestra el calendario de "Mis incidentes": va en la URL (?mes=2026-10) para compartirlo y volver a él; sin
// mes, el actual. Documentado en docs/frontend/incidents.md
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { monthKey, monthOf, parseMonth, shiftMonth, type CalendarMonth } from '../domain/calendar'

export function useCalendarMonth(now: () => Date = () => new Date()) {
  const route = useRoute()
  const router = useRouter()

  const month = computed<CalendarMonth>(() => parseMonth(route.query.mes) ?? monthOf(now()))

  function show(next: CalendarMonth): void {
    void router.replace({ query: { ...route.query, mes: monthKey(next) } })
  }

  return {
    month,
    previous: () => show(shiftMonth(month.value, -1)),
    next: () => show(shiftMonth(month.value, 1)),
    today: () => show(monthOf(now())),
  }
}

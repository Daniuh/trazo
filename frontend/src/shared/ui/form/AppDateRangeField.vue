<!-- Rango de fechas (sin hora), para filtros: "1 oct – 22 oct". Toma días completos en la hora local:
     desde el inicio del primer día hasta el final del último. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDate } from 'quasar'
import { computed, ref, watch } from 'vue'
import AppButton from '../AppButton.vue'
import AppPopupField from './AppPopupField.vue'
import { dayBounds, rangeLabel, toDay } from './dateRange'

const props = defineProps<{ label: string; placeholder?: string }>()
const from = defineModel<string | null>('from', { default: null })
const to = defineModel<string | null>('to', { default: null })

const open = ref(false)
type QDateRange = string | { from: string; to: string } | null
const draft = ref<QDateRange>(null)

const empty = computed(() => !from.value && !to.value)
const text = computed(() => (empty.value ? (props.placeholder ?? '') : rangeLabel(from.value, to.value)))

// Al abrir, el calendario muestra el rango vigente
watch(open, (isOpen) => {
  if (!isOpen) return
  const start = from.value ? toDay(from.value) : null
  const end = to.value ? toDay(to.value) : null
  draft.value = start && end && start !== end ? { from: start, to: end } : (start ?? end)
})

function apply(): void {
  const range = draft.value
  const [start, end] = typeof range === 'string' ? [range, range] : range ? [range.from, range.to] : [null, null]
  from.value = start ? dayBounds(start).start : null
  to.value = end ? dayBounds(end).end : null
  open.value = false
}

function clear(): void {
  from.value = null
  to.value = null
  open.value = false
}
</script>

<template>
  <AppPopupField v-model:open="open" :label="label" :text="text" :empty="empty" icon="calendar">
    <div class="date-range" role="dialog" :aria-label="label">
      <p class="date-range__title">{{ label }}</p>
      <QDate v-model="draft" class="date-range__calendar" mask="YYYY/MM/DD" range minimal flat first-day-of-week="1" />
      <div class="date-range__footer">
        <AppButton variant="link" @click="clear">Limpiar</AppButton>
        <div class="date-range__actions">
          <AppButton variant="secondary" @click="open = false">Cancelar</AppButton>
          <AppButton @click="apply">Listo</AppButton>
        </div>
      </div>
    </div>
  </AppPopupField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.date-range {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  padding: $space-4;
  background: $color-surface;
}

.date-range__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.date-range__calendar {
  width: 100%;
  background: transparent;

  :deep(.q-date__calendar-item--today .q-btn) {
    box-shadow: inset 0 0 0 1px $color-primary;
  }
}

.date-range__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
  padding-top: $space-3;
  border-top: 1px solid $color-border;
}

.date-range__actions {
  display: flex;
  gap: $space-2;
}

@include laptop {
  .date-range__calendar {
    width: $datetime-calendar-width;
  }
}
</style>

<!-- Contenido del selector de fecha y hora: calendario, horas cada 30 minutos y acciones.
     Lo usan el menú (portátil y monitor) y el panel inferior (celular y tablet). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDate } from 'quasar'
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue'
import AppButton from '../AppButton.vue'
import { isPast, isPastDay, nextSlot, roundUp, slotsWith, toIso, toParts, type DateTimeParts } from './dateTimeParts'

const props = defineProps<{ title: string; value: string | null; suggestion: string | null; clearLabel?: string }>()
const emit = defineEmits<{ confirm: [iso: string]; clear: []; cancel: [] }>()

// Abre en el valor guardado (aunque ya haya vencido); si no hay, en la sugerencia redondeada a la media hora,
// y si la sugerencia quedó atrás (el formulario llevaba rato abierto), en el siguiente horario libre
function initial(): DateTimeParts {
  if (props.value) return toParts(props.value)
  const suggested = props.suggestion ? roundUp(new Date(props.suggestion)) : nextSlot()
  return isPast(suggested) ? nextSlot() : suggested
}

const start = initial()
const date = ref(start.date)
const time = ref(start.time)
const slots = slotsWith(props.value ? toParts(props.value).time : null)
const list = useTemplateRef<HTMLElement>('list')

const isPastSlot = (slot: string) => isPast({ date: date.value, time: slot })
const canConfirm = computed(() => !isPast({ date: date.value, time: time.value }))

// Al pasar a hoy con una hora ya vencida, se salta al siguiente horario libre
watch(date, () => {
  if (isPastSlot(time.value)) time.value = slots.find((slot) => !isPastSlot(slot)) ?? time.value
})

function label(slot: string): string {
  const [hours, minutes] = slot.split(':').map(Number) as [number, number]
  return new Intl.DateTimeFormat('es-CO', { hour: 'numeric', minute: '2-digit' }).format(
    new Date(2000, 0, 1, hours, minutes),
  )
}

// La hora elegida queda a la vista sin tener que buscarla (sin mover el resto de la página)
onMounted(async () => {
  await nextTick()
  const container = list.value
  const selected = container?.querySelector<HTMLElement>('[aria-selected="true"]')
  if (!container || !selected) return
  container.scrollTop = selected.offsetTop - container.clientHeight / 2
  container.scrollLeft = selected.offsetLeft - container.clientWidth / 2
})
</script>

<template>
  <div class="datetime-panel" role="dialog" :aria-label="title">
    <p class="datetime-panel__title">{{ title }}</p>

    <div class="datetime-panel__body">
      <QDate
        v-model="date"
        class="datetime-panel__calendar"
        mask="YYYY/MM/DD"
        :options="(day: string) => !isPastDay(day)"
        minimal
        flat
        first-day-of-week="1"
      />

      <div class="datetime-panel__times">
        <p class="datetime-panel__subtitle">Hora</p>
        <div ref="list" class="datetime-panel__slots" role="listbox" aria-label="Hora">
          <button
            v-for="slot in slots"
            :key="slot"
            class="datetime-panel__slot"
            :class="{ 'datetime-panel__slot--selected': slot === time }"
            type="button"
            role="option"
            :aria-selected="slot === time"
            :disabled="isPastSlot(slot)"
            @click="time = slot"
          >
            {{ label(slot) }}
          </button>
        </div>
      </div>
    </div>

    <div class="datetime-panel__footer">
      <AppButton v-if="clearLabel" variant="link" @click="emit('clear')">{{ clearLabel }}</AppButton>
      <div class="datetime-panel__actions">
        <AppButton variant="secondary" @click="emit('cancel')">Cancelar</AppButton>
        <AppButton :disabled="!canConfirm" @click="emit('confirm', toIso({ date, time }))">Listo</AppButton>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.datetime-panel {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  padding: $space-4;
  background: $color-surface;
}

.datetime-panel__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.datetime-panel__subtitle {
  margin: 0 0 $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
}

.datetime-panel__body {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

// El calendario toma la superficie de Trazo; hoy se marca con borde y el día elegido con fondo azul
.datetime-panel__calendar {
  width: 100%;
  background: transparent;

  :deep(.q-date__calendar-item--today .q-btn) {
    box-shadow: inset 0 0 0 1px $color-primary;
  }
}

// En pantallas chicas las horas van en una fila que se desliza
.datetime-panel__slots {
  position: relative;
  display: flex;
  gap: $space-2;
  overflow-x: auto;
  padding-bottom: $space-1;
}

.datetime-panel__slot {
  flex-shrink: 0;
  min-height: $touch-target;
  padding: 0 $space-3;
  border: 1px solid $color-border-strong;
  border-radius: $radius-control;
  background: $color-surface;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  white-space: nowrap;
  cursor: pointer;

  &:hover:not(:disabled) {
    background: $color-page;
  }

  &:disabled {
    color: $color-muted;
    cursor: not-allowed;
  }
}

.datetime-panel__slot--selected {
  border-color: $color-primary;
  background: $color-primary;
  color: $color-on-dark;

  &:hover:not(:disabled) {
    background: $color-primary;
  }
}

.datetime-panel__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
  padding-top: $space-3;
  border-top: 1px solid $color-border;
}

.datetime-panel__actions {
  display: flex;
  flex: 1;
  justify-content: flex-end;
  gap: $space-2;
}

// En el menú de portátil y monitor: calendario y horas lado a lado, con las horas en columna
@include laptop {
  .datetime-panel__body {
    flex-direction: row;
  }

  .datetime-panel__calendar {
    width: $datetime-calendar-width;
  }

  .datetime-panel__times {
    padding-left: $space-3;
    border-left: 1px solid $color-border;
  }

  .datetime-panel__slots {
    flex-direction: column;
    overflow: hidden auto;
    max-height: $datetime-slots-height;
    padding-right: $space-1;
  }

  .datetime-panel__slot {
    min-height: auto;
    padding: $space-1 $space-3;
    border-color: transparent;
    text-align: left;
  }
}
</style>

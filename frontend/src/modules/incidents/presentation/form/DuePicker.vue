<!-- Vencimiento con atajos: "Sugerido" (solo al crear; lo calcula la API con la prioridad), "Hoy" y "Mañana" al cierre
     de la jornada, y "Otra fecha", que abre el selector de siempre. Debajo, la fecha exacta.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { formatDateTime } from '@/shared/format/dates'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppDateTimeField from '@/shared/ui/form/AppDateTimeField.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import { choiceFor, dueChoices, dueDateFor, type DueChoice } from '../../domain/reporting'

const props = defineProps<{
  creating: boolean
  // Lo que calcularía la API con la prioridad elegida, y cuánto es ("3 días")
  suggestion: string | null
  resolution: string | null
  error?: string
}>()
const model = defineModel<string | null>({ required: true })

const LABELS: Record<DueChoice, string> = {
  suggested: 'Sugerido',
  today: 'Hoy',
  tomorrow: 'Mañana',
  other: 'Otra fecha',
}

const now = new Date()
const options = dueChoices(now, props.creating).map((value) => ({ label: LABELS[value], value }))
const choice = ref<DueChoice>(choiceFor(model.value, now, props.creating))

watch(choice, (value) => {
  if (value === 'suggested') model.value = null
  else if (value !== 'other') model.value = dueDateFor(value, now)
})

const exact = computed(() => {
  if (choice.value === 'suggested') {
    return props.suggestion ? `${formatDateTime(props.suggestion)} · en ${props.resolution}` : null
  }
  return choice.value === 'other' || model.value === null ? null : formatDateTime(model.value)
})
</script>

<template>
  <div class="due-picker">
    <div class="due-picker__label">
      <span>Vence</span>
      <AppInfoTip
        v-if="creating"
        label="Sobre el vencimiento"
        text="El sugerido sale del tiempo de resolución de la prioridad. Hoy y mañana vencen a las 6:00 p. m."
      />
    </div>
    <AppSegmented v-model="choice" label="Cuándo vence" :options="options" />
    <p v-if="exact" class="due-picker__exact">{{ exact }}</p>
    <AppDateTimeField
      v-if="choice === 'other'"
      v-model="model"
      label="Fecha y hora"
      :suggestion="suggestion"
      :error="error"
    />
    <p v-else-if="error" class="due-picker__error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.due-picker {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-2;
}

.due-picker__label {
  display: flex;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.due-picker__exact {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.due-picker__error {
  margin: 0;
  color: $color-danger;
  font-size: $font-size-xs;
}

// El selector de fecha ocupa el ancho disponible, como cualquier campo
.due-picker > :deep(.app-field) {
  align-self: stretch;
}
</style>

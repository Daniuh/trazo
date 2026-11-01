<!-- Guía visual: campos de formulario agregados después de la base (casillas, chips, lista de opciones, control
     segmentado, fecha y hora, rango de fechas y código de 6 dígitos). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppChoiceChips from '@/shared/ui/form/AppChoiceChips.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'
import AppDateRangeField from '@/shared/ui/form/AppDateRangeField.vue'
import AppDateTimeField from '@/shared/ui/form/AppDateTimeField.vue'
import AppRadioList from '@/shared/ui/form/AppRadioList.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'

const overdue = ref(true)
const statuses = ref(['open', 'in_progress'])
const role = ref<string | null>('agent')
const view = ref('pending')
const dueDate = ref<string | null>(null)
const from = ref<string | null>(null)
const to = ref<string | null>(null)
const code = ref('')
</script>

<template>
  <AppCard title="Más campos">
    <div class="guide-form">
      <AppCheckbox v-model="overdue" label="Solo vencidos" />
      <AppCheckbox :model-value="false" indeterminate label="Todos (a medias)" />
      <AppChoiceChips
        v-model="statuses"
        label="Estado"
        :options="[
          { label: 'Abierto', value: 'open' },
          { label: 'En progreso', value: 'in_progress' },
          { label: 'En espera', value: 'on_hold' },
        ]"
      />
      <AppRadioList
        v-model="role"
        label="Rol"
        :options="[
          { value: 'agent', label: 'Agente', description: 'Atiende los incidentes que tiene asignados.' },
          { value: 'supervisor', label: 'Supervisor', description: 'Ve todos los incidentes y los asigna.' },
        ]"
      />
      <AppSegmented
        v-model="view"
        label="Qué eventos ver"
        :options="[
          { label: 'Pendientes', value: 'pending' },
          { label: 'Resueltos', value: 'resolved' },
          { label: 'Todos', value: 'all' },
        ]"
      />
      <AppDateTimeField v-model="dueDate" label="Vencimiento" placeholder="Sin fecha" />
      <AppDateRangeField v-model:from="from" v-model:to="to" label="Creación" placeholder="Cualquier fecha" />
      <AppCodeField v-model="code" label="Código de la aplicación" />
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.guide-form {
  display: grid;
  gap: $space-4;
}

@include laptop {
  .guide-form {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
  }
}
</style>

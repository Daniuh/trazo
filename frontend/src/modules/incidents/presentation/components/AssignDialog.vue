<!-- Diálogo para asignar, reasignar o dejar sin asignar un incidente. Devuelve el id elegido (o null). -->
<!-- Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { QDialog, useDialogPluginComponent } from 'quasar'
import { computed, onMounted, ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import type { SelectOption } from '@/shared/ui/form/types'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import { useAssignees } from '../../application/useAssignees'
import type { PersonRef } from '../../domain/incident'

// Valor propio para 'Sin asignar': Quasar trata el texto vacío como un campo sin valor
const UNASSIGNED = 'none'

const props = defineProps<{ current: PersonRef | null }>()
defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } = useDialogPluginComponent()
defineExpose({ dialogRef })

const { people, loading, error, load } = useAssignees()
const selected = ref<string>(props.current?.id ?? UNASSIGNED)

const options = computed<SelectOption<string>[]>(() => [
  { label: 'Sin asignar', value: UNASSIGNED },
  // Mientras llega la lista, la persona actual ya aparece con su nombre
  ...(people.value.length > 0 ? people.value : props.current ? [props.current] : []).map((person) => ({
    label: person.name,
    value: person.id,
  })),
])

// Guardar sin cambiar nada no tiene sentido
const choice = computed(() => (selected.value === UNASSIGNED ? null : selected.value))
const changed = computed(() => choice.value !== (props.current?.id ?? null))

onMounted(load)
</script>

<template>
  <QDialog ref="dialogRef" @hide="onDialogHide">
    <div class="assign-dialog" role="dialog" aria-modal="true" aria-labelledby="assign-title">
      <h2 id="assign-title" class="assign-dialog__title">Asignar incidente</h2>
      <AppErrorState v-if="error" :error="error" @retry="load" />
      <AppSelect
        v-else
        v-model="selected"
        label="Asignar a"
        :options="options"
        :placeholder="loading ? 'Cargando…' : undefined"
      />
      <div class="assign-dialog__actions">
        <AppButton variant="secondary" @click="onDialogCancel">Cancelar</AppButton>
        <AppButton :disabled="loading || Boolean(error) || !changed" @click="onDialogOK(choice)">Guardar</AppButton>
      </div>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.assign-dialog {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  width: min(28rem, calc(100vw - #{$space-6}));
  padding: $space-5;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

.assign-dialog__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

// En celular los botones se apilan, con la acción principal arriba
.assign-dialog__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .assign-dialog__actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}
</style>

<!-- Guía visual: avisos y capas (avisos de página, panel lateral o inferior, panel de filtros, diálogo de formulario y
     diálogo del código de reautenticación). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppFilterSheet from '@/shared/ui/AppFilterSheet.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'
import AppFormDialog from '@/shared/ui/feedback/AppFormDialog.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'

const { notify } = useFeedback()

const sheetOpen = ref(false)
const filtersOpen = ref(false)
const formOpen = ref(false)
const codeOpen = ref(false)
const name = ref('Laura Gómez')
const overdue = ref(false)

// El diálogo del código de ejemplo acepta cualquier código de 6 dígitos
async function confirmCode(): Promise<void> {
  notify('Código aceptado.')
}
</script>

<template>
  <AppCard title="Avisos">
    <div class="guide-stack">
      <AppAlert tone="info">
        Hay 3 incidentes con cambios de otras personas.
        <AppButton variant="link" icon="refresh">Actualizar</AppButton>
      </AppAlert>
      <AppAlert>No pudimos guardar: revisa los campos marcados.</AppAlert>
    </div>
  </AppCard>

  <AppCard title="Paneles y diálogos">
    <div class="guide-buttons">
      <AppButton variant="secondary" @click="sheetOpen = true">Panel (derecha o abajo)</AppButton>
      <AppButton variant="secondary" icon="filter" @click="filtersOpen = true">Panel de filtros</AppButton>
      <AppButton variant="secondary" @click="formOpen = true">Diálogo de formulario</AppButton>
      <AppButton variant="secondary" @click="codeOpen = true">Diálogo del código</AppButton>
    </div>
  </AppCard>

  <AppSheet v-model:open="sheetOpen" title="Detalle del registro">
    <p>En portátil y monitor el panel sale por la derecha; en tablet y celular, desde abajo.</p>
  </AppSheet>

  <AppFilterSheet v-model:open="filtersOpen" @apply="filtersOpen = false" @clear="overdue = false">
    <AppCheckbox v-model="overdue" label="Solo vencidos" />
  </AppFilterSheet>

  <AppFormDialog
    v-model="formOpen"
    title="Editar datos"
    message="El cambio se avisa a la persona por correo."
    submit-label="Guardar"
    @submit="formOpen = false"
  >
    <AppTextField v-model="name" label="Nombre" required />
  </AppFormDialog>

  <AppCodeDialog
    v-model="codeOpen"
    title="Confirma que eres tú"
    message="Escribe el código de tu aplicación de autenticación para confirmar."
    confirm-label="Confirmar"
    :action="confirmCode"
  />
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.guide-stack {
  display: grid;
  gap: $space-3;
}

.guide-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: $space-3;
}
</style>

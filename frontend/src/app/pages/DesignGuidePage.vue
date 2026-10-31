<!-- Guía visual (solo en desarrollo): muestra cada componente base y sus estados para revisarlos en las 4 vistas.
     Las secciones más nuevas viven en design-guide/ para que el archivo no crezca. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref } from 'vue'
import { icons, type IconName } from '@/design/icons/icons'
import { ApiError, ClientErrorCode } from '@/shared/http'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppLogo from '@/shared/ui/brand/AppLogo.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import type { Tone } from '@/shared/ui/tones'
import GuideDataSection from './design-guide/GuideDataSection.vue'
import GuideFormSection from './design-guide/GuideFormSection.vue'
import GuideOverlaySection from './design-guide/GuideOverlaySection.vue'

const { confirm, notify, notifyError } = useFeedback()

const iconNames = Object.keys(icons) as IconName[]
const statuses: [string, Tone][] = [
  ['Abierto', 'blue'],
  ['En progreso', 'orange'],
  ['En espera', 'neutral'],
  ['Resuelto', 'green'],
  ['Cerrado', 'dark'],
  ['Cancelado', 'muted'],
]
const priorities: [string, Tone][] = [
  ['Baja', 'green'],
  ['Media', 'blue'],
  ['Alta', 'orange'],
  ['Crítica', 'danger'],
]

const title = ref('No carga el módulo de facturación')
const email = ref('ana@empresa')
const priority = ref<string | null>('high')
const saving = ref(false)
const networkError = new ApiError('network', ClientErrorCode.Network)

async function askDelete() {
  const ok = await confirm({
    title: '¿Eliminar TZ-26-0123?',
    message: 'El incidente dejará de aparecer en los listados. Un supervisor puede restaurarlo.',
    confirmLabel: 'Eliminar',
    danger: true,
  })
  if (ok) notify('Incidente TZ-26-0123 eliminado')
}

function simulateSave() {
  saving.value = true
  setTimeout(() => (saving.value = false), 1500)
}
</script>

<template>
  <div class="guide">
    <h1 class="guide__title">Guía visual</h1>

    <AppCard title="Marca">
      <div class="guide__row guide__logo"><AppLogo /> <AppLogo spinning /></div>
    </AppCard>

    <AppCard title="Íconos">
      <div class="guide__icons">
        <span v-for="name in iconNames" :key="name" class="guide__icon"
          ><AppIcon :name="name" size="lg" />{{ name }}</span
        >
      </div>
    </AppCard>

    <AppCard title="Botones">
      <div class="guide__row">
        <AppButton icon="plus">Crear incidente</AppButton>
        <AppButton variant="secondary">Cancelar</AppButton>
        <AppButton variant="danger" icon="trash" @click="askDelete">Eliminar</AppButton>
        <AppButton variant="link">Ver historial</AppButton>
        <AppButton :loading="saving" @click="simulateSave">{{ saving ? 'Guardando…' : 'Guardar' }}</AppButton>
        <AppButton variant="secondary" icon="audit" :to="{ name: 'design-guide' }"
          >Enlace con aspecto de botón</AppButton
        >
        <AppBackLink :to="{ name: 'design-guide' }" label="Regresar" />
      </div>
    </AppCard>

    <AppCard title="Estados y prioridades">
      <div class="guide__row">
        <AppBadge v-for="[label, tone] in statuses" :key="label" :tone="tone">{{ label }}</AppBadge>
        <AppBadge tone="danger">Vencido</AppBadge>
      </div>
      <div class="guide__row">
        <AppDot v-for="[label, tone] in priorities" :key="label" :tone="tone">{{ label }}</AppDot>
      </div>
    </AppCard>

    <AppCard title="Campos">
      <div class="guide__form">
        <AppTextField v-model="title" label="Título" required :maxlength="150" />
        <AppSelect
          v-model="priority"
          label="Prioridad"
          info="Define qué tan urgente es. Si no indicas fecha de vencimiento, se calcula según la prioridad."
          :options="[
            { label: 'Baja', value: 'low' },
            { label: 'Media', value: 'medium' },
            { label: 'Alta', value: 'high' },
            { label: 'Crítica', value: 'critical' },
          ]"
        />
        <AppTextField
          v-model="email"
          label="Correo"
          type="email"
          error="Escribe un correo válido, por ejemplo nombre@empresa.com."
        />
      </div>
    </AppCard>

    <AppCard title="Mensajes">
      <div class="guide__row">
        <AppButton variant="secondary" @click="notify('Incidente TZ-26-0124 creado')">Notificación</AppButton>
        <AppButton variant="secondary" @click="notifyError(networkError)">Notificación de error</AppButton>
      </div>
    </AppCard>

    <div class="guide__states">
      <AppCard title="Cargando"><AppSkeleton /></AppCard>
      <AppCard title="Vacío">
        <AppEmptyState title="No hay incidentes con estos filtros">
          <AppButton variant="link">Limpiar filtros</AppButton>
        </AppEmptyState>
      </AppCard>
      <AppCard title="Error"><AppErrorState :error="networkError" /></AppCard>
    </div>

    <h2 class="guide__section">Formularios</h2>
    <GuideFormSection />

    <h2 class="guide__section">Datos</h2>
    <GuideDataSection />

    <h2 class="guide__section">Avisos y capas</h2>
    <GuideOverlaySection />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.guide {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.guide__title {
  margin: 0;
  font-size: $font-size-2xl;
  font-weight: $font-weight-semibold;
}

.guide__section {
  margin: $space-4 0 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
}

.guide__row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-3;

  & + & {
    margin-top: $space-3;
  }
}

.guide__logo {
  gap: $space-6;
  font-size: 2.25rem;
}

.guide__icons {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr));
  gap: $space-3;
}

.guide__icon {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $space-1;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.guide__form {
  display: grid;
  gap: $space-4;
}

.guide__states {
  display: grid;
  gap: $space-4;
}

@include laptop {
  .guide__form {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .guide__states {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>

<!-- Solicitud de reapertura pendiente en el detalle y en la vista previa: quién la pidió, cuándo y por qué. Quien puede decidir ve "Aprobar"
     y "Rechazar"; el resto, que está a la espera. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { useId } from 'vue'
import { formatRelative } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { ReopenRequest } from '../../domain/incident'

defineProps<{ request: ReopenRequest; canDecide: boolean; busy?: boolean }>()
const emit = defineEmits<{ approve: []; reject: [] }>()
const titleId = useId()
</script>

<template>
  <section class="reopen-notice" :aria-labelledby="titleId">
    <div class="reopen-notice__heading">
      <AppIcon class="reopen-notice__icon" name="refresh" />
      <h2 :id="titleId" class="reopen-notice__title">Solicitud de reapertura</h2>
    </div>
    <p class="reopen-notice__meta">
      {{ request.requestedBy.name }} pidió reabrirlo
      <time :datetime="request.createdAt">{{ formatRelative(request.createdAt) }}</time>
    </p>
    <blockquote class="reopen-notice__reason">{{ request.reason }}</blockquote>

    <div v-if="canDecide" class="reopen-notice__actions">
      <AppButton variant="secondary" icon="close" :disabled="busy" @click="emit('reject')">Rechazar</AppButton>
      <AppButton icon="check" :loading="busy" @click="emit('approve')">Aprobar y reabrir</AppButton>
    </div>
    <p v-else class="reopen-notice__waiting">Está esperando la respuesta de quien supervisa.</p>
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.reopen-notice {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.reopen-notice__heading {
  display: flex;
  align-items: center;
  gap: $space-2;
}

.reopen-notice__icon {
  color: $color-primary;
}

.reopen-notice__title {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.reopen-notice__meta,
.reopen-notice__waiting {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// El motivo se lee tal como lo escribió la persona, con sus saltos de línea
.reopen-notice__reason {
  margin: 0;
  padding-left: $space-3;
  border-left: 2px solid $color-border;
  white-space: pre-line;
  overflow-wrap: anywhere;
}

// En celular los botones se apilan, con la acción principal arriba
.reopen-notice__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
  margin-top: $space-2;
}

@include tablet {
  .reopen-notice__actions {
    flex-flow: row wrap;
    justify-content: flex-end;
  }
}
</style>

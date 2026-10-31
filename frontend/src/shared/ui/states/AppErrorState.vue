<!-- Estado de error: muestra el mensaje claro del catálogo y permite reintentar. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { messageFor } from '@/shared/errors'
import type { ApiError } from '@/shared/http'
import AppButton from '../AppButton.vue'
import AppIcon from '../AppIcon.vue'

const props = defineProps<{ error: ApiError }>()
defineEmits<{ retry: [] }>()

const message = computed(() => messageFor(props.error))
</script>

<template>
  <div class="app-error" role="alert">
    <AppIcon name="alert" size="lg" class="app-error__icon" />
    <p class="app-error__text">{{ message }}</p>
    <AppButton variant="secondary" icon="refresh" @click="$emit('retry')">Reintentar</AppButton>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $space-3;
  padding: $space-5 $space-4;
  border: 1px solid $color-danger;
  border-radius: $radius-card;
  background: $color-surface;
  text-align: center;
}

.app-error__icon {
  color: $color-danger;
}

.app-error__text {
  max-width: 32rem;
  margin: 0;
}
</style>

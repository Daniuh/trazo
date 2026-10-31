<!-- Página para rutas inexistentes: explica qué pasó y ofrece volver. -->
<script setup lang="ts">
import AppButton from '@/shared/ui/AppButton.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
</script>

<template>
  <AppEmptyState
    title="Esta página no existe"
    description="Puede que el enlace esté mal escrito o que la página se haya movido."
  >
    <RouterLink to="/" custom>
      <template #default="{ navigate }">
        <AppButton icon="home" @click="navigate">Volver al inicio</AppButton>
      </template>
    </RouterLink>
  </AppEmptyState>
</template>

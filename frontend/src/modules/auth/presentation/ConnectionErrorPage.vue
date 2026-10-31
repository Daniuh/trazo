<!-- Sin conexión: la sesión no se pudo comprobar (red caída, mantenimiento o servidor lento), pero tampoco se cierra.
     Dice qué pasó con el mensaje del catálogo y ofrece reintentar, volviendo a la página que se pedía.
     Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { messageFor } from '@/shared/errors'
import { ApiError, ClientErrorCode } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useSessionStore } from '../application/useSessionStore'
import { safeRedirect } from '../domain/safeRedirect'
import AuthPanel from './AuthPanel.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const retrying = ref(false)

// Si se llega aquí sin un error guardado (p. ej. al recargar esta misma página), el mensaje de falta de conexión
const message = computed(() => messageFor(session.connectionError ?? new ApiError('network', ClientErrorCode.Network)))

async function retry(): Promise<void> {
  retrying.value = true
  try {
    // La guarda vuelve a comprobar la sesión; si sigue sin respuesta, esta pantalla se queda con el mensaje al día
    await router.replace(safeRedirect(route.query.volver) ?? { name: 'home' })
  } finally {
    retrying.value = false
  }
}
</script>

<template>
  <AuthPanel title="No pudimos cargar Trazo">
    <div class="connection">
      <AppAlert>{{ message }}</AppAlert>
      <p class="connection__note">Tu sesión sigue abierta: al reintentar vuelves a donde estabas.</p>
      <AppButton icon="refresh" :loading="retrying" block @click="retry">Reintentar</AppButton>
    </div>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.connection {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.connection__note {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>

<!-- Códigos de recuperación recién generados: se muestran una sola vez, con "Copiar todos" y "Descargar .txt".
     Lo usan la configuración del MFA y Mi cuenta. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'

const props = defineProps<{ codes: string[]; account: string }>()

const copied = ref(false)

const asText = () =>
  [
    'Códigos de recuperación de Trazo',
    `Cuenta: ${props.account}`,
    'Cada código sirve una sola vez para ingresar si no tienes tu aplicación de autenticación.',
    '',
    ...props.codes,
  ].join('\n')

async function copyAll(): Promise<void> {
  try {
    await navigator.clipboard.writeText(asText())
    copied.value = true
  } catch {
    // Sin permiso para el portapapeles, los códigos siguen visibles para copiarlos a mano
  }
}

// Se arma en el navegador: los códigos no vuelven a pasar por la red
function download(): void {
  const url = URL.createObjectURL(new Blob([asText()], { type: 'text/plain;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'trazo-codigos-de-recuperacion.txt'
  link.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <section class="recovery-codes" aria-labelledby="recovery-codes-title">
    <h2 id="recovery-codes-title" class="recovery-codes__title">Códigos de recuperación</h2>
    <AppAlert tone="info">
      Guárdalos en un lugar seguro: es la única vez que los verás. Cada uno sirve una sola vez para ingresar si pierdes
      tu teléfono.
    </AppAlert>

    <ol class="recovery-codes__list">
      <li v-for="code in codes" :key="code" class="recovery-codes__code">{{ code }}</li>
    </ol>

    <div class="recovery-codes__actions">
      <AppButton variant="secondary" :icon="copied ? 'check' : 'copy'" @click="copyAll">
        {{ copied ? 'Copiados' : 'Copiar todos' }}
      </AppButton>
      <AppButton variant="secondary" icon="download" @click="download">Descargar .txt</AppButton>
    </div>
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.recovery-codes {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.recovery-codes__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.recovery-codes__list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax($code-column-min-width, 1fr));
  gap: $space-2;
  margin: 0;
  padding: $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-page;
  list-style: none;
}

.recovery-codes__code {
  font-size: $font-size-md;
  font-variant-numeric: tabular-nums;
  letter-spacing: $letter-spacing-code;
  text-align: center;
  white-space: nowrap;
}

.recovery-codes__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}
</style>

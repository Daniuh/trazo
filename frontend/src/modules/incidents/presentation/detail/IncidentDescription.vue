<!-- Descripción del incidente bajo el título: una sola línea. Si no cabe, "Ver más" despliega debajo la descripción
     completa. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, useId } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import { useTextOverflow } from '@/shared/ui/useTextOverflow'

defineProps<{ text: string }>()

const line = ref<HTMLElement | null>(null)
const { overflowing } = useTextOverflow(line)
const open = ref(false)
const fullId = useId()

// Abierta, la línea sigue recortada: el botón se queda para poder cerrarla
const expandable = computed(() => overflowing.value || open.value)
</script>

<template>
  <div class="incident-description">
    <div class="incident-description__summary">
      <p ref="line" class="incident-description__line">{{ text }}</p>
      <AppButton
        v-if="expandable"
        class="incident-description__toggle"
        variant="link"
        :aria-expanded="open"
        :aria-controls="fullId"
        @click="open = !open"
      >
        {{ open ? 'Ver menos' : 'Ver más' }}
      </AppButton>
    </div>
    <p v-if="open" :id="fullId" class="incident-description__full">{{ text }}</p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-description {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  min-width: 0;
}

.incident-description__summary {
  display: flex;
  align-items: baseline;
  gap: $space-2;
  min-width: 0;
}

// Una sola línea: lo que no cabe termina en "…"
.incident-description__line {
  flex: 1;
  min-width: 0;
  margin: 0;
  overflow: hidden;
  color: $color-text-secondary;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.incident-description__toggle {
  flex: none;
}

.incident-description__full {
  margin: 0;
  padding: $space-3;
  border-radius: $radius-control;
  background: $color-page;
  white-space: pre-line;
  overflow-wrap: anywhere;
}
</style>

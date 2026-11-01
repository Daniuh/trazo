<!-- Nota de un registro del historial. Recortada, va en una sola línea y, si no cabe, "Ver nota completa" avisa para
     abrir la actividad, donde la nota se ve entera (full). Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import { useTextOverflow } from '@/shared/ui/useTextOverflow'

defineProps<{ note: string; full?: boolean }>()
const emit = defineEmits<{ more: [] }>()

const line = ref<HTMLElement | null>(null)
const { overflowing } = useTextOverflow(line)
</script>

<template>
  <div class="history-note">
    <p ref="line" class="history-note__line" :class="{ 'history-note__line--full': full }">{{ note }}</p>
    <AppButton v-if="!full && overflowing" class="history-note__more" variant="link" @click="emit('more')">
      Ver nota completa
    </AppButton>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.history-note {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-1;
  max-width: 100%;
  padding: $space-2 $space-3;
  border-radius: $radius-control;
  background: $color-page;
}

// Una sola línea: lo que no cabe termina en "…"
.history-note__line {
  display: -webkit-box;
  max-width: 100%;
  margin: 0;
  overflow: hidden;
  font-size: $font-size-sm;
  white-space: pre-line;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
  line-clamp: 1;
}

.history-note__line--full {
  display: block;
  -webkit-line-clamp: unset;
  line-clamp: unset;
}

.history-note__more {
  font-size: $font-size-xs;
}
</style>

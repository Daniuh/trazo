<!-- Jobs fallidos en un panel (derecha en portátil y monitor, abajo en tablet y celular): qué job, en qué cola,
     cuándo y la primera línea del error, sin su contenido. Cada uno se reintenta o se descarta; abajo, "Reintentar
     todos". Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { formatShortDateTime } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import type { ApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import type { FailedJob } from '../../domain/system'

defineProps<{
  jobs: FailedJob[]
  total: number
  loading: boolean
  error: ApiError | null
  canAct: boolean
  busy: boolean
}>()
const emit = defineEmits<{ retry: [ids: string[] | null]; discard: [id: string]; reload: [] }>()
const open = defineModel<boolean>('open', { default: false })
</script>

<template>
  <AppSheet v-model:open="open" title="Jobs fallidos">
    <AppErrorState v-if="error" :error="error" @retry="emit('reload')" />
    <AppSkeleton v-else-if="loading && jobs.length === 0" :lines="5" />
    <AppEmptyState
      v-else-if="jobs.length === 0"
      title="No hay jobs fallidos"
      description="Todo lo que entró a la cola se procesó."
    />
    <template v-else>
      <p v-if="total > jobs.length" class="failed-jobs__note">
        Se muestran los {{ jobs.length }} más recientes de {{ formatNumber(total) }}.
      </p>
      <ul class="failed-jobs">
        <li v-for="job in jobs" :key="job.id" class="failed-jobs__item">
          <p class="failed-jobs__name">{{ job.job }}</p>
          <p class="failed-jobs__meta">Cola {{ job.queue }} · {{ formatShortDateTime(job.failedAt) }}</p>
          <p class="failed-jobs__error">{{ job.error }}</p>
          <div v-if="canAct" class="failed-jobs__actions">
            <AppButton variant="secondary" icon="refresh" :disabled="busy" @click="emit('retry', [job.id])">
              Reintentar
            </AppButton>
            <AppButton variant="link" :disabled="busy" @click="emit('discard', job.id)">Descartar</AppButton>
          </div>
        </li>
      </ul>
    </template>

    <template v-if="canAct && jobs.length > 0" #footer>
      <AppButton block icon="refresh" :loading="busy" @click="emit('retry', null)">
        Reintentar todos ({{ formatNumber(total) }})
      </AppButton>
    </template>
  </AppSheet>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.failed-jobs {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.failed-jobs__item {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  padding-bottom: $space-3;
  border-bottom: 1px solid $color-border;
}

.failed-jobs__name,
.failed-jobs__meta,
.failed-jobs__error,
.failed-jobs__note {
  margin: 0;
  overflow-wrap: anywhere;
}

.failed-jobs__name {
  font-weight: $font-weight-semibold;
}

.failed-jobs__meta,
.failed-jobs__note {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.failed-jobs__note {
  margin-bottom: $space-3;
}

.failed-jobs__error {
  font-size: $font-size-sm;
}

.failed-jobs__actions {
  display: flex;
  align-items: center;
  gap: $space-2;
  margin-top: $space-1;
}
</style>

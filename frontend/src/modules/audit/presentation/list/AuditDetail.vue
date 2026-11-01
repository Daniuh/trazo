<!-- Detalle de un registro de la auditoría: cuándo, quién, qué y sobre qué, cada cambio con su valor anterior y nuevo,
     y desde dónde (IP y navegador). Los enlaces aparecen solo si la API dice que quien mira puede abrirlos. Se muestra
     al lado de la tabla en monitor y dentro de un panel en las demás vistas. Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime, formatRelative } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import type { AuditCatalog, AuditEntry } from '../../domain/audit'
import { changeRows, targetText } from '../auditTexts'

const props = defineProps<{ entry: AuditEntry; catalog: AuditCatalog | null }>()
const emit = defineEmits<{ actor: [id: string] }>()

const rows = computed(() => changeRows(props.entry, props.catalog))
const targetLink = computed(() => {
  const entry = props.entry
  if (entry.tab === 'incidents') {
    return entry.links.incident ? { name: 'incident-detail', params: { id: entry.incident.id } } : null
  }
  if (entry.links.user && entry.targetUser) return { name: 'user-detail', params: { id: entry.targetUser.id } }
  if (entry.links.role && entry.targetRole) return { name: 'role-detail', params: { code: entry.targetRole.code } }
  return null
})
const linkLabel = computed(() =>
  props.entry.tab === 'incidents' ? 'Abrir incidente' : props.entry.targetUser ? 'Abrir ficha' : 'Abrir rol',
)
</script>

<template>
  <div class="audit-detail">
    <dl class="audit-detail__facts">
      <div class="audit-detail__fact">
        <dt>Cuándo</dt>
        <dd>
          <time :datetime="entry.createdAt">{{ formatDateTime(entry.createdAt) }}</time>
          <span class="audit-detail__muted"> · {{ formatRelative(entry.createdAt) }}</span>
        </dd>
      </div>
      <div class="audit-detail__fact">
        <dt>Hecho por</dt>
        <dd>
          {{ entry.user.name }}
          <AppButton variant="link" @click="emit('actor', entry.user.id)">Ver su actividad</AppButton>
        </dd>
      </div>
      <div class="audit-detail__fact">
        <dt>Sobre</dt>
        <dd>
          {{ targetText(entry) }}
          <span v-if="entry.tab === 'incidents' && entry.incident.deleted" class="audit-detail__muted">
            (eliminado)</span
          >
          <RouterLink v-if="targetLink" class="audit-detail__link" :to="targetLink">{{ linkLabel }}</RouterLink>
        </dd>
      </div>
    </dl>

    <section v-if="rows.length > 0" class="audit-detail__changes" aria-label="Cambios">
      <h3 class="audit-detail__heading">Qué cambió</h3>
      <dl class="audit-detail__facts">
        <div v-for="row in rows" :key="row.label" class="audit-detail__fact">
          <dt>{{ row.label }}</dt>
          <dd>{{ row.value }}</dd>
        </div>
      </dl>
    </section>

    <section class="audit-detail__changes" aria-label="Desde dónde">
      <h3 class="audit-detail__heading">Desde dónde</h3>
      <dl class="audit-detail__facts">
        <div class="audit-detail__fact">
          <dt>Dirección IP</dt>
          <dd>{{ entry.ipAddress ?? 'Sin registro (datos de demostración)' }}</dd>
        </div>
        <div class="audit-detail__fact">
          <dt>Navegador</dt>
          <dd class="audit-detail__agent">{{ entry.userAgent ?? 'Sin registro' }}</dd>
        </div>
      </dl>
    </section>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.audit-detail,
.audit-detail__facts,
.audit-detail__changes {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
}

.audit-detail {
  gap: $space-5;
}

.audit-detail__fact {
  display: flex;
  flex-direction: column;
  gap: $space-1;

  dt {
    color: $color-text-secondary;
    font-size: $font-size-xs;
    font-weight: $font-weight-medium;
  }

  dd {
    margin: 0;
    overflow-wrap: anywhere;
  }
}

.audit-detail__heading {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}

.audit-detail__muted,
.audit-detail__agent {
  color: $color-text-secondary;
}

.audit-detail__agent {
  font-size: $font-size-xs;
}

.audit-detail__link {
  display: block;
  margin-top: $space-1;
  color: $color-primary;
  font-weight: $font-weight-medium;
}
</style>

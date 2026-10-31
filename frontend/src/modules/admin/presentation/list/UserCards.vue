<!-- Usuarios en tarjetas (celular y tablet): nombre, correo, rol, estado y último ingreso. Tocar una tarjeta abre su
     ficha. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import AppBadge from '@/shared/ui/AppBadge.vue'
import type { UserSummary } from '../../domain/user'
import { lastLoginText, mfaLabel, statusLabel, statusTone } from '../userTexts'

defineProps<{ items: UserSummary[] }>()
const emit = defineEmits<{ open: [id: string] }>()
</script>

<template>
  <ul class="user-cards">
    <li v-for="user in items" :key="user.id">
      <article
        class="user-cards__card"
        tabindex="0"
        :aria-label="user.name"
        @click="emit('open', user.id)"
        @keydown.enter="emit('open', user.id)"
      >
        <div class="user-cards__head">
          <h2 class="user-cards__name">{{ user.name }}</h2>
          <AppBadge :tone="statusTone(user)">{{ statusLabel(user) }}</AppBadge>
        </div>
        <p class="user-cards__email">{{ user.email }}</p>
        <p class="user-cards__meta">
          {{ user.role?.name ?? 'Sin rol' }} · {{ mfaLabel(user) }} · {{ lastLoginText(user) }}
        </p>
      </article>
    </li>
  </ul>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-cards {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.user-cards__card {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    border-color: $color-border-strong;
  }
}

.user-cards__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-2;
}

.user-cards__name {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

.user-cards__email,
.user-cards__meta {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  overflow-wrap: anywhere;
}

.user-cards__email {
  font-size: $font-size-sm;
}
</style>

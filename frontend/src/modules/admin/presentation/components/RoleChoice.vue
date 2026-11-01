<!-- Elegir el rol de una persona (AppRadioList): solo los roles que quien administra puede dar, de mayor a menor
     nivel, cada uno con una línea que explica qué hace. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppRadioList from '@/shared/ui/form/AppRadioList.vue'
import type { RoleOption } from '../../domain/user'
import { roleDescription } from '../userTexts'

const props = defineProps<{ roles: RoleOption[]; error?: string }>()
const model = defineModel<string | null>({ default: null })

const options = computed(() =>
  props.roles.map((role) => ({ value: role.code, label: role.name, description: roleDescription(role) })),
)
</script>

<template>
  <AppRadioList v-model="model" label="Rol" :options="options" :error="error" />
</template>

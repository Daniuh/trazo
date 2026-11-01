<!-- Componente raíz: aloja la vista activa y el aviso global cuando una pantalla no se puede descargar.
     Documentado en docs/frontend/http-y-errores.md -->
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { installScreenLoadNotice } from './router/screenLoadNotice'

installScreenLoadNotice(useRouter(), useFeedback().notifyError)
</script>

<template>
  <RouterView />
</template>

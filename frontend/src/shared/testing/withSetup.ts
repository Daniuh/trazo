// Solo para pruebas: monta un composable dentro de un componente, con router en memoria y Pinia, para probar los que
// usan onMounted, la URL o un store. Devuelve lo que entrega el composable y el router para mover la URL.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { enableAutoUnmount, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach } from 'vitest'
import { defineComponent, h } from 'vue'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

// Cada prueba desmonta lo que montó: un componente que sigue vivo seguiría escuchando avisos y temporizadores
enableAutoUnmount(afterEach)

export async function withSetup<T>(
  composable: () => T,
  path = '/',
): Promise<{ result: T; router: Router; unmount: () => void }> {
  const pinia = createPinia()
  setActivePinia(pinia)
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:pathMatch(.*)*', component: { render: () => null } }],
  })
  await router.push(path)
  await router.isReady()

  let result!: T
  const wrapper = mount(
    defineComponent({
      setup() {
        result = composable()
        return () => h('div')
      },
    }),
    { global: { plugins: [pinia, router] } },
  )

  return { result, router, unmount: () => wrapper.unmount() }
}

// Espera a que terminen las promesas pendientes (las cargas que dispara onMounted)
export const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

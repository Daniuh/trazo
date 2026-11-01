// Solo para TypeDoc, que compila con tsc y no entiende los .vue: los trata como componentes genéricos. vue-tsc no lo
// usa, así que los tipos reales de cada componente se siguen revisando. Documentado en docs/frontend/index.md
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent
  export default component
}

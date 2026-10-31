// Medidas del layout que Quasar necesita como números en JS (el resto de tokens vive en SCSS).
// Documentado en docs/frontend/sistema-de-diseno/tokens.md
export const layout = {
  sidebarWidth: 240,
  sidebarMiniWidth: 72,
  // Hasta este ancho el menú es de celular (coincide con $bp-tablet - 1px)
  mobileMaxWidth: 599,
  // Grilla del dashboard en el modo Personalizar (px): coinciden con $dashboard-row-height y $space-4
  dashboardRowHeight: 72,
  dashboardGap: 16,
} as const

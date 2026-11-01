// Medidas del layout que Quasar necesita como números en JS (el resto de tokens vive en SCSS).
// Documentado en docs/frontend/sistema-de-diseno/tokens.md
export const layout = {
  // Celulares angostos: el dock deja un lugar menos para las secciones bajo 400 px y dos menos bajo 340 px
  smallPhoneWidth: 400,
  tinyPhoneWidth: 340,
  // Grilla del dashboard en el modo Personalizar (px): coinciden con $dashboard-row-height y $space-4
  dashboardRowHeight: 72,
  dashboardGap: 16,
  // Alto máximo de los menús con calendario: Quasar los corta al 65 % de la ventana y en portátiles bajos salía scroll
  popupMaxHeight: '90vh',
  // Menús (AppMenu): separación con su botón y margen mínimo con los bordes de la ventana, en px
  menuGap: 6,
  menuViewportMargin: 8,
} as const

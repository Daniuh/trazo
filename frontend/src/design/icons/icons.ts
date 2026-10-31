// Íconos propios de Trazo: grilla de 24, trazo de 1,5 px, puntas redondeadas y sin relleno.
// Se guardan como figuras (no como HTML) para dibujarlos sin v-html. Documentado en docs/frontend/sistema-de-diseno/iconos.md

export type IconShape =
  | { tag: 'path'; d: string }
  | { tag: 'circle'; cx: number; cy: number; r: number }
  | { tag: 'rect'; x: number; y: number; width: number; height: number; rx: number }

const path = (d: string): IconShape => ({ tag: 'path', d })
const circle = (cx: number, cy: number, r: number): IconShape => ({ tag: 'circle', cx, cy, r })
const rect = (x: number, y: number, width: number, height: number, rx = 2): IconShape => ({
  tag: 'rect',
  x,
  y,
  width,
  height,
  rx,
})

// El punto de "información" y de "alerta" es un círculo diminuto: con trazo se ve como punto
const dot = (cx: number, cy: number) => circle(cx, cy, 0.4)

export const icons = {
  home: [path('M4 11l8-7 8 7v8.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5z'), path('M10 21v-6h4v6')],
  incidents: [rect(4, 3.5, 16, 17, 2.5), path('M8 8.5h8M8 12h8M8 15.5h5')],
  dashboard: [path('M5 20v-8M10 20V6M15 20v-9M20 20V9')],
  audit: [path('M3 12h4l3-7 4 14 3-7h4')],
  users: [
    circle(9, 8, 3.5),
    path('M2.5 20a6.5 6.5 0 0 1 13 0'),
    path('M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6'),
  ],
  user: [circle(12, 8, 3.5), path('M5 20a7 7 0 0 1 14 0')],
  shield: [path('M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z')],
  server: [rect(4, 4, 16, 7, 2), rect(4, 13, 16, 7, 2), dot(8, 7.5), dot(8, 16.5)],
  search: [circle(11, 11, 6.5), path('M16 16l4 4')],
  filter: [path('M4 6h16M7 12h10M10 18h4')],
  sort: [path('M8 4v16M4 8l4-4 4 4M16 20V4M12 16l4 4 4-4')],
  plus: [path('M12 5v14M5 12h14')],
  edit: [path('M15.5 4.5l4 4L9 19H5v-4z'), path('M13 7l4 4')],
  trash: [
    path('M4 7h16M10 11v6M14 11v6'),
    path('M6 7l1 12.5a1.5 1.5 0 0 0 1.5 1.5h7a1.5 1.5 0 0 0 1.5-1.5L18 7'),
    path('M9 7V4.5h6V7'),
  ],
  refresh: [path('M20 11a8 8 0 0 0-14.5-4.5L4 8M4 4v4h4'), path('M4 13a8 8 0 0 0 14.5 4.5L20 16M20 20v-4h-4')],
  download: [
    path('M12 4v11M7.5 10.5L12 15l4.5-4.5'),
    path('M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17'),
  ],
  calendar: [rect(4, 5, 16, 15, 2), path('M4 10h16M8.5 3v4M15.5 3v4')],
  clock: [circle(12, 12, 8.5), path('M12 7.5V12l3 2')],
  info: [circle(12, 12, 8.5), path('M12 11v5'), dot(12, 7.8)],
  alert: [path('M12 4L21 19.5H3z'), path('M12 10v4'), dot(12, 16.8)],
  check: [path('M5 12.5l4.5 4.5L19 7.5')],
  close: [path('M6 6l12 12M18 6L6 18')],
  menu: [path('M4 7h16M4 12h16M4 17h16')],
  more: [dot(6, 12), dot(12, 12), dot(18, 12)],
  copy: [rect(8, 8, 12, 12, 2), path('M16 8V5.5A1.5 1.5 0 0 0 14.5 4h-9A1.5 1.5 0 0 0 4 5.5v9A1.5 1.5 0 0 0 5.5 16H8')],
  circle: [circle(12, 12, 7)],
  grip: [dot(9, 6), dot(15, 6), dot(9, 12), dot(15, 12), dot(9, 18), dot(15, 18)],
  'chevron-down': [path('M6 9l6 6 6-6')],
  'chevron-up': [path('M6 15l6-6 6 6')],
  'chevron-left': [path('M15 6l-6 6 6 6')],
  'chevron-right': [path('M9 6l6 6-6 6')],
  eye: [path('M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z'), circle(12, 12, 3)],
  'eye-off': [
    path('M4 4l16 16'),
    path(
      'M9.9 5.8A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.6 3.4M6.2 7.6A16.6 16.6 0 0 0 2.5 12S6 18.5 12 18.5a9 9 0 0 0 4.3-1.1',
    ),
    path('M9.9 9.9a3 3 0 0 0 4.2 4.2'),
  ],
  lock: [rect(5, 10.5, 14, 10, 2), path('M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5')],
  logout: [path('M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 16l4-4-4-4M14 12H4')],
} satisfies Record<string, IconShape[]>

export type IconName = keyof typeof icons

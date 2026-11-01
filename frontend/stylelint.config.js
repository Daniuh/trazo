// Reglas de estilos: fuera de design/ no se escriben colores ni breakpoints a mano, solo tokens y mixins.
// Documentado en docs/frontend/sistema-de-diseno/tokens.md
const BEM_CLASS = '^[a-z][a-z0-9]*(-[a-z0-9]+)*(__[a-z0-9]+(-[a-z0-9]+)*)?(--[a-z0-9]+(-[a-z0-9]+)*)?$'

export default {
  extends: ['stylelint-config-standard-scss', 'stylelint-config-recommended-vue/scss'],
  rules: {
    'color-no-hex': true,
    'color-named': 'never',
    'function-disallowed-list': ['rgb', 'rgba', 'hsl', 'hsla'],
    // Las vistas se declaran con los mixins tablet/laptop/desktop
    'at-rule-disallowed-list': ['media'],
    // Clases en BEM: bloque__elemento--modificador
    'selector-class-pattern': [BEM_CLASS, { resolveNestedSelectors: true }],
  },
  overrides: [
    {
      files: ['src/design/**/*.scss'],
      rules: {
        'color-no-hex': null,
        'function-disallowed-list': null,
        'at-rule-disallowed-list': null,
        'scss/dollar-variable-pattern': null,
      },
    },
  ],
}

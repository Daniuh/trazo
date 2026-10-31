// Reglas de estilos: fuera de design/ no se escriben colores ni breakpoints a mano, solo tokens y mixins.
// Documentado en docs/frontend/sistema-de-diseno/tokens.md
export default {
  extends: ['stylelint-config-standard-scss', 'stylelint-config-recommended-vue/scss'],
  rules: {
    'color-no-hex': true,
    'color-named': 'never',
    'function-disallowed-list': ['rgb', 'rgba', 'hsl', 'hsla'],
    // Las vistas se declaran con los mixins tablet/laptop/desktop
    'at-rule-disallowed-list': ['media'],
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

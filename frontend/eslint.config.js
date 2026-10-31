// Reglas de calidad del frontend: Vue y TypeScript estrictos, más reglas de seguridad propias.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import js from '@eslint/js'
import prettier from 'eslint-config-prettier'
import vue from 'eslint-plugin-vue'
import globals from 'globals'
import tseslint from 'typescript-eslint'

export default tseslint.config(
  { ignores: ['dist', 'coverage', 'node_modules'] },
  js.configs.recommended,
  ...tseslint.configs.strict,
  ...vue.configs['flat/recommended'],
  {
    files: ['**/*.vue'],
    languageOptions: { parserOptions: { parser: tseslint.parser } },
  },
  {
    languageOptions: { globals: { ...globals.browser } },
    rules: {
      // Inyectar HTML abre la puerta a XSS: prohibido en todo el proyecto
      'vue/no-v-html': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-console': ['warn', { allow: ['warn', 'error'] }],
      'vue/multi-word-component-names': 'off',
      eqeqeq: ['error', 'always'],
    },
  },
  prettier,
)

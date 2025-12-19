import { inject } from 'vue'

export function useTheme() {
    const themeContext = inject('theme')
    if (!themeContext) {
        throw new Error('useTheme must be used within ThemeProvider')
    }
    return themeContext
}

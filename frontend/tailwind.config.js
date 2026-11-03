/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
  ],
  safelist: [
    'max-w-5xl',
    'max-w-6xl',
    'max-w-7xl',
    'max-w-screen-xl',
    'max-w-screen-2xl',
    'max-w-full',
  ],
  theme: {
    extend: {
      colors: {
        // ── Dynamic — all driven by CSS vars set by useAppearance.js ──
        primary:         'rgb(var(--color-primary-rgb) / <alpha-value>)',
        'primary-dark':  'rgb(var(--color-primary-dark-rgb) / <alpha-value>)',
        'primary-light': 'rgb(var(--color-primary-light-rgb) / <alpha-value>)',
        accent:          'rgb(var(--color-accent-rgb) / <alpha-value>)',
        'accent-dark':   'rgb(var(--color-accent-dark-rgb) / <alpha-value>)',
        'accent-light':  'rgb(var(--color-accent-light-rgb) / <alpha-value>)',
        // ── Static brand colours ──
        bkash: '#E2136E',
        cream: '#F8FAF9',
        gold:  '#F5A623',
      },
      fontFamily: {
        bengali: ['Hind Siliguri', 'sans-serif'],
        display: ['Inter', 'Hind Siliguri', 'sans-serif'],
      },
      borderRadius: {
        // Dynamic radius tokens — match --border-radius CSS var
        'ap':    'var(--border-radius)',
        'ap-sm': 'var(--border-radius-sm)',
        'ap-lg': 'var(--border-radius-lg)',
      },
      minHeight: {
        'hero': 'var(--hero-height, 420px)',
      },
      boxShadow: {
        card:       '0 2px 4px rgba(0,0,0,0.06)',
        'card-hover': '0 8px 24px rgba(0,0,0,0.12)',
        btn:        '0 4px 14px rgba(0,0,0,0.15)',
      },
      backgroundImage: {
        'hero-pattern': "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C%2Fg%3E%3C%2Fsvg%3E\")",
      },
    },
  },
  plugins: [],
}

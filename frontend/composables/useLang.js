/**
 * composables/useLang.js — Bangla / English switch.
 *
 * - The chosen language is kept in a cookie (`lang`), so the server renders the same language the visitor picked.
 * - `tr('বাংলা লেখা')` returns the English text from locales/en.js when English is on (and the Bangla text otherwise
 *   or when no translation exists yet). Add more pairs to locales/en.js to translate more of the site.
 */
import en from '~/locales/en.js'

export const useLang = () => {
  const lang = useCookie('lang', { default: () => 'bn', maxAge: 60 * 60 * 24 * 365, sameSite: 'lax', path: '/' })
  const isEn = computed(() => lang.value === 'en')
  const tr = (text) => (lang.value === 'en' && en[text]) || text
  const setLang = (l) => { lang.value = l === 'en' ? 'en' : 'bn' }
  const toggleLang = () => setLang(isEn.value ? 'bn' : 'en')
  return { lang, isEn, tr, setLang, toggleLang }
}

export default useLang

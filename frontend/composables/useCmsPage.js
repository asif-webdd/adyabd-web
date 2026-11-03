/**
 * Loads an admin-editable page (Admin → Content → Site Pages).
 * `page.content` is null/empty when the admin has not written anything,
 * in which case the calling page keeps showing its built-in default text.
 */
export const useCmsPage = (slug) => {
  const page = ref(null)
  const api = useApi()
  const load = async () => {
    try { page.value = await api.get(`/pages/${slug}`) } catch { page.value = null }
  }
  if (import.meta.client) onMounted(load)
  return page
}

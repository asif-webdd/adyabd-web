/** Shop name / helpline come from Admin → Settings (never hard-coded in pages). */
export const useShopName = () => {
  const { branding, footer } = useAppearance()
  const fallback = useRuntimeConfig().public.siteName || 'Shop'
  const shopName = computed(() => branding.value.shop_name_bn || branding.value.shop_name || fallback)
  const helpline = computed(() => footer.value.phone || '')
  const helplineHref = computed(() => helpline.value ? 'tel:' + helpline.value.replace(/[^\d+]/g, '') : '')
  return { shopName, helpline, helplineHref }
}

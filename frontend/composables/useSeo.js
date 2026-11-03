/**
 * useSeo.js — dynamic SEO using branding from useAppearance
 */
export const useSeo = () => {
  const { branding } = useAppearance()
  const _siteName = useRuntimeConfig().public.siteName
  const name = computed(() => branding.value.shop_name_bn || branding.value.shop_name || _siteName || 'Shop')

  const setProductSeo = (product) => {
    useHead({
      title: `${product.name} — ${name.value}`,
      meta: [
        { name: 'description', content: `${product.name} কিনুন ${name.value}এ সেরা দামে। ৳${product.price} | ${product.discount}% ছাড় | দ্রুত ডেলিভারি।` },
        { property: 'og:title',       content: `${product.name} — ${name.value}` },
        { property: 'og:description', content: `${product.name} — ৳${product.price}` },
        { property: 'og:image',       content: product.image },
      ],
    })
  }

  const setCategorySeo = (category) => {
    useHead({
      title: `${category.name} — ${name.value}`,
      meta: [
        { name: 'description', content: `${category.name} বিভাগে ${category.productCount}+ পণ্য। সেরা দামে কিনুন ${name.value}এ।` },
      ],
    })
  }

  const setPageSeo = (title) => {
    useHead({ title: `${title} — ${name.value}` })
  }

  return { setProductSeo, setCategorySeo, setPageSeo, shopName: name }
}

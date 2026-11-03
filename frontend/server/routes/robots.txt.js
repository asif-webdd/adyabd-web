// GET /robots.txt — keeps private pages out of search results and points to the sitemap.
export default defineEventHandler((event) => {
  const site = String(useRuntimeConfig().public.siteUrl || '').replace(/\/$/, '')
  setHeader(event, 'Content-Type', 'text/plain; charset=utf-8')
  return [
    'User-agent: *',
    'Allow: /',
    'Disallow: /account',
    'Disallow: /cart',
    'Disallow: /checkout',
    'Disallow: /auth',
    'Disallow: /order',
    'Disallow: /seller/dashboard',
    'Disallow: /support',
    '',
    `Sitemap: ${site}/sitemap.xml`,
    '',
  ].join('\n')
})

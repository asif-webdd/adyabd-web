// GET /sitemap.xml — built from the backend (/sitemap-data), cached by the backend for 10 minutes.
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const site = String(config.public.siteUrl || '').replace(/\/$/, '')
  const api = String(config.public.apiBase || '').replace(/\/$/, '')
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
  const url = (path, date) => `<url><loc>${esc(site + path)}</loc>${date ? `<lastmod>${new Date(date).toISOString().slice(0, 10)}</lastmod>` : ''}</url>`

  const urls = ['/', '/about', '/contact', '/deals', '/blog', '/policy/privacy', '/policy/terms', '/seller/register'].map((p) => url(p))
  try {
    const res = await fetch(`${api}/api/v1/sitemap-data`, { headers: { Accept: 'application/json' } })
    const json = await res.json()
    const d = json?.data ?? json ?? {}
    for (const c of d.categories ?? []) urls.push(url(`/category/${c.slug}`, c.updated_at))
    for (const b of d.posts ?? []) urls.push(url(`/blog/${b.slug}`, b.updated_at))
    for (const p of d.products ?? []) urls.push(url(`/product/${p.slug}`, p.updated_at))
  } catch { /* backend unreachable: still serve the static URLs */ }

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'public, max-age=600')
  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${urls.join('')}</urlset>`
})

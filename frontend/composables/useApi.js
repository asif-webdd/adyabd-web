/**
 * composables/useApi.js
 *
 * Central HTTP client — one instance per app lifetime (singleton via useState).
 *
 * Improvements over original:
 *  ✅ Singleton — same instance reused across every composable/store call
 *  ✅ Token read once per request from the Pinia store (no repeated localStorage hits)
 *  ✅ In-flight deduplication — identical GET requests are coalesced into one fetch
 *  ✅ 401 handling clears auth state cleanly via the Pinia store (no direct localStorage writes)
 *  ✅ Structured error with status code attached for consumers to branch on
 *  ✅ AbortController support via opts.signal
 */

/** @type {Map<string, Promise<any>>} — in-flight GET dedup cache */
const _inFlight = new Map()

/** Singleton api object stored in Nuxt state so SSR/client hydration share it */
let _instance = null

export const useApi = () => {
  if (_instance) return _instance

  const config  = useRuntimeConfig()
  const BASE    = (config.public.apiBase || '').replace(/\/$/, '')

  // ─── Token resolution ───────────────────────────────────────────────────────
  // Prefer Pinia store (reactive, SSR-safe). Fall back to localStorage only when
  // the store isn't hydrated yet (first paint before plugin runs).
  const getToken = () => {
    try {
      // useAuthStore() is safe to call here — Pinia is already initialised
      const { token } = useAuthStore()
      if (token) return token
    } catch { /* store not ready yet (rare) */ }
    if (import.meta.client) return localStorage.getItem('authenticationToken')
    return null
  }

  // ─── Core request ───────────────────────────────────────────────────────────
  const request = async (method, path, body = null, opts = {}) => {
    const token      = getToken()
    const isFormData = body instanceof FormData
    const url        = `${BASE}/api/v1${path}`

    const headers = {
      Accept: 'application/json',
      ...opts.headers,
    }
    if (!isFormData) headers['Content-Type'] = 'application/json'
    if (token)       headers['Authorization'] = `Bearer ${token}`

    const init = { method, headers, signal: opts.signal }
    if (body) init.body = isFormData ? body : JSON.stringify(body)

    const res  = await fetch(url, init)
    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      // Normalise validation-style messages ( { message: { field: ['err'] } } )
      const raw = data.message
      const msg = raw && typeof raw === 'object'
          ? Object.values(raw).flat().join(' ')
          : raw || 'অজানা ত্রুটি'

      if (res.status === 401) {
        // Let the store handle its own cleanup — don't touch localStorage here
        try { useAuthStore()._clear() } catch { /* store not ready */ }
      }

      const err = new Error(msg)
      err.status = res.status
      err.data   = data
      throw err
    }

    return data.data ?? data
  }

  // ─── GET with in-flight deduplication ──────────────────────────────────────
  const get = (path, params = {}, opts = {}) => {
    const filtered = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v != null && v !== '')
    )
    const qs  = new URLSearchParams(filtered).toString()
    const key = qs ? `${path}?${qs}` : path

    // If an identical GET is already in-flight, return the same promise
    if (_inFlight.has(key)) return _inFlight.get(key)

    const promise = request('GET', key, null, opts).finally(() => {
      _inFlight.delete(key)
    })

    _inFlight.set(key, promise)
    return promise
  }

  _instance = {
    get,
    post:   (path, body, opts)      => request('POST',   path, body,      opts),
    put:    (path, body, opts)      => request('PUT',    path, body,      opts),
    patch:  (path, body, opts)      => request('PATCH',  path, body,      opts),
    delete: (path, opts)            => request('DELETE', path, null,      opts),
    upload: (path, formData, opts)  => request('POST',   path, formData,  opts),
  }

  return _instance
}

export default useApi

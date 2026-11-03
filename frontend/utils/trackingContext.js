export function getTrackingContext() {
  if (typeof document === 'undefined') return {}

  const cookie = (name) => {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
    return m ? decodeURIComponent(m[1]) : undefined
  }

  const ga = cookie('_ga')
  const client_id = ga ? ga.split('.').slice(2).join('.') : undefined

  let session_id
  for (const part of document.cookie.split('; ')) {
    if (part.startsWith('_ga_')) {
      const val = part.split('=')[1] || ''
      const m = val.match(/^GS1\.\d\.(\d+)/) || val.match(/^GS2\.\d\.s(\d+)/)
      if (m) { session_id = m[1]; break }
    }
  }

  return {
    fbp: cookie('_fbp'),
    fbc: cookie('_fbc'),
    client_id,
    session_id,
    url: window.location.href,
  }
}
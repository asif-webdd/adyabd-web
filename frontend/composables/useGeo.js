// composables/useGeo.js
// Fetches Bangladesh geo data (divisions → districts → upazilas) from the API.
// Results are cached in module-level Maps so repeated calls don't re-fetch.

const _divisions   = ref(null)          // [{id, name, name_bn}]
const _districtMap = new Map()          // division_id → [{id, name, name_bn}]
const _allDistricts = ref(null)         // every district, all 64
const _upazilaMap  = new Map()          // district_id → [{id, name, name_bn}]

export function useGeo() {
  const api = useApi()

  /** Load all divisions (cached) */
  const loadDivisions = async () => {
    if (_divisions.value) return _divisions.value
    const data = await api.get('/geo/divisions')
    _divisions.value = data ?? []
    return _divisions.value
  }

  /** Load districts for a division (cached per division_id) */
  const loadDistricts = async (divisionId) => {
    if (!divisionId) return []
    const key = Number(divisionId)
    if (_districtMap.has(key)) return _districtMap.get(key)
    const data = await api.get(`/geo/districts?division_id=${key}`)
    const result = data ?? []
    _districtMap.set(key, result)
    return result
  }

  /** Load upazilas for a district (cached per district_id) */
  const loadUpazilas = async (districtId) => {
    if (!districtId) return []
    const key = Number(districtId)
    if (_upazilaMap.has(key)) return _upazilaMap.get(key)
    const data = await api.get(`/geo/upazilas?district_id=${key}`)
    const result = data ?? []
    _upazilaMap.set(key, result)
    return result
  }

  /** Load ALL districts (all divisions, cached) — alphabetical by display name */
  const loadAllDistricts = async () => {
    if (_allDistricts.value) return _allDistricts.value
    const data = await api.get('/geo/districts')
    _allDistricts.value = Array.isArray(data) ? data : []
    return _allDistricts.value
  }

  return { loadDivisions, loadDistricts, loadUpazilas, loadAllDistricts, divisions: _divisions }
}

/** Display name of a geo row: Bangla by default, English when the site language is English. */
export const geoName = (row, isEn = false) => {
  if (!row) return ''
  return isEn ? (row.name || row.name_bn || '') : (row.name_bn || row.name || '')
}

/** Sort geo rows alphabetically by their display name. */
export const sortGeo = (rows, isEn = false) =>
  [...(rows || [])].sort((a, b) => geoName(a, isEn).localeCompare(geoName(b, isEn), isEn ? 'en' : 'bn'))

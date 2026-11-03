<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="section-title flex items-center gap-2"><span class="mi text-primary">location_on</span>{{ tr('আমার ঠিকানা') }}</h1>
      <button @click="openForm()" class="btn-primary text-sm py-2.5">
        <span class="mi mi-sm">add_location</span>{{ tr('নতুন ঠিকানা') }}
      </button>
    </div>

    <!-- Success / Error -->
    <div v-if="successMsg" class="p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-bengali flex items-center gap-2">
      <span class="mi mi-sm">check_circle</span>{{ successMsg }}
    </div>
    <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali flex items-center gap-2">
      <span class="mi mi-sm">error_outline</span>{{ errorMsg }}
    </div>

    <!-- Add / Edit Form -->
    <div v-if="showForm" class="bg-white rounded-2xl shadow-card p-6">
      <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
        <span class="mi text-primary">{{ editingId ? 'edit_location' : 'add_location' }}</span>
        {{ editingId ? 'ঠিকানা সম্পাদনা' : 'নতুন ঠিকানা যোগ করুন' }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Name -->
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পূর্ণ নাম *') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">person</span>
            <input v-model="form.name" type="text" :placeholder="tr('আপনার নাম')" class="input-field pl-10"/>
          </div>
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল নম্বর *') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">phone</span>
            <input v-model="form.phone" type="tel" placeholder="01XXXXXXXXX" class="input-field pl-10"/>
          </div>
        </div>

        <!-- District (all 64) -->
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('জেলা *') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">location_city</span>
            <select v-model="form.district_id" @change="onDistrictChange" class="input-field pl-10" :disabled="loadingDistricts">
              <option value="">{{ loadingDistricts ? tr('লোড হচ্ছে...') : tr('জেলা বেছে নিন') }}</option>
              <option v-for="d in districtList" :key="d.id" :value="d.id">{{ gn(d) }}</option>
            </select>
          </div>
        </div>

        <!-- Upazila / Thana -->
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('উপজেলা / থানা *') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">location_on</span>
            <select v-model="form.upazila_id" class="input-field pl-10" :disabled="!form.district_id || loadingUpazilas">
              <option value="">{{ !form.district_id ? tr('আগে জেলা বেছে নিন') : loadingUpazilas ? tr('লোড হচ্ছে...') : tr('উপজেলা / থানা বেছে নিন') }}</option>
              <option v-for="u in upazilaList" :key="u.id" :value="u.id">{{ gn(u) }}</option>
            </select>
          </div>
        </div>

        <!-- Address Line -->
        <div class="md:col-span-2">
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বিস্তারিত ঠিকানা *') }}</label>
          <textarea v-model="form.address_line" rows="3"
                    :placeholder="tr('বাড়ি নং, রাস্তা নং, এলাকা, ল্যান্ডমার্ক...')"
                    class="input-field resize-none"></textarea>
        </div>
      </div>

      <div class="flex gap-3 mt-5">
        <button @click="saveAddress" :disabled="saving" class="btn-primary disabled:opacity-60">
          <span v-if="saving" class="mi mi-sm animate-spin">refresh</span>
          <span v-else class="mi mi-sm">save</span>
          {{ saving ? 'সংরক্ষণ হচ্ছে...' : 'ঠিকানা সংরক্ষণ করুন' }}
        </button>
        <button @click="cancelForm" class="btn-outline"><span class="mi mi-sm">close</span>{{ tr('বাতিল করুন') }}</button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 2" :key="i" class="bg-white rounded-2xl shadow-card p-5 animate-pulse">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="w-5 h-5 bg-gray-200 rounded-full"></div>
            <div class="h-4 w-16 bg-gray-200 rounded"></div>
            <div class="h-4 w-12 bg-gray-200 rounded-full"></div>
          </div>
          <div class="flex gap-2">
            <div class="h-3 w-10 bg-gray-200 rounded"></div>
            <div class="h-3 w-10 bg-gray-200 rounded"></div>
          </div>
        </div>
        <div class="h-3.5 w-28 bg-gray-200 rounded mb-2"></div>
        <div class="h-3.5 w-full bg-gray-200 rounded mb-1.5"></div>
        <div class="h-3.5 w-3/4 bg-gray-200 rounded mb-2"></div>
        <div class="h-3.5 w-24 bg-gray-200 rounded mb-3"></div>
        <div class="h-7 w-36 bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <!-- Saved Addresses -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="addr in addresses" :key="addr.id" class="bg-white rounded-2xl shadow-card p-5 relative">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-2">
            <span class="mi text-primary">{{ addr.type==='home'?'home':addr.type==='office'?'business':'location_on' }}</span>
            <span class="font-display font-bold text-gray-800">{{ typeLabel(addr.type) }}</span>
            <span v-if="addr.is_default" class="badge bg-primary text-white flex items-center gap-0.5">
              <span class="mi mi-sm">star</span>{{ tr('ডিফল্ট') }}
            </span>
          </div>
          <div class="flex gap-2">
            <button @click="openForm(addr)" class="text-xs text-primary font-bengali hover:underline flex items-center gap-0.5">
              <span class="mi mi-sm">edit</span>{{ tr('সম্পাদনা') }}
            </button>
            <button @click="deleteAddress(addr.id)" :disabled="deleting===addr.id"
                    class="text-xs text-accent font-bengali hover:underline flex items-center gap-0.5 disabled:opacity-50">
              <span class="mi mi-sm">delete</span>{{ tr('মুছুন') }}
            </button>
          </div>
        </div>
        <p class="font-bengali font-semibold text-gray-800 mb-1 flex items-center gap-1">
          <span class="mi mi-sm text-gray-400">person</span>{{ addr.name }}
        </p>
        <p class="font-bengali text-gray-600 text-sm leading-relaxed flex items-start gap-1">
          <span class="mi mi-sm text-gray-400 mt-0.5 shrink-0">location_on</span>
          {{ addr.address_line }}{{ addr.upazila ? ', ' + gn(addr.upazila) : '' }}{{ addr.district ? ', ' + gn(addr.district) : '' }}{{ addr.division ? ', ' + gn(addr.division) : '' }}
        </p>
        <p class="font-bengali text-gray-500 text-sm mt-1 flex items-center gap-1">
          <span class="mi mi-sm text-gray-400">phone</span>{{ addr.phone }}
        </p>
        <button v-if="!addr.is_default" @click="setDefault(addr.id)"
          class="mt-3 text-xs font-bengali text-primary border border-primary px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
          <span class="mi mi-sm">star</span>{{ tr('ডিফল্ট হিসেবে সেট করুন') }}
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!loading && addresses.length===0 && !showForm" class="bg-white rounded-2xl shadow-card p-12 text-center">
      <span class="mi text-gray-200" style="font-size:64px">location_off</span>
      <h3 class="font-display font-bold text-xl text-gray-700 mt-4 mb-2">{{ tr('কোনো ঠিকানা নেই') }}</h3>
      <button @click="openForm()" class="btn-primary mt-2 inline-flex"><span class="mi mi-sm">add_location</span>{{ tr('ঠিকানা যোগ করুন') }}</button>
    </div>
  </div>
</template>

<script setup>
import { geoName, sortGeo } from '~/composables/useGeo.js'
const { tr, isEn } = useLang()
const api = useApi()
const { loadAllDistricts, loadUpazilas } = useGeo()
const gn = (row) => geoName(row, isEn.value)

const addresses  = ref([])
const loading    = ref(true)
const saving     = ref(false)
const deleting   = ref(null)
const showForm   = ref(false)
const editingId  = ref(null)
const successMsg = ref('')
const errorMsg   = ref('')

// Geo lists for dropdowns (all 64 districts; upazilas/thanas of the chosen district)
const districtList     = ref([])
const upazilaList      = ref([])
const loadingDistricts = ref(false)
const loadingUpazilas  = ref(false)

// Only: name, phone, district, upazila/thana, full address
const form = reactive({ name: '', phone: '', address_line: '', district_id: '', upazila_id: '' })

const typeLabel = (t) => ({ home:'বাড়ি', office:'অফিস', other:'অন্যান্য' }[t] || t)

const showMsg = (type, msg) => {
  if (type === 'success') { successMsg.value = msg; setTimeout(() => successMsg.value = '', 3000) }
  else                    { errorMsg.value   = msg; setTimeout(() => errorMsg.value   = '', 4000) }
}

const onDistrictChange = async () => {
  form.upazila_id = ''
  upazilaList.value = []
  if (!form.district_id) return
  loadingUpazilas.value = true
  try { upazilaList.value = sortGeo(await loadUpazilas(form.district_id), isEn.value) }
  finally { loadingUpazilas.value = false }
}

const loadAddresses = async () => {
  loading.value = true
  try {
    const data = await api.get('/addresses')
    addresses.value = data ?? []
  } catch (e) {
    showMsg('error', e.message)
  } finally {
    loading.value = false
  }
}

const openForm = async (addr = null) => {
  if (!districtList.value.length) {
    loadingDistricts.value = true
    try { districtList.value = sortGeo(await loadAllDistricts(), isEn.value) }
    finally { loadingDistricts.value = false }
  }
  editingId.value = addr?.id ?? null
  Object.assign(form, {
    name:         addr?.name         ?? '',
    phone:        addr?.phone        ?? '',
    address_line: addr?.address_line ?? '',
    district_id:  addr?.district_id  ?? addr?.district?.id ?? '',
    upazila_id:   addr?.upazila_id   ?? addr?.upazila?.id  ?? '',
  })
  upazilaList.value = []
  if (form.district_id) {
    const keep = form.upazila_id
    await onDistrictChange()
    form.upazila_id = keep
  }
  showForm.value = true
}

const cancelForm = () => { showForm.value = false; editingId.value = null }

const saveAddress = async () => {
  if (!form.name || !form.phone || !form.district_id || !form.address_line) {
    showMsg('error', 'নাম, মোবাইল, জেলা এবং বিস্তারিত ঠিকানা আবশ্যক।'); return
  }
  if (upazilaList.value.length && !form.upazila_id) {
    showMsg('error', 'উপজেলা / থানা বেছে নিন।'); return
  }
  saving.value = true
  try {
    const payload = {
      name:         form.name,
      phone:        form.phone,
      address_line: form.address_line,
      district_id:  form.district_id,
      upazila_id:   form.upazila_id || null,
    }
    if (editingId.value) {
      await api.put(`/addresses/${editingId.value}`, payload)
      showMsg('success', 'ঠিকানা আপডেট হয়েছে।')
    } else {
      await api.post('/addresses', payload)
      showMsg('success', 'নতুন ঠিকানা যোগ হয়েছে।')
    }
    cancelForm()
    await loadAddresses()
  } catch (e) {
    showMsg('error', e.message)
  } finally {
    saving.value = false
  }
}

const deleteAddress = async (id) => {
  if (!confirm('এই ঠিকানাটি মুছে ফেলবেন?')) return
  deleting.value = id
  try {
    await api.delete(`/addresses/${id}`)
    await loadAddresses()
    showMsg('success', 'ঠিকানা মুছে ফেলা হয়েছে।')
  } catch (e) {
    showMsg('error', e.message)
  } finally {
    deleting.value = null
  }
}

const setDefault = async (id) => {
  try {
    await api.put(`/addresses/${id}/default`)
    await loadAddresses()
    showMsg('success', 'ডিফল্ট ঠিকানা সেট হয়েছে।')
  } catch (e) {
    showMsg('error', e.message)
  }
}

onMounted(loadAddresses)
</script>

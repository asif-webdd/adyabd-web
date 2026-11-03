<template>
  <div class="space-y-5">
    <h1 class="section-title flex items-center gap-2"><span class="mi text-primary">person</span>{{ tr('আমার প্রোফাইল') }}</h1>

    <!-- Success / Error -->
    <div v-if="successMsg" class="p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-bengali flex items-center gap-2">
      <span class="mi mi-sm">check_circle</span>{{ successMsg }}
    </div>
    <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali flex items-center gap-2">
      <span class="mi mi-sm">error_outline</span>{{ errorMsg }}
    </div>

    <!-- Avatar -->
    <div class="bg-white rounded-2xl shadow-card p-6">
      <div class="flex items-center gap-5 flex-wrap">
        <div class="relative">
          <img :src="avatarSrc" alt="Profile" class="w-24 h-24 rounded-full object-cover ring-4 ring-primary/20"/>
          <label class="absolute bottom-0 right-0 w-8 h-8 bg-primary rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-primary-dark transition-colors">
            <span class="mi mi-sm text-white">edit</span>
            <input type="file" accept="image/*" class="hidden" @change="uploadAvatar"/>
          </label>
        </div>
        <div>
          <h2 class="font-display font-bold text-xl text-gray-800">{{ authStore.user?.name }}</h2>
          <p class="font-bengali text-gray-500 text-sm flex items-center gap-1 mt-0.5">
            <span class="mi mi-sm">email</span>{{ authStore.user?.email }}
          </p>
          <p class="font-bengali text-gray-400 text-xs mt-1 flex items-center gap-1">
            <span class="mi mi-sm">calendar_today</span>যোগদান: {{ joinDate }}
          </p>
        </div>
      </div>
    </div>

    <!-- Profile Form -->
    <div class="bg-white rounded-2xl shadow-card p-6">
      <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2"><span class="mi text-primary">edit</span>{{ tr('ব্যক্তিগত তথ্য সম্পাদনা') }}</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পূর্ণ নাম *') }}</label>
          <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">person</span>
            <input v-model="form.name" type="text" class="input-field pl-10" :placeholder="tr('আপনার পূর্ণ নাম')"/></div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ইমেইল') }}</label>
          <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">email</span>
            <input v-model="form.email" type="email" class="input-field pl-10" :placeholder="tr('আপনার ইমেইল')"/></div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল নম্বর') }}</label>
          <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">phone</span>
            <input v-if="authStore.user?.phone" :value="authStore.user?.phone" type="tel" disabled class="input-field pl-10 bg-gray-50 cursor-not-allowed text-gray-500" :placeholder="tr('মোবাইল নম্বর')"/>
            <input v-else v-model="form.phone" type="tel" class="input-field pl-10" placeholder="01XXXXXXXXX"/></div>
          <p class="text-xs font-bengali text-gray-400 mt-1">
            {{ authStore.user?.phone ? 'মোবাইল নম্বর পরিবর্তন করতে সাপোর্টে যোগাযোগ করুন।' : 'অর্ডার ও ডেলিভারির জন্য মোবাইল নম্বর যোগ করুন (একবারই যোগ করা যাবে)।' }}
          </p>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('জন্ম তারিখ') }}</label>
          <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">cake</span>
            <input v-model="form.date_of_birth" type="date" class="input-field pl-10"/></div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-2">{{ tr('লিঙ্গ') }}</label>
          <div class="flex gap-4 flex-wrap">
            <label v-for="g in genders" :key="g.value" class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="gender" :value="g.value" v-model="form.gender" class="accent-primary"/>
              <span class="mi mi-sm" :class="g.color">{{ g.icon }}</span>
              <span class="font-bengali text-sm text-gray-700">{{ g.label }}</span>
            </label>
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পছন্দের ভাষা') }}</label>
          <select v-model="form.preferred_language" class="input-field">
            <option value="bn">{{ tr('বাংলা') }}</option>
            <option value="en">English</option>
          </select>
        </div>
      </div>
      <button @click="saveProfile" :disabled="saving" class="mt-5 btn-primary disabled:opacity-60">
        <span v-if="saving" class="mi mi-sm animate-spin">refresh</span>
        <span v-else class="mi mi-sm">save</span>
        {{ saving ? 'সংরক্ষণ হচ্ছে...' : 'পরিবর্তন সংরক্ষণ করুন' }}
      </button>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl shadow-card p-6">
      <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2"><span class="mi text-primary">lock</span>{{ tr('পাসওয়ার্ড পরিবর্তন') }}</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বর্তমান পাসওয়ার্ড') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
            <input v-model="pwForm.current_password" :type="showCurrentPw ? 'text' : 'password'" class="input-field pl-10 pr-10" :placeholder="tr('বর্তমান পাসওয়ার্ড')"/>
            <button type="button" @click="showCurrentPw = !showCurrentPw" class="absolute right-3 top-1/2 -translate-y-1/2">
              <span class="mi mi-sm text-gray-400">{{ showCurrentPw ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('নতুন পাসওয়ার্ড') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock_open</span>
            <input v-model="pwForm.password" :type="showNewPw ? 'text' : 'password'" class="input-field pl-10 pr-10" :placeholder="tr('নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)')"/>
            <button type="button" @click="showNewPw = !showNewPw" class="absolute right-3 top-1/2 -translate-y-1/2">
              <span class="mi mi-sm text-gray-400">{{ showNewPw ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
          <!-- Strength indicator -->
          <div v-if="pwForm.password" class="mt-2">
            <div class="flex gap-1 mb-1">
              <div v-for="i in 4" :key="i" class="flex-1 h-1 rounded-full transition-colors" :class="i <= pwStrength ? pwStrengthColor : 'bg-gray-200'"></div>
            </div>
            <p class="text-xs font-bengali" :class="pwStrengthTextColor">{{ pwStrengthLabel }}</p>
          </div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পাসওয়ার্ড নিশ্চিত করুন') }}</label>
          <div class="relative">
            <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
            <input v-model="pwForm.password_confirmation" :type="showConfirmPw ? 'text' : 'password'" class="input-field pl-10 pr-10" :placeholder="tr('আবার পাসওয়ার্ড দিন')"/>
            <button type="button" @click="showConfirmPw = !showConfirmPw" class="absolute right-3 top-1/2 -translate-y-1/2">
              <span class="mi mi-sm text-gray-400">{{ showConfirmPw ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
          <p v-if="pwForm.password_confirmation && pwForm.password !== pwForm.password_confirmation" class="text-xs text-accent mt-1 font-bengali">{{ tr('পাসওয়ার্ড মেলেনি।') }}</p>
        </div>
      </div>
      <button @click="changePassword" :disabled="savingPw || pwForm.password !== pwForm.password_confirmation" class="mt-5 btn-primary disabled:opacity-60">
        <span v-if="savingPw" class="mi mi-sm animate-spin">refresh</span>
        <span v-else class="mi mi-sm">lock_reset</span>
        {{ savingPw ? 'পরিবর্তন হচ্ছে...' : 'পাসওয়ার্ড পরিবর্তন করুন' }}
      </button>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-2xl shadow-card p-6 border border-red-100">
      <h2 class="font-display font-bold text-lg mb-2 flex items-center gap-2 text-accent"><span class="mi">warning</span>{{ tr('অ্যাকাউন্ট মুছুন') }}</h2>
      <p class="font-bengali text-gray-500 text-sm mb-4">{{ tr('এই কাজটি স্থায়ী এবং পুনরুদ্ধারযোগ্য নয়। আপনার সব ডেটা মুছে যাবে।') }}</p>
      <button @click="confirmDelete" class="text-sm font-bengali font-semibold text-accent border border-accent px-5 py-2.5 rounded-xl hover:bg-accent hover:text-white transition-colors flex items-center gap-2">
        <span class="mi mi-sm">delete_forever</span>{{ tr('অ্যাকাউন্ট মুছে ফেলুন') }}
      </button>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
import { useAuthStore } from '~/stores/auth'

const api       = useApi()
const authStore = useAuthStore()
const config    = useRuntimeConfig()
const apiBase   = config.public.apiBase

const saving        = ref(false)
const savingPw      = ref(false)
const successMsg    = ref('')
const errorMsg      = ref('')
const showCurrentPw = ref(false)
const showNewPw     = ref(false)
const showConfirmPw = ref(false)

const form = reactive({
  name:               authStore.user?.name               ?? '',
  email:              authStore.user?.email              ?? '',
  date_of_birth:      authStore.user?.date_of_birth      ?? '',
  gender:             authStore.user?.gender             ?? '',
  preferred_language: authStore.user?.preferred_language ?? 'bn',
  phone:              '',
})

const pwForm = reactive({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

const avatarSrc = computed(() => {
  const a = authStore.user?.avatar
  if (!a) return `https://ui-avatars.com/api/?name=${encodeURIComponent(form.name)}&background=166534&color=fff&size=96`
  return a.startsWith('http') ? a : `${apiBase}/storage/${a}`
})

const joinDate = computed(() => {
  const d = authStore.user?.created_at
  return d ? new Date(d).toLocaleDateString('bn-BD', { year: 'numeric', month: 'long', day: 'numeric' }) : ''
})

const genders = [
  { value: 'male',   label: 'পুরুষ',  icon: 'man',   color: 'text-blue-500'  },
  { value: 'female', label: 'মহিলা',  icon: 'woman', color: 'text-pink-500'  },
  { value: 'other',  label: 'অন্যান্য',icon:'person', color: 'text-gray-500' },
]

// Password strength
const pwStrength = computed(() => {
  const p = pwForm.password
  if (!p) return 0
  let s = 0
  if (p.length >= 8)           s++
  if (/[A-Z]/.test(p))        s++
  if (/[0-9]/.test(p))        s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})
const pwStrengthColor    = computed(() => ['','bg-red-400','bg-yellow-400','bg-blue-400','bg-green-500'][pwStrength.value] || 'bg-gray-200')
const pwStrengthTextColor = computed(() => ['','text-red-500','text-yellow-600','text-blue-500','text-green-600'][pwStrength.value])
const pwStrengthLabel    = computed(() => ['','দুর্বল','মোটামুটি','ভালো','শক্তিশালী'][pwStrength.value])

const showMsg = (type, msg) => {
  if (type === 'success') { successMsg.value = msg; setTimeout(() => successMsg.value = '', 3500) }
  else                    { errorMsg.value   = msg; setTimeout(() => errorMsg.value   = '', 4000) }
}

const saveProfile = async () => {
  if (!form.name.trim()) { showMsg('error', 'নাম আবশ্যক।'); return }
  saving.value = true
  try {
    const data = await api.put('/user/profile', {
      name:               form.name,
      email:              form.email,
      date_of_birth:      form.date_of_birth || undefined,
      gender:             form.gender        || undefined,
      preferred_language: form.preferred_language,
      phone:              (!authStore.user?.phone && form.phone.trim()) ? form.phone.trim() : undefined,
    })
    // Update auth store user
    authStore.user = { ...authStore.user, ...data }
    showMsg('success', 'প্রোফাইল সফলভাবে আপডেট হয়েছে!')
  } catch (e) {
    showMsg('error', e.message || 'আপডেট করতে সমস্যা হয়েছে।')
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  if (pwForm.password !== pwForm.password_confirmation) return
  if (pwForm.password.length < 8) { showMsg('error', 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।'); return }
  savingPw.value = true
  try {
    await api.put('/user/password', {
      current_password:      pwForm.current_password,
      password:              pwForm.password,
      password_confirmation: pwForm.password_confirmation,
    })
    pwForm.current_password = ''
    pwForm.password = ''
    pwForm.password_confirmation = ''
    showMsg('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে!')
  } catch (e) {
    showMsg('error', e.message || 'পাসওয়ার্ড পরিবর্তন ব্যর্থ হয়েছে।')
  } finally {
    savingPw.value = false
  }
}

const uploadAvatar = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const formData = new FormData()
  formData.append('avatar', file)
  try {
    const data = await api.upload('/user/avatar', formData)
    authStore.user = { ...authStore.user, avatar: data.avatar }
    showMsg('success', 'প্রোফাইল ছবি আপডেট হয়েছে!')
  } catch (e) {
    showMsg('error', e.message)
  }
}

const confirmDelete = () => {
  if (confirm('আপনি কি সত্যিই অ্যাকাউন্ট মুছে ফেলতে চান? এটি স্থায়ী।')) {
    api.delete('/user/account').then(() => {
      authStore.logout()
      navigateTo('/')
    }).catch(e => showMsg('error', e.message))
  }
}

// Sync form when auth store user loads
watch(() => authStore.user, (u) => {
  if (!u) return
  form.name               = u.name               ?? ''
  form.email              = u.email              ?? ''
  form.date_of_birth      = u.date_of_birth      ?? ''
  form.gender             = u.gender             ?? ''
  form.preferred_language = u.preferred_language ?? 'bn'
}, { immediate: true })
</script>

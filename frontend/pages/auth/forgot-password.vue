<template>
  <div class="min-h-screen bg-gradient-to-br from-primary/5 via-cream to-accent/5 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <NuxtLink to="/" class="inline-flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-primary flex items-center justify-center">
            <span class="text-white font-display font-bold text-xl">বা</span>
          </div>
          <div class="font-display font-bold text-2xl text-primary">{{ branding.shop_name_bn || branding.shop_name }}</div>
        </NuxtLink>
      </div>

      <div class="bg-white rounded-2xl shadow-card p-8">

        <!-- Error -->
        <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali flex items-center gap-2">
          <span class="mi mi-sm">error_outline</span>{{ errorMsg }}
        </div>

        <!-- ── Step 1: Enter Phone ───────────────────── -->
        <div v-if="step === 1">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="mi text-primary" style="font-size:36px">lock_reset</span>
            </div>
            <h2 class="font-display font-bold text-2xl text-gray-800">{{ tr('পাসওয়ার্ড ভুলে গেছেন?') }}</h2>
            <p class="font-bengali text-gray-500 text-sm mt-2">{{ tr('আপনার মোবাইল নম্বরে OTP পাঠানো হবে') }}</p>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল নম্বর') }}</label>
              <div class="flex gap-2">
                <div class="flex items-center gap-1.5 bg-gray-50 border-2 border-gray-200 rounded-xl px-3 shrink-0">
                  <span class="mi mi-sm text-gray-500">flag</span>
                  <span class="text-sm font-semibold text-gray-600">+880</span>
                </div>
                <div class="relative flex-1">
                  <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">phone</span>
                  <input v-model="phone" type="tel" placeholder="01XXXXXXXXX"
                         class="input-field pl-10" @keyup.enter="sendOtp"/>
                </div>
              </div>
            </div>
            <button @click="sendOtp" :disabled="loading || !phone.trim()"
                    class="w-full btn-primary justify-center py-3.5 disabled:opacity-60">
              <span v-if="loading" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi mi-sm">send</span>
              {{ loading ? 'পাঠানো হচ্ছে...' : 'OTP পাঠান' }}
            </button>
            <NuxtLink to="/auth/login"
                      class="block text-center text-sm font-bengali text-primary hover:underline flex items-center justify-center gap-1">
              <span class="mi mi-sm">arrow_back</span>{{ tr('লগইন পেজে ফিরে যান') }}
            </NuxtLink>
          </div>
        </div>

        <!-- ── Step 2: Enter OTP ─────────────────────── -->
        <div v-else-if="step === 2">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="mi text-green-600" style="font-size:36px">sms</span>
            </div>
            <h2 class="font-display font-bold text-2xl text-gray-800">{{ tr('OTP যাচাই করুন') }}</h2>
            <p class="font-bengali text-gray-500 text-sm mt-2">
              +880 {{ phone }} নম্বরে ৬ সংখ্যার OTP পাঠানো হয়েছে
            </p>
          </div>

          <!-- OTP Boxes -->
          <div class="flex gap-2 justify-center mb-6">
            <input v-for="i in 6" :key="i"
                   :ref="el => { if (el) otpRefs[i-1] = el }"
                   v-model="otpDigits[i-1]"
                   type="text" inputmode="numeric" maxlength="1"
                   class="w-12 h-12 border-2 border-gray-200 focus:border-primary rounded-xl text-center font-display font-bold text-xl outline-none transition-colors"
                   :class="otpDigits[i-1] ? 'border-primary bg-primary/5' : ''"
                   @input="onOtpInput(i-1, $event)"
                   @keydown.backspace="onOtpBackspace(i-1)"/>
          </div>

          <button @click="verifyOtp" :disabled="loading || otp.length < 6"
                  class="w-full btn-primary justify-center py-3.5 mb-3 disabled:opacity-60">
            <span v-if="loading" class="mi mi-sm animate-spin">refresh</span>
            <span v-else class="mi mi-sm">verified</span>
            {{ loading ? 'যাচাই হচ্ছে...' : 'যাচাই করুন' }}
          </button>

          <p class="text-center text-sm font-bengali text-gray-500">
            {{ tr('OTP পাননি?') }}
            <button @click="resendOtp" :disabled="resendCooldown > 0 || loading"
                    class="text-primary font-semibold hover:underline disabled:opacity-50 ml-1">
              {{ resendCooldown > 0 ? `পুনরায় পাঠান (${resendCooldown}s)` : 'পুনরায় পাঠান' }}
            </button>
          </p>
        </div>

        <!-- ── Step 3: New Password ───────────────────── -->
        <div v-else-if="step === 3">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-accent/10 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="mi text-accent" style="font-size:36px">lock_open</span>
            </div>
            <h2 class="font-display font-bold text-2xl text-gray-800">{{ tr('নতুন পাসওয়ার্ড') }}</h2>
            <p class="font-bengali text-gray-500 text-sm mt-2">{{ tr('নতুন পাসওয়ার্ড সেট করুন') }}</p>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('নতুন পাসওয়ার্ড') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                <input v-model="newPassword" :type="showPw ? 'text' : 'password'"
                       :placeholder="tr('কমপক্ষে ৮ অক্ষর')" class="input-field pl-10 pr-10" @keyup.enter="resetPassword"/>
                <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2">
                  <span class="mi mi-sm text-gray-400">{{ showPw ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <!-- Strength bar -->
              <div v-if="newPassword" class="mt-2">
                <div class="flex gap-1 mb-1">
                  <div v-for="i in 4" :key="i" class="flex-1 h-1 rounded-full transition-colors"
                       :class="i <= pwStrength ? pwStrengthColor : 'bg-gray-200'"></div>
                </div>
                <p class="text-xs font-bengali" :class="pwStrengthTextColor">{{ pwStrengthLabel }}</p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পাসওয়ার্ড নিশ্চিত করুন') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                <input v-model="confirmPassword" :type="showPw ? 'text' : 'password'"
                       :placeholder="tr('পুনরায় পাসওয়ার্ড লিখুন')" class="input-field pl-10" @keyup.enter="resetPassword"/>
              </div>
              <p v-if="confirmPassword && newPassword !== confirmPassword"
                 class="text-xs text-accent mt-1 font-bengali">{{ tr('পাসওয়ার্ড মেলেনি।') }}</p>
            </div>
            <button @click="resetPassword"
                    :disabled="loading || newPassword.length < 8 || newPassword !== confirmPassword"
                    class="w-full btn-primary justify-center py-3.5 disabled:opacity-60">
              <span v-if="loading" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi mi-sm">check_circle</span>
              {{ loading ? 'পরিবর্তন হচ্ছে...' : 'পাসওয়ার্ড পরিবর্তন করুন' }}
            </button>
          </div>
        </div>

        <!-- ── Step 4: Done ──────────────────────────── -->
        <div v-else class="text-center py-4">
          <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="mi text-5xl text-green-500">check_circle</span>
          </div>
          <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">{{ tr('পাসওয়ার্ড পরিবর্তন সম্পন্ন!') }}</h2>
          <p class="font-bengali text-gray-500 mb-6">{{ tr('আপনার নতুন পাসওয়ার্ড দিয়ে লগইন করুন।') }}</p>
          <NuxtLink to="/auth/login" class="btn-primary inline-flex">
            <span class="mi mi-sm">login</span>{{ tr('লগইন করুন') }}
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { branding } = useAppearance()
definePageMeta({ layout: 'default' })

const api = useApi()

const step          = ref(1)
const phone         = ref('')
const otp           = computed(() => otpDigits.value.join(''))
const otpDigits     = ref(['','','','','',''])
const otpRefs       = ref([])
const newPassword   = ref('')
const confirmPassword = ref('')
const showPw        = ref(false)
const loading       = ref(false)
const errorMsg      = ref('')
const resendCooldown = ref(0)
let cooldownTimer   = null

// Password strength
const pwStrength = computed(() => {
  const p = newPassword.value
  if (!p) return 0
  let s = 0
  if (p.length >= 8)           s++
  if (/[A-Z]/.test(p))        s++
  if (/[0-9]/.test(p))        s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})
const pwStrengthColor    = computed(() => ['','bg-red-400','bg-yellow-400','bg-blue-400','bg-green-500'][pwStrength.value])
const pwStrengthTextColor = computed(() => ['','text-red-500','text-yellow-600','text-blue-500','text-green-600'][pwStrength.value])
const pwStrengthLabel    = computed(() => ['','দুর্বল','মোটামুটি','ভালো','শক্তিশালী'][pwStrength.value])

// OTP input handlers
const onOtpInput = (idx, e) => {
  const val = e.target.value.replace(/\D/g, '')
  otpDigits.value[idx] = val.slice(-1)
  if (val && idx < 5) nextTick(() => otpRefs.value[idx + 1]?.focus())
}
const onOtpBackspace = (idx) => {
  if (!otpDigits.value[idx] && idx > 0) {
    otpDigits.value[idx - 1] = ''
    nextTick(() => otpRefs.value[idx - 1]?.focus())
  }
}

const startCooldown = () => {
  resendCooldown.value = 60
  clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}

const sendOtp = async () => {
  errorMsg.value = ''
  if (!phone.value.trim()) return
  loading.value = true
  try {
    await api.post('/auth/send-otp', { phone: phone.value.trim(), type: 'forgot_password' })
    step.value = 2
    startCooldown()
  } catch (e) {
    errorMsg.value = e.message || 'OTP পাঠাতে সমস্যা হয়েছে।'
  } finally {
    loading.value = false
  }
}

const resendOtp = async () => {
  if (resendCooldown.value > 0) return
  errorMsg.value = ''
  loading.value = true
  try {
    await api.post('/auth/send-otp', { phone: phone.value.trim(), type: 'forgot_password' })
    otpDigits.value = ['','','','','','']
    startCooldown()
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

const verifyOtp = async () => {
  errorMsg.value = ''
  if (otp.value.length < 6) return
  loading.value = true
  try {
    await api.post('/auth/verify-otp', { phone: phone.value.trim(), otp: otp.value })
    step.value = 3
  } catch (e) {
    errorMsg.value = e.message || 'OTP সঠিক নয়। আবার চেষ্টা করুন।'
  } finally {
    loading.value = false
  }
}

const resetPassword = async () => {
  errorMsg.value = ''
  if (newPassword.value.length < 8 || newPassword.value !== confirmPassword.value) return
  loading.value = true
  try {
    await api.post('/auth/reset-password', {
      phone:                phone.value.trim(),
      otp:                  otp.value,
      password:             newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    step.value = 4
  } catch (e) {
    errorMsg.value = e.message || 'পাসওয়ার্ড পরিবর্তন করতে সমস্যা হয়েছে।'
  } finally {
    loading.value = false
  }
}

onUnmounted(() => clearInterval(cooldownTimer))
</script>

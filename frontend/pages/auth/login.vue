<template>
  <div class="min-h-screen bg-gradient-to-br from-primary/5 via-cream to-accent/5 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

      <!-- Logo -->
      <div class="text-center mb-8">
        <NuxtLink to="/" class="inline-flex items-center gap-3">
          <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center">
            <span class="text-white font-display font-bold text-2xl">বা</span>
          </div>
          <div class="text-left">
            <div class="font-display font-bold text-3xl text-primary">{{ branding.shop_name_bn || branding.shop_name }}</div>
            <div class="text-sm text-gray-400 font-bengali">Bangladesh's Online Marketplace</div>
          </div>
        </NuxtLink>
      </div>

      <div class="bg-white rounded-2xl shadow-card overflow-hidden">

        <!-- Tabs: Login / Register -->
        <div class="grid grid-cols-2 border-b border-gray-100">
          <button @click="switchTab('login')"
                  class="py-4 font-display font-bold text-base transition-colors flex items-center justify-center gap-2"
                  :class="activeTab==='login' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-50'">
            <span class="mi mi-sm">login</span>{{ tr('লগইন') }}
          </button>
          <button @click="switchTab('register')"
                  class="py-4 font-display font-bold text-base transition-colors flex items-center justify-center gap-2"
                  :class="activeTab==='register' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-50'">
            <span class="mi mi-sm">person_add</span>{{ tr('নিবন্ধন') }}
          </button>
        </div>

        <div class="p-6">
          <!-- Error Banner -->
          <div v-if="authStore.error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali flex items-center gap-2">
            <span class="mi mi-sm">error_outline</span>{{ authStore.error }}
          </div>

          <!-- ── LOGIN FORM ── -->
          <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">

            <!-- Login method toggle -->
            <div class="flex gap-2 mb-2">
              <button type="button" @click="loginMethod='password'"
                      class="flex-1 py-2 rounded-xl text-sm font-bengali font-semibold border-2 transition-colors"
                      :class="loginMethod==='password' ? 'bg-primary/10 border-primary text-primary' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                <span class="mi mi-sm align-middle">lock</span> {{ tr('পাসওয়ার্ড') }}
              </button>
              <button type="button" @click="loginMethod='otp'"
                      class="flex-1 py-2 rounded-xl text-sm font-bengali font-semibold border-2 transition-colors"
                      :class="loginMethod==='otp' ? 'bg-primary/10 border-primary text-primary' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                <span class="mi mi-sm align-middle">sms</span> OTP
              </button>
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ফোন নম্বর বা ইমেইল') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">alternate_email</span>
                <input v-model="loginForm.phone" type="text" :placeholder="tr('01XXXXXXXXX বা example@email.com')" class="input-field pl-10" autocomplete="username" required/>
              </div>
            </div>

            <!-- Password mode -->
            <template v-if="loginMethod === 'password'">
              <div>
                <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পাসওয়ার্ড') }}</label>
                <div class="relative">
                  <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                  <input v-model="loginForm.password" :type="showPass ? 'text' : 'password'"
                         :placeholder="tr('পাসওয়ার্ড লিখুন')" class="input-field pl-10 pr-12" required/>
                  <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <span class="mi mi-sm">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
                  </button>
                </div>
              </div>
              <div class="flex justify-end">
                <NuxtLink to="/auth/forgot-password" class="text-sm font-bengali text-primary hover:underline flex items-center gap-1">
                  <span class="mi mi-sm">help_outline</span>{{ tr('পাসওয়ার্ড ভুলে গেছেন?') }}
                </NuxtLink>
              </div>
            </template>

            <!-- OTP mode -->
            <template v-else>
              <div v-if="!otpSent">
                <button type="button" @click="sendLoginOtp" :disabled="authStore.loading || !loginForm.phone"
                        class="w-full btn-secondary py-3 justify-center disabled:opacity-60">
                  <span v-if="authStore.loading" class="mi mi-sm animate-spin">refresh</span>
                  <span v-else class="mi mi-sm">send</span>
                  {{ authStore.loading ? 'পাঠানো হচ্ছে...' : 'OTP পাঠান' }}
                </button>
              </div>
              <div v-else>
                <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">
                  {{ tr('OTP কোড') }}
                  <span class="text-gray-400 font-normal ml-1">({{ loginForm.phone }}-এ পাঠানো হয়েছে)</span>
                </label>
                <div class="flex gap-2 mb-2">
                  <input v-for="i in 6" :key="i"
                         :ref="el => { if(el) otpInputRefs[i-1]=el }"
                         v-model="otpDigits[i-1]" type="text" inputmode="numeric" maxlength="1"
                         class="w-10 h-10 border-2 border-gray-200 focus:border-primary rounded-xl text-center font-display font-bold text-lg outline-none"
                         :class="otpDigits[i-1] ? 'border-primary bg-primary/5' : ''"
                         @input="onOtpInput(i-1, $event)"
                         @keydown.backspace="onOtpBackspace(i-1)"/>
                </div>
                <button type="button" @click="resendLoginOtp" :disabled="resendCooldown > 0"
                        class="text-xs font-bengali text-primary hover:underline disabled:opacity-50">
                  {{ resendCooldown > 0 ? `পুনরায় পাঠান (${resendCooldown}s)` : 'পুনরায় পাঠান' }}
                </button>
              </div>
            </template>

            <button type="submit" :disabled="authStore.loading || (loginMethod==='otp' && !otpSent)"
                    class="btn-primary w-full justify-center py-3.5 text-base disabled:opacity-60 disabled:cursor-not-allowed">
              <span v-if="authStore.loading" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi">login</span>
              {{ authStore.loading ? 'লোড হচ্ছে...' : 'লগইন করুন' }}
            </button>

            <!-- Social login divider -->
            <div class="relative flex items-center gap-3 my-1">
              <div class="flex-1 h-px bg-gray-200"></div>
              <span class="text-xs font-bengali text-gray-400">{{ tr('অথবা') }}</span>
              <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <!-- Google button is rendered by Google Identity Services (only when a client id is configured) -->
            <div v-if="googleClientId" ref="googleBtn" class="flex justify-center min-h-[44px]"></div>
            <p v-else-if="!facebookAppId" class="text-center text-xs font-bengali text-gray-400">
              {{ tr('Google/Facebook লগইন চালু করতে NUXT_PUBLIC_GOOGLE_CLIENT_ID / NUXT_PUBLIC_FACEBOOK_APP_ID সেট করুন।') }}
            </p>

            <!-- Facebook (only when an app id is configured) -->
            <button v-if="facebookAppId" type="button" @click="facebookLogin" :disabled="authStore.loading"
                    class="w-full h-11 rounded-xl bg-[#1877F2] hover:bg-[#166fe0] text-white font-display font-bold text-sm flex items-center justify-center gap-2 disabled:opacity-60 transition-colors">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
              {{ tr('Facebook দিয়ে চালিয়ে যান') }}
            </button>
          </form>

          <!-- ── REGISTER FORM ── -->
          <form v-else @submit.prevent="handleRegister" class="space-y-4">
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পূর্ণ নাম *') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">person</span>
                <input v-model="regForm.name" type="text" :placeholder="tr('আপনার পূর্ণ নাম')" class="input-field pl-10" required/>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল নম্বর *') }}</label>
              <div class="flex gap-2">
                <div class="flex items-center gap-1.5 bg-gray-50 border-2 border-gray-200 rounded-xl px-3 shrink-0">
                  <span class="mi mi-sm text-gray-500">flag</span>
                  <span class="text-sm font-semibold text-gray-600">+880</span>
                </div>
                <input v-model="regForm.phone" type="tel" placeholder="01XXXXXXXXX" class="input-field flex-1" required/>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ইমেইল (ঐচ্ছিক)') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">mail</span>
                <input v-model="regForm.email" type="email" placeholder="email@example.com" class="input-field pl-10"/>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পাসওয়ার্ড *') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                <input v-model="regForm.password" :type="showPass ? 'text' : 'password'"
                       :placeholder="tr('কমপক্ষে ৬ অক্ষর')" class="input-field pl-10 pr-12" required minlength="6"/>
                <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <span class="mi mi-sm">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('রেফারেল কোড (ঐচ্ছিক)') }}</label>
              <div class="relative">
                <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">card_giftcard</span>
                <input v-model="regForm.referral_code" type="text" :placeholder="tr('রেফারেল কোড থাকলে দিন')" class="input-field pl-10"/>
              </div>
            </div>
            <label class="flex items-start gap-2 cursor-pointer">
              <input v-model="regForm.agreed" type="checkbox" class="accent-primary mt-0.5" required/>
              <span class="text-xs font-bengali text-gray-600 leading-relaxed">
                আমি {{ branding.shop_name_bn || branding.shop_name }}-এর
                <NuxtLink to="/policy/terms" class="text-primary underline">{{ tr('শর্তাবলী') }}</NuxtLink> {{ tr('ও') }}
                <NuxtLink to="/policy/privacy" class="text-primary underline">{{ tr('গোপনীয়তা নীতি') }}</NuxtLink>
                {{ tr('পড়েছি এবং সম্মত আছি') }}
              </span>
            </label>

            <button type="submit" :disabled="authStore.loading || !regForm.agreed"
                    class="btn-primary w-full justify-center py-3.5 text-base disabled:opacity-60 disabled:cursor-not-allowed">
              <span v-if="authStore.loading" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi">person_add</span>
              {{ authStore.loading ? 'তৈরি হচ্ছে...' : 'অ্যাকাউন্ট তৈরি করুন' }}
            </button>
          </form>
        </div>
      </div>

      <p class="text-center text-xs text-gray-400 font-bengali mt-6 flex items-center justify-center gap-1">
        <span class="mi mi-sm text-green-600">lock</span>{{ tr('আপনার তথ্য সম্পূর্ণ নিরাপদ ও SSL এনক্রিপ্টেড') }}
      </p>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { branding } = useAppearance()
import { useAuthStore } from '~/stores/auth'
import { useCartStore } from '~/stores/cart'
import { useWishlistStore } from '~/stores/wishlist'

definePageMeta({ layout: 'default' })

const authStore     = useAuthStore()
const cartStore     = useCartStore()
const wishlistStore = useWishlistStore()
const router        = useRouter()
const route         = useRoute()

// Redirect already-logged-in users
if (authStore.isLoggedIn) {
  navigateTo(route.query.redirect?.toString() || '/account')
}

const activeTab   = ref('login')
const showPass    = ref(false)
const loginMethod = ref('password')
const otpSent     = ref(false)
const otpDigits   = ref(['','','','','',''])
const otpInputRefs = ref([])
const resendCooldown = ref(0)
let cooldownTimer = null

const loginForm = reactive({ phone: '', password: '' })
const regForm   = reactive({ name: '', phone: '', email: '', password: '', referral_code: '', agreed: false })

const computedOtp = computed(() => otpDigits.value.join(''))

const switchTab = (tab) => {
  activeTab.value   = tab
  authStore.error   = null
  otpSent.value     = false
  loginMethod.value = 'password'
  otpDigits.value   = ['','','','','','']
}

// ── OTP handlers ──────────────────────────────────────────────
const onOtpInput = (idx, e) => {
  const val = e.target.value.replace(/\D/g, '')
  otpDigits.value[idx] = val.slice(-1)
  if (val && idx < 5) nextTick(() => otpInputRefs.value[idx + 1]?.focus())
}
const onOtpBackspace = (idx) => {
  if (!otpDigits.value[idx] && idx > 0) {
    otpDigits.value[idx - 1] = ''
    nextTick(() => otpInputRefs.value[idx - 1]?.focus())
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

const sendLoginOtp = async () => {
  if (!loginForm.phone) return
  const ok = await authStore.sendLoginOtp(loginForm.phone)
  if (ok) { otpSent.value = true; startCooldown() }
}

const resendLoginOtp = async () => {
  if (resendCooldown.value > 0) return
  await sendLoginOtp()
}

// ── Login ─────────────────────────────────────────────────────
const dataLayer = useDataLayer()

const handleLogin = async () => {
  authStore.error = null
  let ok = false

  if (loginMethod.value === 'otp') {
    ok = await authStore.loginWithOtp(loginForm.phone, computedOtp.value)
  } else {
    ok = await authStore.login(loginForm.phone, loginForm.password)
  }

  if (ok) {
    dataLayer.login(loginMethod.value === 'otp' ? 'otp' : 'password')
    // Hydrate cart and wishlist after login
    await Promise.all([cartStore.fetch(), wishlistStore.fetch()])
    await router.push(route.query.redirect?.toString() || '/account')
  }
}

// ── Register ──────────────────────────────────────────────────
const handleRegister = async () => {
  authStore.error = null
  const ok = await authStore.register({
    name:          regForm.name,
    phone:         regForm.phone,
    email:         regForm.email     || undefined,
    password:      regForm.password,
    referral_code: regForm.referral_code || undefined,
  })
  if (ok) {
    dataLayer.signUp('phone')
    await Promise.all([cartStore.fetch(), wishlistStore.fetch()])
    await router.push('/account')
  }
}

// ── Google sign-in (Google Identity Services) ────────────────
const googleClientId = useRuntimeConfig().public.googleClientId
const googleBtn = ref(null)

const onGoogleCredential = async (resp) => {
  authStore.error = null
  const ok = await authStore.socialLogin('google', resp.credential)
  if (ok) {
    dataLayer.login('google')
    await Promise.all([cartStore.fetch(), wishlistStore.fetch()])
    await router.push(route.query.redirect?.toString() || '/account')
  }
}

const renderGoogleButton = () => {
  if (!googleClientId || !googleBtn.value || !window.google?.accounts?.id) return
  window.google.accounts.id.initialize({ client_id: googleClientId, callback: onGoogleCredential })
  window.google.accounts.id.renderButton(googleBtn.value, { theme: 'outline', size: 'large', width: 320, text: 'continue_with' })
}

// ── Facebook login (JS SDK, loaded on demand) ────────────────
const facebookAppId = useRuntimeConfig().public.facebookAppId
const loadFacebookSdk = () => new Promise((resolve, reject) => {
  if (window.FB) return resolve(window.FB)
  window.fbAsyncInit = () => {
    window.FB.init({ appId: facebookAppId, cookie: false, xfbml: false, version: 'v19.0' })
    resolve(window.FB)
  }
  const s = document.createElement('script')
  s.src = 'https://connect.facebook.net/en_US/sdk.js'
  s.async = true
  s.onerror = () => reject(new Error('Facebook লোড করা যায়নি।'))
  document.head.appendChild(s)
})

const facebookLogin = async () => {
  authStore.error = null
  try {
    const FB = await loadFacebookSdk()
    FB.login(async (resp) => {
      const token = resp?.authResponse?.accessToken
      if (!token) return                      // user closed the popup
      const ok = await authStore.socialLogin('facebook', token)
      if (ok) {
        dataLayer.login('facebook')
        await Promise.all([cartStore.fetch(), wishlistStore.fetch()])
        await router.push(route.query.redirect?.toString() || '/account')
      }
    }, { scope: 'public_profile,email' })
  } catch (e) {
    authStore.error = e.message
  }
}

onMounted(() => {
  if (!googleClientId) return
  if (window.google?.accounts?.id) { nextTick(renderGoogleButton); return }
  const s = document.createElement('script')
  s.src = 'https://accounts.google.com/gsi/client'
  s.async = true
  s.onload = () => nextTick(renderGoogleButton)
  document.head.appendChild(s)
})

// The button lives inside the login form only — re-render when the user switches tabs.
watch(googleBtn, (el) => { if (el) nextTick(renderGoogleButton) })

onUnmounted(() => clearInterval(cooldownTimer))
</script>
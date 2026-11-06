<template>
  <Teleport to="body">
    <Transition name="popup-fade">
      <div v-if="popup.visible" class="login-popup-overlay" @click.self="popup.close()">
        <Transition name="popup-slide">
          <div v-if="popup.visible" class="login-popup-card" role="dialog" aria-modal="true" :aria-label="tr('লগইন করুন')">

            <!-- Close -->
            <button @click="popup.close()" class="popup-close" :aria-label="tr('বন্ধ করুন')">
              <span class="mi mi-sm">close</span>
            </button>

            <!-- Header -->
            <div class="popup-header">
              <div class="popup-logo">
                <span class="text-white font-display font-bold text-xl">বা</span>
              </div>
              <div>
                <h2 class="font-display font-bold text-xl text-gray-900">{{ tr('লগইন করুন') }}</h2>
                <p class="text-sm font-bengali text-gray-500 mt-0.5">{{ tr('কার্ট বা উইশলিস্ট ব্যবহার করতে লগইন করুন') }}</p>
              </div>
            </div>

            <!-- Error -->
            <div v-if="authStore.error" class="error-banner">
              <span class="mi mi-sm">error_outline</span>
              <span class="font-bengali text-sm">{{ authStore.error }}</span>
            </div>

            <!-- Tabs -->
            <div class="tab-row">
              <button @click="activeTab = 'login'"
                      class="tab-btn"
                      :class="activeTab === 'login' ? 'tab-active' : 'tab-inactive'">
                <span class="mi mi-sm">login</span> {{ tr('লগইন') }}
              </button>
              <button @click="activeTab = 'register'"
                      class="tab-btn"
                      :class="activeTab === 'register' ? 'tab-active' : 'tab-inactive'">
                <span class="mi mi-sm">person_add</span> {{ tr('নিবন্ধন') }}
              </button>
            </div>

            <!-- ── LOGIN FORM ── -->
            <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="popup-form">

              <!-- Login method -->
              <div class="method-toggle">
                <button type="button" @click="loginMethod = 'password'"
                        class="method-btn" :class="loginMethod === 'password' ? 'method-active' : 'method-inactive'">
                  <span class="mi mi-sm align-middle">lock</span> {{ tr('পাসওয়ার্ড') }}
                </button>
                <button type="button" @click="loginMethod = 'otp'"
                        class="method-btn" :class="loginMethod === 'otp' ? 'method-active' : 'method-inactive'">
                  <span class="mi mi-sm align-middle">sms</span> OTP
                </button>
              </div>

              <!-- Phone -->
              <div class="field-group">
                <label class="field-label">{{ tr('ফোন বা ইমেইল') }}</label>
                <div class="relative">
                  <span class="mi mi-sm field-icon">alternate_email</span>
                  <input v-model="loginForm.phone" type="text" :placeholder="tr('01XXXXXXXXX বা example@email.com')" class="input-field pl-10" autocomplete="username" required />
                </div>
              </div>

              <!-- Password mode -->
              <template v-if="loginMethod === 'password'">
                <div class="field-group">
                  <label class="field-label">{{ tr('পাসওয়ার্ড') }}</label>
                  <div class="relative">
                    <span class="mi mi-sm field-icon">lock</span>
                    <input v-model="loginForm.password" :type="showPass ? 'text' : 'password'"
                           :placeholder="tr('পাসওয়ার্ড লিখুন')" class="input-field pl-10 pr-12" required />
                    <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                      <span class="mi mi-sm">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
                    </button>
                  </div>
                </div>
                <button type="submit" :disabled="authStore.loading" class="btn-primary w-full justify-center">
                  <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                  </svg>
                  <span class="mi mi-sm" v-else>login</span>
                  {{ tr('লগইন করুন') }}
                </button>
              </template>

              <!-- OTP mode -->
              <template v-else>
                <div v-if="!otpSent">
                  <button type="button" @click="handleSendOtp" :disabled="authStore.loading" class="btn-primary w-full justify-center">
                    <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span class="mi mi-sm" v-else>sms</span>
                    {{ tr('OTP পাঠান') }}
                  </button>
                </div>
                <template v-else>
                  <div class="field-group">
                    <label class="field-label">{{ tr('OTP কোড') }}</label>
                    <div class="relative">
                      <span class="mi mi-sm field-icon">pin</span>
                      <input v-model="otpCode" type="text" inputmode="numeric" maxlength="6" :placeholder="tr('6 সংখ্যার OTP')" class="input-field pl-10 tracking-widest text-center font-bold" required />
                    </div>
                  </div>
                  <button type="submit" :disabled="authStore.loading" class="btn-primary w-full justify-center">
                    <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span class="mi mi-sm" v-else>verified</span>
                    {{ tr('যাচাই করুন') }}
                  </button>
                </template>
              </template>

            </form>

            <!-- ── REGISTER FORM ── -->
            <form v-else @submit.prevent="handleRegister" class="popup-form">

              <div class="field-group">
                <label class="field-label">{{ tr('পুরো নাম') }}</label>
                <div class="relative">
                  <span class="mi mi-sm field-icon">person</span>
                  <input v-model="registerForm.name" type="text" :placeholder="tr('আপনার নাম')" class="input-field pl-10" required />
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">{{ tr('ফোন বা ইমেইল') }}</label>
                <div class="relative">
                  <span class="mi mi-sm field-icon">phone</span>
                  <input v-model="registerForm.phone" type="tel" placeholder="01XXXXXXXXX" class="input-field pl-10" required />
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">{{ tr('পাসওয়ার্ড') }}</label>
                <div class="relative">
                  <span class="mi mi-sm field-icon">lock</span>
                  <input v-model="registerForm.password" :type="showRegPass ? 'text' : 'password'"
                         :placeholder="tr('পাসওয়ার্ড তৈরি করুন')" class="input-field pl-10 pr-12" required />
                  <button type="button" @click="showRegPass = !showRegPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <span class="mi mi-sm">{{ showRegPass ? 'visibility_off' : 'visibility' }}</span>
                  </button>
                </div>
              </div>

              <button type="submit" :disabled="authStore.loading" class="btn-secondary w-full justify-center">
                <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="mi mi-sm" v-else>person_add</span>
                {{ tr('নিবন্ধন করুন') }}
              </button>

            </form>

            <!-- Full login page link -->
            <p class="text-center text-xs font-bengali text-gray-400 mt-4">
              <NuxtLink to="/auth/login" @click="popup.close()" class="text-primary hover:underline">
                {{ tr('সম্পূর্ণ লগইন পেজ দেখুন') }}
              </NuxtLink>
            </p>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const { tr } = useLang()
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAuthPopupStore } from '~/stores/AuthPopup'

const authStore = useAuthStore()
const popup     = useAuthPopupStore()

const activeTab    = ref('login')
const loginMethod  = ref('password')
const showPass     = ref(false)
const showRegPass  = ref(false)
const otpSent      = ref(false)
const otpCode      = ref('')

const loginForm = ref({ phone: '', password: '' })
const registerForm = ref({ name: '', phone: '', password: '' })

const handleLogin = async () => {
  let ok = false
  if (loginMethod.value === 'password') {
    ok = await authStore.login(loginForm.value.phone, loginForm.value.password)
  } else {
    ok = await authStore.loginWithOtp(loginForm.value.phone, otpCode.value)
  }
  if (ok) popup.onLoginSuccess()
}

const handleSendOtp = async () => {
  if (!loginForm.value.phone) return
  const ok = await authStore.sendLoginOtp(loginForm.value.phone)
  if (ok) otpSent.value = true
}

const handleRegister = async () => {
  const ok = await authStore.register(registerForm.value)
  if (ok) popup.onLoginSuccess()
}
</script>

<style scoped>
.login-popup-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.login-popup-card {
  background: #fff;
  border-radius: 1.5rem;
  padding: 1.75rem;
  width: 100%;
  max-width: 420px;
  position: relative;
  box-shadow: 0 25px 60px rgba(0,0,0,0.2);
}

.popup-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  transition: background 0.15s;
}
.popup-close:hover { background: #e5e7eb; }

.popup-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}

.popup-logo {
  width: 3rem;
  height: 3rem;
  border-radius: 0.875rem;
  background: var(--color-primary, #166534);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.75rem;
  padding: 0.625rem 0.875rem;
  color: #b91c1c;
  margin-bottom: 1rem;
}

.tab-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border: 2px solid #e5e7eb;
  border-radius: 0.875rem;
  overflow: hidden;
  margin-bottom: 1.25rem;
}
.tab-btn {
  padding: 0.625rem 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  transition: all 0.15s;
  font-family: inherit;
}
.tab-active   { background: #166534; color: #fff; }
.tab-inactive { color: #6b7280; }
.tab-inactive:hover { background: #f9fafb; }

.popup-form { display: flex; flex-direction: column; gap: 0.875rem; }

.method-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}
.method-btn {
  padding: 0.5rem;
  border-radius: 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  border: 2px solid transparent;
  transition: all 0.15s;
  font-family: inherit;
}
.method-active   { background: rgba(22,101,52,0.08); border-color: #166534; color: #166534; }
.method-inactive { border-color: #e5e7eb; color: #6b7280; }
.method-inactive:hover { border-color: #d1d5db; }

.field-group { display: flex; flex-direction: column; gap: 0.375rem; }
.field-label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.field-icon  { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }

/* Transitions */
.popup-fade-enter-active, .popup-fade-leave-active { transition: opacity 0.25s ease; }
.popup-fade-enter-from,   .popup-fade-leave-to     { opacity: 0; }

.popup-slide-enter-active { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease; }
.popup-slide-leave-active { transition: transform 0.2s ease, opacity 0.2s ease; }
.popup-slide-enter-from   { transform: translateY(24px) scale(0.96); opacity: 0; }
.popup-slide-leave-to     { transform: translateY(8px) scale(0.98); opacity: 0; }
</style>
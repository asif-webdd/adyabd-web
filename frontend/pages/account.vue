<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <div class="flex gap-6">
      <!-- ── Sidebar ─────────────────────────────────── -->
      <aside class="w-64 shrink-0 hidden lg:block">
        <!-- User Card -->
        <div class="bg-gradient-to-br from-primary to-primary-dark text-white rounded-2xl p-5 mb-4">
          <div class="flex items-center gap-3 mb-4">
            <img :src="avatarSrc" :alt="user?.name" class="w-14 h-14 rounded-full object-cover border-2 border-white/30 shrink-0"/>
            <div>
              <p class="font-display font-bold text-base">{{ user?.name ?? 'লোড হচ্ছে...' }}</p>
              <p class="text-green-200 text-xs font-bengali flex items-center gap-0.5">
                <span class="mi mi-sm">phone</span>{{ user?.phone ?? '' }}
              </p>
              <div class="flex items-center gap-1 mt-0.5">
                <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div>
                <span class="text-green-300 text-xs font-bengali">{{ tr('অনলাইন') }}</span>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-2 pt-3 border-t border-white/20 text-center">
            <div>
              <p class="font-bold text-base">{{ dashboard.total_orders ?? '—' }}</p>
              <p class="text-green-200 text-xs font-bengali">{{ tr('অর্ডার') }}</p>
            </div>
            <div>
              <p class="font-bold text-base">{{ wishlistStore.count }}</p>
              <p class="text-green-200 text-xs font-bengali">{{ tr('উইশলিস্ট') }}</p>
            </div>
            <div>
              <p class="font-bold text-base">৳{{ walletBal }}</p>
              <p class="text-green-200 text-xs font-bengali">{{ tr('ওয়ালেট') }}</p>
            </div>
          </div>
        </div>

        <!-- Wallet Quick -->
        <div class="bg-white rounded-2xl shadow-card p-4 mb-4 flex items-center justify-between">
          <div>
            <p class="font-bengali text-xs text-gray-500 flex items-center gap-0.5">
              <span class="mi mi-sm text-primary">account_balance_wallet</span>{{ tr('ওয়ালেট') }}
            </p>
            <p class="font-display font-bold text-primary text-lg">৳{{ walletBal }}</p>
          </div>
          <NuxtLink to="/account/wallet" class="text-xs font-bengali text-primary hover:underline flex items-center gap-0.5">
            {{ tr('বিস্তারিত') }} <span class="mi mi-sm">arrow_forward</span>
          </NuxtLink>
        </div>

        <!-- Nav Menu -->
        <nav class="bg-white rounded-2xl shadow-card overflow-hidden">
          <NuxtLink v-for="item in navItems" :key="item.href" :to="item.href" class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors font-bengali text-sm font-medium" active-class="text-primary bg-primary/5 border-l-4 border-l-primary font-bold">
            <span class="mi" :class="$route.path===item.href ? 'text-primary' : 'text-gray-400'">{{ item.icon }}</span>
            <span class="flex-1">{{ tr(item.label) }}</span>
            <span v-if="item.badge" class="badge bg-accent text-white text-xs">{{ item.badge }}</span>
          </NuxtLink>
        </nav>

        <!-- Logout -->
        <button @click="authStore.logout()"
                class="w-full mt-4 flex items-center gap-3 px-4 py-3 bg-white rounded-2xl shadow-card text-accent hover:bg-accent/10 transition-colors font-bengali font-medium text-sm">
          <span class="mi">logout</span> {{ tr('লগআউট করুন') }}
        </button>
      </aside>

      <!-- ── Page Content ───────────────────────────── -->
      <div class="flex-1 min-w-0">
        <!-- Mobile Nav -->
        <div class="lg:hidden mb-5 overflow-x-auto">
          <div class="flex gap-2 pb-2">
            <NuxtLink v-for="item in navItems" :key="item.href" :to="item.href" class="whitespace-nowrap flex items-center gap-1.5 px-3 py-2 rounded-full text-sm font-bengali border-2 transition-colors shrink-0 border-gray-200 text-gray-600 hover:border-primary hover:text-primary" active-class="bg-primary text-white border-primary">
              <span class="mi mi-sm">{{ item.icon }}</span>{{ item.label }}
            </NuxtLink>
          </div>
        </div>
        <NuxtPage />

        <!-- Logout (mobile / tablet: the sidebar is hidden there) -->
        <button @click="authStore.logout()"
                class="lg:hidden w-full mt-6 flex items-center justify-center gap-2 px-4 py-3 bg-white rounded-2xl shadow-card text-accent font-bengali font-semibold text-sm active:bg-accent/10">
          <span class="mi">logout</span> {{ tr('লগআউট করুন') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import { useWishlistStore } from '~/stores/wishlist'
import {useAuth} from "../composables/useAuth.js";

definePageMeta({ middleware: 'auth' })
const authStore = useAuth()
const wishlistStore = useWishlistStore()
const api           = useApi()
const config        = useRuntimeConfig()
const apiBase       = config.public.apiBase

const dashboard = ref({})
const walletBal = ref('০')
const user = computed(()=> authStore.user.value)
const avatarSrc = computed(() => {
  const a = authStore.user.value?.avatar
  if (!a) return `https://ui-avatars.com/api/?name=${encodeURIComponent(authStore.user.value?.name ?? 'U')}&background=166534&color=fff&size=96`
  return a.startsWith('http') ? a : `${apiBase}/storage/${a}`
})

const pendingCount    = computed(() => dashboard.value.pending_orders ?? 0)
const notifCount      = computed(() => dashboard.value.unread_notifications ?? 0)

const navItems = computed(() => [
  { href:'/account',                label:'ড্যাশবোর্ড',       icon:'dashboard' },
  { href:'/account/orders',         label:'আমার অর্ডার',       icon:'inventory_2',           badge: pendingCount.value > 0 ? pendingCount.value : null },
  { href:'/account/wishlist',       label:'উইশলিস্ট',          icon:'favorite',              badge: wishlistStore.count > 0 ? wishlistStore.count : null },
  { href:'/account/addresses',      label:'ঠিকানা',            icon:'location_on' },
  { href:'/account/profile',        label:'প্রোফাইল সম্পাদনা', icon:'manage_accounts' },
  { href:'/account/reviews',        label:'আমার রিভিউ',        icon:'rate_review' },
  { href:'/account/wallet',         label:'ওয়ালেট ও রিফান্ড', icon:'account_balance_wallet' },
  { href:'/account/notifications',  label:'নোটিফিকেশন',       icon:'notifications',         badge: notifCount.value > 0 ? notifCount.value : null },
  authStore.user.value?.seller
    ? { href:'/seller/dashboard', label:'বিক্রেতা ড্যাশবোর্ড', icon:'storefront' }
    : { href:'/seller/register',  label:'বিক্রেতা হোন',       icon:'add_business' },
])

onMounted(async () => {
  authStore.fetchMe().catch(() => {})   // refresh user (e.g. a seller account created/approved after login)
  try {
    const data = await api.get('/user/dashboard')
    dashboard.value = data
    walletBal.value = Number(data.wallet_balance ?? 0).toLocaleString('bn-BD', { minimumFractionDigits: 0 })
  } catch {

  }

  await wishlistStore.fetch()
})
</script>
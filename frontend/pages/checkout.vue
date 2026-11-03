<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <h1 class="section-title flex items-center gap-2 mb-5">
      <span class="mi text-primary">shopping_bag</span>Checkout
    </h1>

    <!-- Step Indicator -->
    <div class="flex items-center justify-between mb-6 relative">
      <div class="absolute left-0 right-0 top-4 h-0.5 bg-gray-200 -z-0">
        <div class="h-full bg-primary transition-all duration-500"
             :style="`width:${(currentStep / (steps.length - 1)) * 100}%`"></div>
      </div>
      <div v-for="(step, i) in steps" :key="i" class="flex flex-col items-center z-10">
        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all mb-1"
             :class="i < currentStep ? 'bg-primary border-primary text-white'
                : i === currentStep ? 'bg-white border-primary text-primary'
                : 'bg-white border-gray-200 text-gray-400'">
          <span class="mi mi-sm">{{ i < currentStep ? 'check' : step.icon }}</span>
        </div>
        <span class="text-xs hidden sm:block"
              :class="i <= currentStep ? 'text-primary font-semibold' : 'text-gray-400'">{{ step.label }}</span>
      </div>
    </div>

    <!-- Error Banner -->
    <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
      <span class="mi mi-sm">error_outline</span>{{ error }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div :class="currentStep === 3 ? 'lg:col-span-3' : 'lg:col-span-2'">

        <!-- ── Step 0: Address ─────────────────────────── -->
        <div v-if="currentStep === 0" class="bg-white rounded-2xl shadow-card p-6">
          <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
            <span class="mi text-primary">location_on</span>Delivery Address
          </h2>

          <!-- Step 0: Address skeleton -->
          <div v-if="loadingAddresses" class="space-y-3">
            <div v-for="i in 2" :key="i" class="border-2 border-gray-100 rounded-2xl p-4 animate-pulse flex items-start gap-4">
              <div class="w-4 h-4 bg-gray-200 rounded-full mt-1 shrink-0"></div>
              <div class="flex-1 space-y-2">
                <div class="flex items-center gap-2">
                  <div class="w-4 h-4 bg-gray-200 rounded-full"></div>
                  <div class="h-3.5 w-24 bg-gray-200 rounded"></div>
                </div>
                <div class="h-3 w-full bg-gray-200 rounded"></div>
                <div class="h-3 w-1/3 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>

          <div v-else class="space-y-3 mb-4">
            <label v-for="addr in addresses" :key="addr.id"
                   class="flex items-start gap-4 border-2 rounded-2xl p-4 cursor-pointer transition-all"
                   :class="selectedAddress === addr.id ? 'border-primary bg-primary/5' : 'border-gray-100 hover:border-gray-200'">
              <input type="radio" :value="addr.id" v-model="selectedAddress" class="accent-primary mt-1"/>
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                  <span class="mi mi-sm"
                        :class="addr.type==='home' ? 'text-green-500' : addr.type==='office' ? 'text-blue-500' : 'text-orange-500'">
                    {{ addr.type==='home' ? 'home' : addr.type==='office' ? 'business' : 'location_on' }}
                  </span>
                  <span class="font-display font-bold text-sm text-gray-800">{{ addr.name }}</span>
                  <span v-if="addr.is_default" class="badge bg-primary text-white text-xs">Default</span>
                </div>
                <p class="text-gray-600 text-sm">
                  {{ addr.address_line }}{{ addr.upazila ? ', ' + gn(addr.upazila) : '' }}{{ addr.district ? ', ' + gn(addr.district) : '' }}{{ addr.division ? ', ' + gn(addr.division) : '' }}
                </p>
                <p class="text-gray-400 text-xs mt-0.5 flex items-center gap-1">
                  <span class="mi mi-sm">phone</span>{{ addr.phone }}
                </p>
              </div>
            </label>
          </div>

          <button @click="showNewAddr = !showNewAddr"
                  class="w-full border-2 border-dashed border-gray-200 rounded-2xl p-4 text-gray-500 hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2 font-medium mb-4">
            <span class="mi mi-sm">{{ showNewAddr ? 'remove' : 'add_location' }}</span>
            {{ showNewAddr ? 'Cancel' : 'Add New Address' }}
          </button>

          <div v-if="showNewAddr" class="border border-gray-100 rounded-2xl p-4 mb-4 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name *</label>
                <input v-model="newAddr.name" type="text" class="input-field text-sm" placeholder="Full name"/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Phone *</label>
                <input v-model="newAddr.phone" type="tel" class="input-field text-sm" placeholder="01XXXXXXXXX"/>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">District *</label>
                <select v-model="newAddr.district_id" @change="onNewAddrDistrictChange" class="input-field text-sm"
                        :disabled="loadingNewDistricts">
                  <option value="">{{ loadingNewDistricts ? 'Loading...' : 'Select district' }}</option>
                  <option v-for="d in newAddrDistricts" :key="d.id" :value="d.id">{{ gn(d) }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Upazila / Thana *</label>
                <select v-model="newAddr.upazila_id" class="input-field text-sm"
                        :disabled="!newAddr.district_id || loadingNewUpazilas">
                  <option value="">{{ !newAddr.district_id ? 'Select district first' : loadingNewUpazilas ? 'Loading...' : 'Select upazila / thana' }}</option>
                  <option v-for="u in newAddrUpazilas" :key="u.id" :value="u.id">{{ gn(u) }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Full Address *</label>
              <textarea v-model="newAddr.address_line" rows="2" class="input-field text-sm resize-none"
                        placeholder="House no., road, area..."></textarea>
            </div>
            <button @click="saveNewAddress" :disabled="savingAddr" class="btn-primary text-sm py-2 disabled:opacity-60">
              <span v-if="savingAddr" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi mi-sm">save</span>
              {{ savingAddr ? 'Saving...' : 'Save Address' }}
            </button>
          </div>

          <button @click="nextStep" :disabled="!selectedAddress"
                  class="w-full btn-primary justify-center py-3.5 disabled:opacity-60">
            <span class="mi mi-sm">arrow_forward</span>Continue to Delivery
          </button>
        </div>

        <!-- ── Step 1: Delivery ────────────────────────── -->
        <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-card p-6">
          <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
            <span class="mi text-primary">local_shipping</span>Delivery Method
          </h2>

          <!-- Step 1: Delivery method skeleton -->
          <div v-if="loadingConfig" class="space-y-3 mb-5">
            <div v-for="i in 3" :key="i" class="border-2 border-gray-100 rounded-2xl p-4 animate-pulse flex items-center gap-4">
              <div class="w-4 h-4 bg-gray-200 rounded-full shrink-0"></div>
              <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0"></div>
              <div class="flex-1 space-y-2">
                <div class="h-3.5 w-28 bg-gray-200 rounded"></div>
                <div class="h-3 w-40 bg-gray-200 rounded"></div>
              </div>
              <div class="h-4 w-12 bg-gray-200 rounded shrink-0"></div>
            </div>
          </div>

          <div v-else class="space-y-3 mb-5">
            <label v-for="method in checkoutConfig.delivery_methods" :key="method.id" class="flex items-center gap-4 border-2 rounded-2xl p-4 cursor-pointer transition-all" :class="selectedDelivery === method.id ? 'border-primary bg-primary/5' : 'border-gray-100 hover:border-gray-200'">
              <input type="radio" :value="method.id" v-model="selectedDelivery" class="accent-primary"/>
              <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                <span class="mi" :class="method.color">{{ method.icon }}</span>
              </div>
              <div class="flex-1">
                <p class="font-display font-bold text-sm text-gray-800">{{ method.name }}</p>
                <p class="text-gray-500 text-xs">
                  {{ method.desc }}
                  <span v-if="method.id === 'standard' && checkoutConfig.resolved_district"
                        class="text-primary font-medium"> · {{ checkoutConfig.resolved_district }}</span>
                </p>
              </div>
              <div class="text-right shrink-0">
                <p class="font-display font-bold" :class="effectiveDeliveryPrice(method) === 0 ? 'text-green-600' : 'text-gray-800'">
                  {{ effectiveDeliveryPrice(method) === 0 ? 'Free' : '৳' + effectiveDeliveryPrice(method) }}
                </p>
                <p v-if="method.id === 'standard' && freeShippingQualified" class="text-xs text-green-500 line-through">
                  ৳{{ method.price }}
                </p>
              </div>
            </label>
          </div>

          <!-- Free shipping progress -->
          <div v-if="!loadingConfig && !freeShippingQualified && checkoutConfig.free_shipping_min_order > 0" class="mb-5 p-3 bg-blue-50 rounded-xl text-sm text-blue-700 flex items-center gap-2">
            <span class="mi mi-sm">local_shipping</span>
            Add ৳{{ Math.max(0, checkoutConfig.free_shipping_min_order - cartStore.subtotal).toLocaleString() }} more for free standard delivery
          </div>

          <div class="mb-5">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-1">
              <span class="mi mi-sm text-primary">notes</span>Special Instructions (optional)
            </label>
            <textarea v-model="customerNote" rows="3" class="input-field resize-none" placeholder="Any special delivery instructions..."></textarea>
          </div>

          <div class="flex gap-3">
            <button @click="currentStep--" class="btn-outline"><span class="mi mi-sm">arrow_back</span>Back</button>
            <button @click="nextStep" class="flex-1 btn-primary justify-center py-3.5">
              <span class="mi mi-sm">arrow_forward</span>Continue to Payment
            </button>
          </div>
        </div>

        <!-- ── Step 2: Payment ─────────────────────────── -->
        <div v-if="currentStep === 2" class="bg-white rounded-2xl shadow-card p-6">
          <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
            <span class="mi text-primary">payments</span>Payment Method
          </h2>

          <!-- Step 2: Payment method skeleton -->
          <div v-if="loadingConfig" class="space-y-3 mb-5">
            <div v-for="i in 4" :key="i" class="border-2 border-gray-100 rounded-2xl p-4 animate-pulse flex items-center gap-4">
              <div class="w-4 h-4 bg-gray-200 rounded-full shrink-0"></div>
              <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0"></div>
              <div class="flex-1 space-y-2">
                <div class="h-3.5 w-32 bg-gray-200 rounded"></div>
                <div class="h-3 w-24 bg-gray-200 rounded"></div>
              </div>
              <div class="h-5 w-14 bg-gray-200 rounded-full shrink-0"></div>
            </div>
          </div>

          <div v-else class="space-y-3 mb-5">
            <label v-for="method in checkoutConfig.payment_methods" :key="method.id" class="flex items-center gap-4 border-2 rounded-2xl p-4 cursor-pointer transition-all" :class="selectedPayment === method.id ? 'border-primary bg-primary/5' : 'border-gray-100 hover:border-gray-200'">
              <input type="radio" :value="method.id" v-model="selectedPayment" class="accent-primary"/>
              <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="method.bg">
                <span class="mi" :class="method.color">{{ method.icon }}</span>
              </div>
              <div class="flex-1">
                <p class="font-display font-bold text-sm text-gray-800">{{ method.name }}</p>
                <p class="text-gray-500 text-xs">{{ method.sub }}</p>
              </div>
              <div class="shrink-0 flex items-center gap-2">
                <span v-if="method.badge" class="badge bg-green-100 text-green-700 text-xs">{{ method.badge }}</span>
              </div>
            </label>
          </div>

          <!-- ZiniPay redirect notice -->
          <div v-if="selectedPayment === 'zinipay'" class="mb-5 p-3 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800 flex items-center gap-2">
            <span class="mi mi-sm">info</span>
            You will be redirected to ZiniPay to complete payment after the order is placed.
          </div>

          <div class="flex gap-3">
            <button @click="currentStep--" class="btn-outline"><span class="mi mi-sm">arrow_back</span>Back</button>
            <button @click="placeOrder" :disabled="placing || !selectedPayment" class="flex-1 btn-primary justify-center py-3.5 disabled:opacity-60">
              <span v-if="placing" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi mi-sm">check_circle</span>
              {{ placing ? 'Placing order...' : 'Confirm Order' }}
            </button>
          </div>
        </div>

        <!-- ── Step 3: Success ─────────────────────────── -->
        <div v-if="currentStep === 3" class="bg-white rounded-2xl shadow-card p-8 text-center">
          <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="mi text-6xl text-green-500">check_circle</span>
          </div>
          <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">Order Placed Successfully!</h2>
          <p class="text-gray-500 mb-1">Your order number:</p>
          <p class="font-display font-bold text-xl text-primary mb-4">{{ placedOrderNumber }}</p>
          <p class="text-gray-400 text-sm mb-6">
            Your order will arrive {{ selectedDeliveryLabel }}.<br/>Order updates will be sent via SMS.
          </p>
          <div class="flex gap-3 justify-center flex-wrap">
            <NuxtLink :to="`/order/track?id=${placedOrderNumber}`" class="btn-primary">
              <span class="mi mi-sm">location_searching</span>Track Order
            </NuxtLink>
            <NuxtLink to="/account/orders" class="btn-outline">
              <span class="mi mi-sm">inventory_2</span>My Orders
            </NuxtLink>
            <NuxtLink to="/" class="btn-outline">
              <span class="mi mi-sm">shopping_bag</span>Continue Shopping
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- ── Sidebar: Order Summary ──────────────────── -->
      <div v-if="currentStep !== 3" class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-card p-5 sticky top-20">
          <h3 class="font-display font-bold text-base mb-4 flex items-center gap-2">
            <span class="mi text-primary">receipt_long</span>Order Summary
          </h3>

          <!--
            cartLoaded gate: cartStore data can already be populated client-side
            (e.g. persisted store / previous navigation) before the page finishes
            mounting, while SSR always starts from an empty cart. Rendering real
            numbers immediately causes a hydration text mismatch (server "৳0" vs
            client "৳8,499"). Gating on cartLoaded guarantees the very first paint
            is identical on server and client; real totals appear as a normal
            post-mount update instead of a hydration diff.
          -->

          <!-- Items skeleton -->
          <div v-if="!cartLoaded" class="space-y-3 mb-4 animate-pulse">
            <div v-for="i in 2" :key="i" class="flex gap-3">
              <div class="w-12 h-12 rounded-xl bg-gray-200 shrink-0"></div>
              <div class="flex-1 space-y-2">
                <div class="h-3 w-full bg-gray-200 rounded"></div>
                <div class="h-3 w-10 bg-gray-200 rounded"></div>
              </div>
              <div class="h-3.5 w-12 bg-gray-200 rounded shrink-0"></div>
            </div>
          </div>

          <div v-else class="space-y-3 mb-4 max-h-64 overflow-y-auto">
            <div v-for="item in cartStore.items" :key="item.id" class="flex gap-3">
              <img :src="item.product?.image || item.product?.thumbnail || item.image"
                   :alt="item.product?.name ?? item.name"
                   class="w-12 h-12 object-cover rounded-xl bg-gray-50 shrink-0"/>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-gray-700 line-clamp-2 leading-snug">{{ item.product?.name ?? item.name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">× {{ item.quantity }}</p>
              </div>
              <p class="font-display font-bold text-sm text-accent shrink-0">
                ৳{{ (Number(item.product?.price ?? item.unit_price) * item.quantity).toLocaleString() }}
              </p>
            </div>
          </div>

          <!-- Coupon -->
          <div class="flex gap-2 mb-4">
            <input v-model="couponCode" type="text" placeholder="Coupon code"
                   class="input-field flex-1 text-sm py-2" @keyup.enter="applyCoupon"/>
            <button @click="applyCoupon" :disabled="applyingCoupon || !!cartStore.coupon"
                    class="btn-secondary text-sm px-4 py-2 disabled:opacity-60">
              <span v-if="applyingCoupon" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi mi-sm">local_offer</span>
            </button>
          </div>
          <p v-if="couponMsg" class="text-xs mb-3" :class="couponSuccess ? 'text-green-600' : 'text-red-500'">
            {{ couponMsg }}
          </p>

          <!-- Totals skeleton -->
          <div v-if="!cartLoaded" class="space-y-2 border-t border-gray-50 pt-4 mb-4 animate-pulse">
            <div class="flex justify-between">
              <div class="h-3 w-16 bg-gray-200 rounded"></div>
              <div class="h-3 w-12 bg-gray-200 rounded"></div>
            </div>
            <div class="flex justify-between">
              <div class="h-3 w-20 bg-gray-200 rounded"></div>
              <div class="h-3 w-12 bg-gray-200 rounded"></div>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-3 mt-2">
              <div class="h-4 w-12 bg-gray-200 rounded"></div>
              <div class="h-4 w-16 bg-gray-200 rounded"></div>
            </div>
          </div>

          <!-- Totals -->
          <div v-else class="space-y-2 border-t border-gray-50 pt-4 mb-4">
            <div class="flex justify-between text-sm text-gray-600">
              <span>Subtotal</span>
              <span>৳{{ Number(cartStore.subtotal).toLocaleString() }}</span>
            </div>
            <div v-if="cartStore.discount > 0" class="flex justify-between text-sm text-green-600">
              <span>Coupon discount</span>
              <span>- ৳{{ Number(cartStore.discount).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-sm text-gray-600">
              <span>Delivery charge</span>
              <span :class="deliveryCharge === 0 ? 'text-green-600 font-semibold' : ''">
                {{ deliveryCharge === 0 ? 'Free' : '৳' + deliveryCharge }}
              </span>
            </div>
            <div class="flex justify-between font-display font-extrabold text-lg border-t border-gray-100 pt-3 mt-2">
              <span>Total</span>
              <span class="text-accent">৳{{ grandTotal.toLocaleString() }}</span>
            </div>
          </div>

          <div class="flex items-center justify-center gap-2 text-green-600">
            <span class="mi mi-sm">lock</span>
            <span class="text-xs">Secure & encrypted payment</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { geoName, sortGeo } from '~/composables/useGeo.js'
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import { useCartStore } from '~/stores/cart'

definePageMeta({ layout: 'default', middleware: 'auth', ssr: false })

const cartStore = useCartStore()
const api       = useApi()
const dataLayer = useDataLayer()
const route     = useRoute()
const { loadAllDistricts, loadUpazilas } = useGeo()
const { isEn } = useLang()
const gn = (row) => (typeof row === 'string' ? row : geoName(row, isEn.value))

// ── State ──────────────────────────────────────────────
const currentStep       = ref(0)
const selectedAddress   = ref(null)
const selectedDelivery  = ref('standard')
const selectedPayment   = ref(null)
const customerNote      = ref('')
const couponCode        = ref('')
const couponMsg         = ref('')
const couponSuccess     = ref(false)
const applyingCoupon    = ref(false)
const placing           = ref(false)
const error             = ref('')
const placedOrderNumber = ref('')

// True once cartStore.fetch() has resolved at least once on this page.
// Used to gate cart-derived totals/items so the first client render
// matches the server's always-empty snapshot — see template comment
// above the Order Summary card for why this matters.
const cartLoaded = ref(false)

// Becomes true once onMounted has finished restoring step/address/delivery/
// payment from the URL. The query-sync watcher (below) stays silent until
// then, so it never overwrites the URL with default values before the
// real, refreshed-from state has been applied.
const restored = ref(false)

const addresses        = ref([])
const loadingAddresses = ref(true)
const showNewAddr      = ref(false)
const savingAddr       = ref(false)
const newAddr = reactive({
  name: '', phone: '', address_line: '',
  district_id: '', upazila_id: '',
})

// Geo state for the inline "Add New Address" form: all districts → upazilas/thanas of the chosen district
const newAddrDistricts     = ref([])
const newAddrUpazilas      = ref([])
const loadingNewDistricts  = ref(false)
const loadingNewUpazilas   = ref(false)

// Checkout config from GET /api/v1/checkout/config
const loadingConfig = ref(true)
const checkoutConfig = ref({
  delivery_methods:        [],
  payment_methods:         [],
  free_shipping_min_order: 500,
})

const steps = [
  { label: 'Address',  icon: 'location_on'   },
  { label: 'Delivery', icon: 'local_shipping' },
  { label: 'Payment',  icon: 'payments'       },
  { label: 'Done',     icon: 'check_circle'   },
]

// Advances the checkout wizard. Each step's "Continue" button already
// guards its own preconditions (e.g. :disabled="!selectedAddress" on
// Step 0), so this just needs to move forward.
const nextStep = () => {
  currentStep.value++
}

// ── Helpers ────────────────────────────────────────────

// Standard delivery is free when subtotal >= threshold
const freeShippingQualified = computed(() =>
    checkoutConfig.value.free_shipping_min_order > 0 &&
    cartStore.subtotal >= checkoutConfig.value.free_shipping_min_order
)

const effectiveDeliveryPrice = (method) => {
  if (method.id === 'standard' && freeShippingQualified.value) return 0
  return method.price
}

const deliveryCharge = computed(() => {
  const method = checkoutConfig.value.delivery_methods.find(d => d.id === selectedDelivery.value)
  return method ? effectiveDeliveryPrice(method) : 0
})

const grandTotal = computed(() => Math.max(0, cartStore.total + deliveryCharge.value))

const selectedDeliveryLabel = computed(() => ({
  standard: 'within 2–3 business days',
  express:  'within 1 business day',
  same_day: 'today',
}[selectedDelivery.value] ?? ''))

// ── Data loading ───────────────────────────────────────

/**
 * Single call to GET /api/v1/checkout/config?address_id=X
 * Returns delivery methods (with city-based live prices) + enabled payment methods.
 * Re-called whenever the selected address changes so delivery charge updates.
 */
const loadCheckoutConfig = async () => {
  loadingConfig.value = true
  try {
    const params = selectedAddress.value ? `?address_id=${selectedAddress.value}` : ''
    const data = await api.get(`/checkout/config${params}`)
    if (data) {
      checkoutConfig.value = data
      // Auto-select first available delivery method if current selection got disabled
      if (data.delivery_methods?.length && !data.delivery_methods.some(d => d.id === selectedDelivery.value)) {
        selectedDelivery.value = data.delivery_methods[0].id
      }
      // Auto-select first available payment method
      if (data.payment_methods?.length && !selectedPayment.value) {
        selectedPayment.value = data.payment_methods[0].id
      }
    }
  } catch (e) {
    error.value = 'Failed to load checkout options. Please refresh.'
  } finally {
    loadingConfig.value = false
  }
}

const loadAddresses = async () => {
  loadingAddresses.value = true
  try {
    const data = await api.get('/addresses')
    addresses.value = data ?? []
    // If a selection was already restored from the URL (page refresh) and
    // it still exists in this account's address list, keep it. Otherwise
    // fall back to the default/first address as before.
    const restoredAddr = addresses.value.find(a => String(a.id) === String(selectedAddress.value))
    if (restoredAddr) {
      selectedAddress.value = restoredAddr.id
    } else {
      const def = addresses.value.find(a => a.is_default) ?? addresses.value[0]
      if (def) selectedAddress.value = def.id
    }
  } catch {}
  finally { loadingAddresses.value = false }
}

const saveNewAddress = async () => {
  if (!newAddr.name || !newAddr.phone || !newAddr.district_id || !newAddr.address_line) {
    error.value = 'Please fill in name, phone, district and full address.'; return
  }
  if (newAddrUpazilas.value.length && !newAddr.upazila_id) {
    error.value = 'Please select an upazila / thana.'; return
  }
  error.value = ''
  savingAddr.value = true
  try {
    const saved = await api.post('/addresses', {
      name:         newAddr.name,
      phone:        newAddr.phone,
      address_line: newAddr.address_line,
      district_id:  newAddr.district_id,
      upazila_id:   newAddr.upazila_id || null,
    })
    await loadAddresses()
    selectedAddress.value = saved?.id ?? addresses.value.at(-1)?.id
    showNewAddr.value = false
    Object.assign(newAddr, { name: '', phone: '', address_line: '', district_id: '', upazila_id: '' })
    newAddrUpazilas.value = []
  } catch (e) {
    error.value = e.message
  } finally {
    savingAddr.value = false
  }
}

const onNewAddrDistrictChange = async () => {
  newAddr.upazila_id = ''
  newAddrUpazilas.value = []
  if (!newAddr.district_id) return
  loadingNewUpazilas.value = true
  try { newAddrUpazilas.value = sortGeo(await loadUpazilas(newAddr.district_id), isEn.value) }
  finally { loadingNewUpazilas.value = false }
}

// Lazy-load all districts the first time the inline form is opened
watch(showNewAddr, async (val) => {
  if (val && !newAddrDistricts.value.length) {
    loadingNewDistricts.value = true
    try { newAddrDistricts.value = sortGeo(await loadAllDistricts(), isEn.value) }
    finally { loadingNewDistricts.value = false }
  }
})

const applyCoupon = async () => {
  if (!couponCode.value.trim()) return
  applyingCoupon.value = true
  couponMsg.value = ''
  const result = await cartStore.applyCoupon(couponCode.value.trim())
  couponSuccess.value = result.success
  couponMsg.value = result.success ? `Coupon applied! You saved ৳${result.data?.discount ?? 0}.` : result.message
  applyingCoupon.value = false
}

const placeOrder = async () => {
  error.value = ''
  placing.value = true
  try {
    const items = cartStore.items.map(item => ({
      product_id: item.product_id ?? item.product?.id,
      quantity:   item.quantity,
      variant_id: item.variant_id ?? null,
    }))

    const payload = {
      address_id:     selectedAddress.value,
      delivery_type:  selectedDelivery.value,
      payment_method: selectedPayment.value,
      customer_note:  customerNote.value || undefined,
      coupon_code:    cartStore.coupon?.code || undefined,
      items,
      tracking:       getTrackingContext(),
    }

    // ZiniPay: create order first, then redirect to the hosted payment page
    if (selectedPayment.value === 'zinipay') {
      const orderData = await api.post('/orders', payload)
      const orderId   = orderData?.id
      placedOrderNumber.value = orderData?.order_numbers?.length > 1 ? orderData.order_numbers.join(', ') : (orderData?.order_number ?? 'BD-' + orderId)

      const init = await api.post('/payment/zinipay/create', { order_id: orderId })
      if (init?.payment_url) { window.location.href = init.payment_url; return }

      currentStep.value = 3
      dataLayer.purchase({
        ...orderData,
        items: cartStore.items,
        //total: orderData?.total ?? grandTotal.value,
        total: data?.grand_total ?? grandTotal.value,
        shipping: deliveryCharge.value,
        coupon: cartStore.coupon,
      })
      await cartStore.fetch()
      return
    }

    const data = await api.post('/orders', payload)
    placedOrderNumber.value = data?.order_numbers?.length > 1 ? data.order_numbers.join(', ') : (data?.order_number ?? data?.id ?? 'BD-' + Date.now())
    currentStep.value = 3
    dataLayer.purchase({
      ...data,
      items: cartStore.items,
      total: data?.grand_total ?? grandTotal.value,
      shipping: deliveryCharge.value,
      coupon: cartStore.coupon,
    })
    await cartStore.fetch()
  } catch (e) {
    error.value = e.message || 'Failed to place order. Please try again.'
  } finally {
    placing.value = false
  }
}

onMounted(async () => {
  await cartStore.fetch()
  cartLoaded.value = true

  if (!cartStore.items.length) {
    if (process.client && window.$toast) {
      window.$toast({ type: 'error', title: 'আপনার কার্ট খালি', message: 'চেকআউট করার আগে কিছু পণ্য কার্টে যুক্ত করুন।', duration: 3000 })
    }
    await navigateTo('/')
    return
  }

  // Resume from a page refresh: pick up address/delivery/payment from the
  // URL *before* the initial config call, so loadCheckoutConfig() prices
  // delivery for the right address on its first request rather than the
  // generic/default one.
  const q = route.query
  if (q.address)  selectedAddress.value  = q.address
  if (q.delivery) selectedDelivery.value = q.delivery
  if (q.payment)  selectedPayment.value  = q.payment

  await Promise.all([loadAddresses(), loadCheckoutConfig()])

  // Only resume into a step if its prerequisite actually still holds —
  // e.g. don't land on Payment if the restored address no longer exists,
  // or on Delivery if the restored delivery method isn't currently offered.
  // The success step (3) is intentionally never restored: a refresh after
  // a placed order empties the cart, so the guard above already redirects
  // home in that case.
  let step = Number(q.step) || 0
  if (step > 0 && !selectedAddress.value) step = 0
  if (step > 1 && !checkoutConfig.value.delivery_methods.some(d => d.id === selectedDelivery.value)) step = 1
  currentStep.value = Math.min(step, 2)

  restored.value = true
  dataLayer.beginCheckout(cartStore)
})

// ── dataLayer: fire add_shipping_info / add_payment_info when the user
// changes their selection at checkout ──────────────────────────────────
watch(selectedDelivery, (val) => {
  if (!val) return
  dataLayer.addShippingInfo(cartStore, val)
})

watch(selectedPayment, (val) => {
  if (!val) return
  dataLayer.addPaymentInfo(cartStore, val)
})

// Reload delivery config whenever address changes so city-based charge updates
watch(selectedAddress, async (val) => {
  if (!val) return
  await loadCheckoutConfig()
})

// ── Persist wizard state to the URL in real time ────────────────────────
// Keeps ?step=&address=&delivery=&payment= in sync with the live refs so a
// page refresh resumes on the same step with the same selections instead
// of restarting at Step 0. Uses history.replaceState directly (not
// router.replace) to avoid triggering a full route navigation — and
// therefore re-running page middleware — on every single selection change.
const syncStateToUrl = () => {
  if (!process.client || !restored.value || currentStep.value >= 3) return
  const url = new URL(window.location.href)
  const params = url.searchParams

  currentStep.value      ? params.set('step', currentStep.value)         : params.delete('step')
  selectedAddress.value  ? params.set('address', selectedAddress.value)  : params.delete('address')
  selectedDelivery.value ? params.set('delivery', selectedDelivery.value): params.delete('delivery')
  selectedPayment.value  ? params.set('payment', selectedPayment.value)  : params.delete('payment')

  window.history.replaceState(window.history.state, '', url)
}

watch([currentStep, selectedAddress, selectedDelivery, selectedPayment], syncStateToUrl, { flush: 'post' })

</script>
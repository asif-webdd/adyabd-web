<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-10 pb-24 md:pb-10']">
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-card p-8 text-center">

      <!-- Verifying -->
      <div v-if="loading" class="py-10">
        <span class="mi text-5xl text-primary animate-spin block mb-4">refresh</span>
        <p class="text-gray-500">Verifying your payment…</p>
      </div>

      <!-- Cancelled by user -->
      <div v-else-if="cancelled">
        <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-6xl text-orange-500">cancel</span>
        </div>
        <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">Payment Cancelled</h2>
        <p class="text-gray-500 mb-1">Your order:</p>
        <p class="font-display font-bold text-xl text-primary mb-4">{{ orderNumber }}</p>
        <p class="text-gray-400 text-sm mb-6">Your order has been saved and is awaiting payment. You can complete it anytime from your order page.</p>
        <div class="flex gap-3 justify-center flex-wrap">
          <NuxtLink :to="`/order/track?id=${orderNumber}`" class="btn-primary">
            <span class="mi mi-sm">location_searching</span>View Order
          </NuxtLink>
          <NuxtLink to="/" class="btn-outline">
            <span class="mi mi-sm">shopping_bag</span>Continue Shopping
          </NuxtLink>
        </div>
      </div>

      <!-- Payment completed -->
      <div v-else-if="status === 'COMPLETED'">
        <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-6xl text-green-500">check_circle</span>
        </div>
        <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">Payment Successful!</h2>
        <p class="text-gray-500 mb-1">Your order number:</p>
        <p class="font-display font-bold text-xl text-primary mb-6">{{ orderNumber }}</p>
        <div class="flex gap-3 justify-center flex-wrap">
          <NuxtLink :to="`/order/track?id=${orderNumber}`" class="btn-primary">
            <span class="mi mi-sm">location_searching</span>Track Order
          </NuxtLink>
          <NuxtLink to="/account/orders" class="btn-outline">
            <span class="mi mi-sm">inventory_2</span>My Orders
          </NuxtLink>
        </div>
      </div>

      <!-- Still pending (e.g. webhook hasn't landed yet) -->
      <div v-else-if="status === 'PENDING'">
        <div class="w-24 h-24 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-6xl text-yellow-500">hourglass_top</span>
        </div>
        <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">Payment Processing</h2>
        <p class="text-gray-500 mb-1">Your order:</p>
        <p class="font-display font-bold text-xl text-primary mb-4">{{ orderNumber }}</p>
        <p class="text-gray-400 text-sm mb-6">We're still confirming your ZiniPay payment. This usually takes a few seconds.</p>
        <div class="flex gap-3 justify-center flex-wrap">
          <button @click="verify" :disabled="loading" class="btn-primary disabled:opacity-60">
            <span class="mi mi-sm">refresh</span>Check Again
          </button>
          <NuxtLink :to="`/order/track?id=${orderNumber}`" class="btn-outline">
            <span class="mi mi-sm">location_searching</span>View Order
          </NuxtLink>
        </div>
      </div>

      <!-- Failed / unknown -->
      <div v-else>
        <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-6xl text-red-500">error_outline</span>
        </div>
        <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">Payment Not Confirmed</h2>
        <p class="text-gray-400 text-sm mb-6">{{ error || "We couldn't confirm your payment status." }}</p>
        <div class="flex gap-3 justify-center flex-wrap">
          <button v-if="orderNumber" @click="verify" :disabled="loading" class="btn-primary disabled:opacity-60">
            <span class="mi mi-sm">refresh</span>Check Again
          </button>
          <NuxtLink v-if="orderNumber" :to="`/order/track?id=${orderNumber}`" class="btn-outline">
            <span class="mi mi-sm">location_searching</span>View Order
          </NuxtLink>
          <NuxtLink to="/" class="btn-outline">
            <span class="mi mi-sm">shopping_bag</span>Continue Shopping
          </NuxtLink>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

definePageMeta({ layout: 'default', middleware: 'auth' })

const route = useRoute()
const api   = useApi()

const orderNumber = ref(route.query.order ?? '')
const cancelled   = computed(() => route.query.cancelled === '1')

const loading = ref(true)
const status  = ref('')
const error   = ref('')

const verify = async () => {
  if (!orderNumber.value) { loading.value = false; return }
  loading.value = true
  error.value = ''
  try {
    const data = await api.post('/payment/zinipay/verify', { order_number: orderNumber.value })
    status.value = data?.status ?? 'PENDING'
  } catch (e) {
    error.value = e.message || "Could not verify payment status."
    status.value = 'ERROR'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (cancelled.value) { loading.value = false; return }
  verify()
})
</script>

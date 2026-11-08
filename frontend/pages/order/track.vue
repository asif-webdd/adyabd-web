<script setup>
definePageMeta({ layout: 'default' })

const route  = useRoute()
const api    = useApi()

const trackId = ref(route.query.id ?? '')
const data    = ref(null)
const loading = ref(false)
const error   = ref(null)

// Derived from API response
const order          = computed(() => data.value?.order ?? null)
const trackingId     = computed(() => data.value?.tracking_id ?? null)
const trackingUrl    = computed(() => data.value?.tracking_url ?? null)
const courier        = computed(() => data.value?.courier ?? null)
const statusHistory  = computed(() => [...(data.value?.status_histories ?? [])].reverse())

// Build timeline from status_histories
const STATUS_META = {
  pending:   { icon: 'receipt_long',     label: 'Order Placed' },
  confirmed: { icon: 'verified',         label: 'Order Confirmed' },
  processing:{ icon: 'inventory_2',      label: 'Processing' },
  shipped:   { icon: 'local_shipping',   label: 'Shipped' },
  delivered: { icon: 'check_circle',     label: 'Delivered' },
  cancelled: { icon: 'cancel',           label: 'Cancelled' },
}

const ALL_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered']

const timeline = computed(() => {
  if (!order.value) return []
  const histories = data.value?.status_histories ?? []
  const doneStatuses = new Set(histories.map(h => h.status))
  const currentStatus = order.value.status

  return ALL_STATUSES.map(status => {
    const history = histories.find(h => h.status === status)
    const meta = STATUS_META[status] ?? { icon: 'circle', label: status }
    return {
      status,
      icon:   meta.icon,
      title:  meta.label,
      done:   doneStatuses.has(status),
      active: status === currentStatus,
      time:   history ? new Date(history.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : null,
      note:   history?.note ?? null,
    }
  })
})

const progressHeight = computed(() => {
  const done  = timeline.value.filter(s => s.done).length
  const total = timeline.value.length
  return total > 1 ? ((done - 1) / (total - 1)) * 100 : 0
})

const trackOrder = async () => {
  if (!trackId.value.trim()) return
  loading.value = true
  error.value   = null
  try {
    const res   = await api.get(`/orders/${trackId.value.trim()}/track`)
    data.value  = res
  } catch (e) {
    error.value = e.message || 'Order not found.'
    data.value  = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (trackId.value) trackOrder()
})



// variant_options can be [{attribute,value}] (current) or {Color:'Red'} (legacy)
const variantText = (o) => {
  if (typeof o === 'string') { try { o = JSON.parse(o) } catch { return o } }
  if (!o || typeof o !== 'object') return ''
  return Object.entries(o).map(([k, v]) => {
    if (v && typeof v === 'object') return v.attribute ? `${v.attribute}: ${v.value}` : String(v.value ?? '')
    return /^\d+$/.test(k) ? String(v) : `${k}: ${v}`
  }).filter(Boolean).join(', ')
}

</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-8">

    <h1 class="section-title flex items-center gap-2 mb-5">
      <span class="mi text-primary">location_searching</span> Track Order
    </h1>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-card p-5 mb-5">
      <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
        <span class="mi mi-sm text-primary">receipt</span> Enter order number or tracking ID
      </label>
      <div class="flex gap-3">
        <div class="relative flex-1">
          <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
          <input v-model="trackId" type="text" placeholder="BD-XXXXX or tracking number" class="input-field pl-10 w-full" @keyup.enter="trackOrder"/>
        </div>
        <button @click="trackOrder" :disabled="loading || !trackId.trim()" class="btn-primary disabled:opacity-60">
          <span v-if="loading" class="mi mi-sm animate-spin">refresh</span>
          <span v-else class="mi mi-sm">search</span>
          {{ loading ? 'Searching...' : 'Track' }}
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-5 text-center mb-5">
      <span class="mi text-3xl text-red-400 block mb-2">search_off</span>
      <p class="text-red-600 font-semibold">{{ error }}</p>
      <p class="text-red-400 text-sm mt-1">Please check your order number or tracking number.</p>
    </div>

    <template v-if="order">

      <!-- Status Banner -->
      <div class="bg-gradient-to-r from-primary to-green-700 text-white rounded-2xl p-5 mb-5">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div>
            <p class="text-green-200 text-sm flex items-center gap-1">
              <span class="mi mi-sm">receipt</span> Order #{{ order.order_number }}
            </p>
            <p class="font-display font-extrabold text-2xl capitalize">
              {{ STATUS_META[order.status]?.label ?? order.status }}
            </p>
            <p class="text-green-200 text-sm mt-0.5 flex items-center gap-1">
              <span class="mi mi-sm">calendar_today</span>
              Placed: {{ new Date(order.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-green-200 text-xs flex items-center gap-1 justify-end mb-0.5">
              <span class="mi mi-sm">schedule</span> Est. Delivery
            </p>
            <p class="font-display font-bold text-xl">
              {{ order.estimated_delivery_at ? new Date(order.estimated_delivery_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short' }) : 'Coming Soon' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Courier Info -->
      <div v-if="courier || trackingId" class="bg-white rounded-2xl shadow-card p-5 mb-5">
        <h2 class="font-display font-bold text-base mb-4 flex items-center gap-2">
          <span class="mi text-primary">local_shipping</span> Courier Information
        </h2>
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="space-y-1">
            <p class="text-sm text-gray-500">Courier Service</p>
            <p class="font-semibold text-gray-800">{{ courier ?? '—' }}</p>
          </div>
          <div class="space-y-1">
            <p class="text-sm text-gray-500">Tracking ID</p>
            <p class="font-mono font-semibold text-gray-800">{{ trackingId ?? '—' }}</p>
          </div>

          <a v-if="trackingUrl" :href="trackingUrl" target="_blank" class="btn-primary text-sm flex items-center gap-1">
          <span class="mi mi-sm">open_in_new</span> Track on Courier
          </a>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-white rounded-2xl shadow-card p-5 mb-5">
        <h2 class="font-display font-bold text-lg mb-5 flex items-center gap-2">
          <span class="mi text-primary">timeline</span> Delivery Progress
        </h2>
        <div class="relative pl-8">
          <!-- Vertical track -->
          <div class="absolute left-3 top-3 bottom-3 w-0.5 bg-gray-200">
            <div class="w-full bg-primary rounded-full transition-all duration-700" :style="`height:${progressHeight}%`"></div>
          </div>

          <div v-for="(step, i) in timeline" :key="i" class="relative mb-6 last:mb-0">
            <!-- Dot -->
            <div class="absolute -left-8 w-7 h-7 rounded-full flex items-center justify-center border-2 transition-colors top-0" :class=" step.done   ? 'bg-primary border-primary' : step.active ? 'bg-white border-primary' : 'bg-white border-gray-200'">
              <span class="mi mi-sm" :class="step.done   ? 'text-white' : step.active ? 'text-primary animate-pulse' : 'text-gray-300'">{{ step.icon }}</span>
            </div>

            <div class="ml-2">
              <div class="flex items-start justify-between gap-2">
                <p class="font-semibold text-sm" :class="step.done || step.active ? 'text-gray-800' : 'text-gray-400'">{{ step.title }}</p>
                <p v-if="step.time" class="text-xs text-gray-400 shrink-0 flex items-center gap-0.5">
                  <span class="mi mi-sm">access_time</span>{{ step.time }}
                </p>
              </div>
              <p v-if="step.note" class="text-xs text-gray-500 mt-0.5">{{ step.note }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Status History -->
      <div class="bg-white rounded-2xl shadow-card p-5 mb-5">
        <h2 class="font-display font-bold text-base mb-4 flex items-center gap-2">
          <span class="mi text-primary">history</span> Status History
        </h2>
        <div class="space-y-3">
          <div v-for="history in statusHistory" :key="history.id" class="flex items-start gap-3 text-sm">
            <span class="mi mi-sm text-primary mt-0.5 shrink-0">{{ STATUS_META[history.status]?.icon ?? 'circle' }}</span>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-800 capitalize">{{ STATUS_META[history.status]?.label ?? history.status }}</p>
              <p v-if="history.note" class="text-gray-500 text-xs mt-0.5">{{ history.note }}</p>
            </div>
            <p class="text-xs text-gray-400 shrink-0">{{ new Date(history.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-2xl shadow-card p-5 mb-5">
        <h2 class="font-display font-bold text-base mb-4 flex items-center gap-2">
          <span class="mi text-primary">inventory_2</span> Order Items
        </h2>
        <div class="space-y-3">
          <div v-for="item in (order.items ?? [])" :key="item.id" class="flex items-center gap-3">
            <img :src="item.product_image" :alt="item.product_name" class="w-14 h-14 object-cover rounded-xl bg-gray-50 shrink-0"/>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-800 text-sm line-clamp-1">{{ item.product_name }}</p>
              <p class="text-gray-400 text-xs mt-0.5">
                <span>{{ variantText(item.variant_options) }}</span>
              </p>
              <p class="text-gray-400 text-xs">Qty: {{ item.quantity }}</p>
            </div>
            <p class="font-display font-bold text-accent shrink-0">
              ৳{{ Number(item.unit_price).toLocaleString() }}
            </p>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
          <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>৳{{ Number(order.subtotal).toLocaleString() }}</span>
          </div>
          <div v-if="Number(order.coupon_discount) > 0" class="flex justify-between text-green-600">
            <span class="flex items-center gap-1">
              <span class="mi mi-sm">local_offer</span> Coupon ({{ order.coupon_code }})
            </span>
            <span>− ৳{{ Number(order.coupon_discount).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Shipping</span>
            <span>৳{{ Number(order.shipping_charge).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between font-bold text-gray-800 pt-2 border-t border-gray-100">
            <span>Total</span>
            <span class="text-accent font-extrabold font-display">৳{{ Number(order.total_amount).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between text-xs text-gray-400 pt-1">
            <span class="flex items-center gap-1">
              <span class="mi mi-sm">payments</span>
              {{ order.payment_method === 'cod' ? 'Cash on Delivery' : order.payment_method }}
            </span>
            <span class="capitalize px-2 py-0.5 rounded-full text-xs font-semibold" :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">{{ order.payment_status }}</span>
          </div>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="bg-white rounded-2xl shadow-card p-5">
        <h2 class="font-display font-bold text-base mb-4 flex items-center gap-2">
          <span class="mi text-primary">location_on</span> Shipping Address
        </h2>
        <div class="text-sm text-gray-600 space-y-1">
          <p class="font-semibold text-gray-800 flex items-center gap-1">
            <span class="mi mi-sm text-primary">person</span>{{ order.shipping_name }}
          </p>
          <p class="flex items-center gap-1">
            <span class="mi mi-sm text-gray-400">call</span>
            <a :href="`tel:${order.shipping_phone}`" class="hover:text-primary transition-colors">{{ order.shipping_phone }}</a>
          </p>
          <p class="flex items-start gap-1">
            <span class="mi mi-sm text-gray-400 mt-0.5">home</span>
            {{ order.shipping_address }}, {{ order.shipping_district }}, {{ order.shipping_division }} {{ order.shipping_postal_code }}
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
<template>
  <div class="space-y-5">
    <h1 class="section-title flex items-center gap-2"><span class="mi text-primary">account_balance_wallet</span>{{ tr('আমার ওয়ালেট') }}</h1>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-primary to-green-700 text-white rounded-2xl p-6 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-10 translate-x-10"></div>
      <div class="absolute bottom-0 left-0 w-28 h-28 bg-white/5 rounded-full translate-y-10 -translate-x-10"></div>
      <div class="relative">
        <p class="font-bengali text-green-200 text-sm mb-1 flex items-center gap-1"><span class="mi mi-sm">account_balance_wallet</span>{{ tr('মোট ব্যালেন্স') }}</p>
        <div v-if="loading" class="h-10 w-40 bg-white/20 animate-pulse rounded-xl mb-1"></div>
        <p v-else class="font-display font-extrabold text-4xl mb-1">৳ {{ Number(balance).toLocaleString('bn-BD', { minimumFractionDigits: 2 }) }}</p>
        <p class="font-bengali text-green-300 text-xs">{{ tr('সর্বশেষ আপডেট: এইমাত্র') }}</p>
        <div class="mt-5 flex gap-3 flex-wrap">
          <button @click="showWithdraw = true" class="bg-white text-primary font-display font-bold px-5 py-2.5 rounded-xl text-sm hover:bg-green-50 transition-colors flex items-center gap-1.5">
            <span class="mi mi-sm">arrow_upward</span>{{ tr('উত্তোলন') }}
          </button>
          <button class="bg-white/20 border border-white/30 text-white font-display font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-white/30 transition-colors flex items-center gap-1.5">
            <span class="mi mi-sm">history</span>{{ tr('ইতিহাস') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Withdraw Modal -->
    <div v-if="showWithdraw" class="bg-white rounded-2xl shadow-card p-6 border border-primary/20">
      <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2"><span class="mi text-primary">arrow_upward</span>{{ tr('উত্তোলন করুন') }}</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পরিমাণ *') }}</label>
          <div class="relative"><span class="font-bengali absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">৳</span>
            <input v-model.number="withdrawForm.amount" type="number" min="100" :max="balance" :placeholder="tr('সর্বনিম্ন ৳১০০')" class="input-field pl-8"/>
          </div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-2">{{ tr('মাধ্যম') }}</label>
          <div class="grid grid-cols-2 gap-3">
            <label v-for="m in withdrawMethods" :key="m.id" class="flex items-center gap-2 border-2 rounded-xl p-3 cursor-pointer transition-colors"
              :class="withdrawForm.method === m.id ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300'">
              <input type="radio" :value="m.id" v-model="withdrawForm.method" class="accent-primary"/>
              <span class="mi mi-sm" :class="m.color">{{ m.icon }}</span>
              <span class="font-bengali text-sm font-medium">{{ m.name }}</span>
            </label>
          </div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('অ্যাকাউন্ট নম্বর *') }}</label>
          <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">phone</span>
            <input v-model="withdrawForm.account_number" type="text" placeholder="01XXXXXXXXX" class="input-field pl-10"/>
          </div>
        </div>
        <div v-if="withdrawError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali">{{ withdrawError }}</div>
        <div class="flex gap-3">
          <button @click="doWithdraw" :disabled="withdrawing" class="flex-1 btn-primary justify-center disabled:opacity-60">
            <span v-if="withdrawing" class="mi mi-sm animate-spin">refresh</span>
            <span v-else class="mi mi-sm">arrow_upward</span>
            {{ withdrawing ? 'প্রক্রিয়া হচ্ছে...' : 'উত্তোলন করুন' }}
          </button>
          <button @click="showWithdraw = false; withdrawError = ''" class="btn-outline">{{ tr('বাতিল') }}</button>
        </div>
      </div>
    </div>

    <!-- Transactions -->
    <div class="bg-white rounded-2xl shadow-card overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-50">
        <h2 class="font-display font-bold text-lg text-gray-800 flex items-center gap-2"><span class="mi text-primary">receipt_long</span>{{ tr('লেনদেনের ইতিহাস') }}</h2>
        <select v-model="txFilter" @change="loadTransactions"
          class="text-sm font-bengali border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:border-primary">
          <option value="">{{ tr('সব') }}</option>
          <option value="credit">{{ tr('জমা') }}</option>
          <option value="debit">{{ tr('উত্তোলন') }}</option>
          <option value="refund">{{ tr('রিফান্ড') }}</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="loadingTx" class="p-4 space-y-3">
        <div v-for="i in 5" :key="i" class="h-14 bg-gray-100 animate-pulse rounded-xl"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="transactions.length === 0" class="p-10 text-center">
        <span class="mi text-4xl text-gray-200 block mb-2">receipt_long</span>
        <p class="font-bengali text-gray-400 text-sm">{{ tr('কোনো লেনদেন নেই।') }}</p>
      </div>

      <!-- List -->
      <div v-else class="divide-y divide-gray-50">
        <div v-for="tx in transactions" :key="tx.id" class="flex items-center gap-4 p-4 hover:bg-gray-50/50 transition-colors">
          <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
            :class="tx.type === 'credit' || tx.type === 'refund' ? 'bg-green-100' : 'bg-red-100'">
            <span class="mi mi-sm" :class="tx.type === 'credit' || tx.type === 'refund' ? 'text-green-600' : 'text-red-500'">
              {{ txIcon(tx.type) }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-bengali font-semibold text-gray-800 text-sm line-clamp-1">{{ tx.description }}</p>
            <p class="font-bengali text-xs text-gray-400 flex items-center gap-1 mt-0.5">
              <span class="mi mi-sm">schedule</span>{{ formatDate(tx.created_at) }}
              <span class="badge text-xs ml-1" :class="txStatusClass(tx.status)">{{ txStatusLabel(tx.status) }}</span>
            </p>
          </div>
          <p class="font-display font-bold shrink-0" :class="tx.type === 'credit' || tx.type === 'refund' ? 'text-green-600' : 'text-accent'">
            {{ tx.type === 'credit' || tx.type === 'refund' ? '+' : '-' }}৳{{ Number(tx.amount).toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="txTotalPages > 1" class="p-4 flex justify-center gap-2">
        <button @click="txPage--; loadTransactions()" :disabled="txPage <= 1"
          class="px-3 py-1.5 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
          <span class="mi mi-sm">arrow_back</span>
        </button>
        <span class="px-3 py-1.5 font-bengali text-sm text-gray-600">{{ txPage }} / {{ txTotalPages }}</span>
        <button @click="txPage++; loadTransactions()" :disabled="txPage >= txTotalPages"
          class="px-3 py-1.5 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
          <span class="mi mi-sm">arrow_forward</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const api = useApi()

const balance      = ref(0)
const transactions = ref([])
const loading      = ref(true)
const loadingTx    = ref(true)
const txFilter     = ref('')
const txPage       = ref(1)
const txTotalPages = ref(1)
const showWithdraw = ref(false)
const withdrawing  = ref(false)
const withdrawError = ref('')

const withdrawForm = reactive({ amount: null, method: 'bkash', account_number: '' })

const withdrawMethods = [
  { id: 'bkash',  name: 'bKash',          icon: 'phone_iphone',    color: 'text-bkash'       },
  { id: 'nagad',  name: 'Nagad',           icon: 'phone_android',   color: 'text-orange-500'  },
  { id: 'rocket', name: 'Rocket',          icon: 'smartphone',      color: 'text-purple-500'  },
  { id: 'bank',   name: 'ব্যাংক ট্রান্সফার', icon: 'account_balance', color: 'text-blue-600'  },
]

const txIcon = (type) => ({
  credit:   'arrow_downward',
  debit:    'arrow_upward',
  refund:   'currency_exchange',
  cashback: 'savings',
}[type] || 'swap_horiz')

const txStatusClass = (s) => ({
  completed: 'bg-green-100 text-green-700',
  pending:   'bg-yellow-100 text-yellow-700',
  failed:    'bg-red-100 text-red-700',
}[s] || 'bg-gray-100 text-gray-600')

const txStatusLabel = (s) => ({
  completed: 'সম্পন্ন',
  pending:   'প্রক্রিয়াধীন',
  failed:    'ব্যর্থ',
}[s] || s)

const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''

const loadWallet = async () => {
  loading.value = true
  try {
    const data = await api.get('/user/wallet')
    balance.value = data.balance ?? 0
  } catch {}
  finally { loading.value = false }
}

const loadTransactions = async () => {
  loadingTx.value = true
  try {
    const params = { page: txPage.value }
    if (txFilter.value) params.type = txFilter.value
    const data = await api.get('/user/transactions', params)
    transactions.value = data.data ?? data ?? []
    txTotalPages.value = data.last_page ?? 1
  } catch {}
  finally { loadingTx.value = false }
}

const doWithdraw = async () => {
  withdrawError.value = ''
  if (!withdrawForm.amount || withdrawForm.amount < 100) {
    withdrawError.value = 'সর্বনিম্ন ৳১০০ উত্তোলন করতে হবে।'; return
  }
  if (withdrawForm.amount > balance.value) {
    withdrawError.value = 'পর্যাপ্ত ব্যালেন্স নেই।'; return
  }
  if (!withdrawForm.account_number) {
    withdrawError.value = 'অ্যাকাউন্ট নম্বর দিন।'; return
  }
  withdrawing.value = true
  try {
    await api.post('/user/wallet/withdraw', { ...withdrawForm })
    showWithdraw.value = false
    withdrawForm.amount = null
    withdrawForm.account_number = ''
    await Promise.all([loadWallet(), loadTransactions()])
  } catch (e) {
    withdrawError.value = e.message || 'উত্তোলন ব্যর্থ হয়েছে।'
  } finally {
    withdrawing.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadWallet(), loadTransactions()])
})
</script>

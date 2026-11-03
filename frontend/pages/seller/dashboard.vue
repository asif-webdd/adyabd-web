<template>
  <div class="max-w-6xl mx-auto px-4 py-6 font-bengali">
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
      <div>
        <h1 class="section-title text-2xl">{{ tr('বিক্রেতা ড্যাশবোর্ড') }}</h1>
        <p v-if="stats.shop_name" class="text-sm text-gray-500">{{ stats.shop_name }}</p>
      </div>
      <NuxtLink to="/account" class="text-sm text-primary hover:underline">{{ tr('← আমার অ্যাকাউন্ট') }}</NuxtLink>
    </div>

    <!-- Not a seller / pending -->
    <div v-if="blocked" class="bg-white rounded-2xl shadow-card p-8 text-center">
      <span class="mi text-5xl text-gray-300 block mb-3">{{ blocked.icon }}</span>
      <p class="font-bold text-gray-800 mb-1">{{ blocked.title }}</p>
      <p class="text-sm text-gray-500 mb-4">{{ blocked.text }}</p>
      <NuxtLink v-if="blocked.link" :to="blocked.link" class="btn-primary inline-flex">{{ blocked.linkLabel }}</NuxtLink>
    </div>

    <template v-else>
      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div v-for="c in cards" :key="c.label" class="bg-white rounded-2xl shadow-card p-4 text-center">
          <p class="font-display font-bold text-xl text-gray-800">{{ c.value }}</p>
          <p class="text-xs text-gray-500">{{ c.label }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-2 mb-4 flex-wrap">
        <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
                class="px-4 py-2 rounded-full text-sm border-2 transition-colors"
                :class="tab === t.key ? 'bg-primary text-white border-primary' : 'border-gray-200 text-gray-600 hover:border-primary'">
          {{ t.label }}
          <span v-if="t.key === 'notifications' && unread > 0" class="ml-1 bg-red-500 text-white text-[10px] rounded-full px-1.5 py-0.5">{{ unread }}</span>
        </button>
      </div>

      <p v-if="msg" class="text-sm rounded-xl px-3 py-2 mb-3" :class="msgOk ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'">{{ msg }}</p>

      <!-- Orders -->
      <div v-if="tab === 'orders'" class="bg-white rounded-2xl shadow-card divide-y divide-gray-100">
        <p v-if="!orders.length" class="p-6 text-center text-sm text-gray-400">{{ tr('কোনো অর্ডার নেই।') }}</p>
        <div v-for="o in orders" :key="o.id" @click="openOrder(o.id)" class="hover:bg-gray-50 cursor-pointer p-4 flex items-center justify-between gap-3 flex-wrap">
          <div>
            <p class="font-display font-bold text-gray-800">#{{ o.order_number || o.id }}</p>
            <p class="text-xs text-gray-500">৳{{ o.total_amount ?? o.total }} · {{ statusLabel(o.status) }}</p>
          </div>
          <div class="flex gap-2">
            <button v-for="a in nextActions(o.status)" :key="a.to" @click.stop="setStatus(o, a.to)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                    :class="a.danger ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-primary text-primary hover:bg-primary/10'">
              {{ a.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div v-if="tab === 'products'">
        <div class="bg-white rounded-2xl shadow-card p-4 mb-4">
          <p class="font-bold text-gray-800 mb-3">{{ tr('নতুন পণ্য যোগ করুন') }}</p>
          <div class="grid md:grid-cols-2 gap-3">
            <input v-model="pf.name" class="input-field" :placeholder="tr('পণ্যের নাম *')"/>
            <select v-model="pf.category_id" class="input-field">
              <option value="">{{ tr('ক্যাটাগরি *') }}</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model.number="pf.price" type="number" min="1" class="input-field" :placeholder="tr('দাম (৳) *')"/>
            <input v-model.number="pf.quantity" type="number" min="0" class="input-field"
       :disabled="pf.variants.length > 0"
       :placeholder="pf.variants.length ? 'স্টক ভ্যারিয়েন্ট থেকে হিসাব হবে' : tr('স্টক পরিমাণ *')"/>
            <textarea v-model="pf.description" rows="3" class="input-field md:col-span-2 resize-none" :placeholder="tr('বিবরণ')"></textarea>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-gray-600 mb-1">{{ tr('ফিচার্ড ছবি (প্রধান ছবি)') }}</label>
              <input type="file" accept="image/*" @change="e => pf.image = e.target.files[0]" class="text-sm"/>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-gray-600 mb-1">{{ tr('গ্যালারি ছবি (সর্বোচ্চ ৮টি, একসাথে একাধিক বাছাই করা যায়)') }}</label>
              <input type="file" accept="image/*" multiple @change="e => pf.gallery = Array.from(e.target.files).slice(0, 8)" class="text-sm"/>
              <p v-if="pf.gallery.length" class="text-xs text-primary mt-1">{{ pf.gallery.length }}টি গ্যালারি ছবি বাছাই করা হয়েছে</p>
            </div>
          </div>


          <!-- Variants (optional) -->
          <div class="border-t border-gray-100 mt-4 pt-3">
            <p class="font-bold text-gray-800 mb-1">{{ tr('ভ্যারিয়েন্ট (সাইজ / রঙ ইত্যাদি)') }} <span class="text-xs font-normal text-gray-400">({{ tr('ঐচ্ছিক') }})</span></p>
            <p class="text-xs text-gray-400 mb-2">{{ tr('ভ্যারিয়েন্ট যোগ করলে মোট স্টক ভ্যারিয়েন্টগুলোর যোগফল হবে।') }}</p>
            <div v-for="(v, i) in pf.variants" :key="i" class="flex items-center justify-between gap-2 text-sm border border-gray-100 rounded-xl px-3 py-2 mb-2">
              <div class="min-w-0">
                <p class="font-bold text-gray-700 truncate">{{ variantLabel(v) }}</p>
                <p class="text-xs text-gray-500">৳{{ v.price }} · স্টক {{ v.quantity }}<span v-if="v.sku"> · {{ v.sku }}</span><span v-if="!v.is_active" class="text-red-500"> {{ tr('· বন্ধ') }}</span></p>
              </div>
              <button type="button" @click="pf.variants.splice(i, 1)" class="text-xs text-red-600 font-bold shrink-0">{{ tr('মুছুন') }}</button>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs font-bold text-gray-600 mb-2">{{ tr('নতুন ভ্যারিয়েন্ট') }}</p>
              <div v-for="(a, i) in nv.attrs" :key="i" class="flex gap-2 mb-2">
                <input v-model="a.attribute" class="input-field" :placeholder="tr('যেমন: রঙ')"/>
                <input v-model="a.value" class="input-field" :placeholder="tr('যেমন: লাল')"/>
                <button v-if="nv.attrs.length > 1" type="button" @click="nv.attrs.splice(i, 1)" class="text-red-500 px-2">×</button>
              </div>
              <button v-if="nv.attrs.length < 5" type="button" @click="nv.attrs.push({ attribute: '', value: '' })" class="text-xs text-primary font-bold mb-2">{{ tr('+ আরেকটি বৈশিষ্ট্য') }}</button>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <input v-model.number="nv.price" type="number" min="1" class="input-field" :placeholder="tr('দাম (৳) *')"/>
                <input v-model.number="nv.compare_price" type="number" min="0" class="input-field" :placeholder="tr('আগের দাম')"/>
                <input v-model.number="nv.quantity" type="number" min="0" class="input-field" :placeholder="tr('স্টক *')"/>
                <input v-model="nv.sku" class="input-field" placeholder="SKU"/>
              </div>
              <label class="flex items-center gap-2 text-xs text-gray-600 mt-2"><input type="checkbox" v-model="nv.is_active"/> {{ tr('সক্রিয়') }}</label>
              <button type="button" @click="stageVariant" class="btn-primary text-sm mt-2">{{ tr('ভ্যারিয়েন্ট যোগ করুন') }}</button>
            </div>
          </div>


          <p class="text-xs text-gray-400 mt-2">{{ tr('নতুন পণ্য অ্যাডমিনের অনুমোদনের পর সাইটে দেখা যাবে।') }}</p>
          <button @click="addProduct" :disabled="saving" class="btn-primary mt-3 disabled:opacity-60">
            {{ saving ? 'জমা হচ্ছে...' : 'পণ্য জমা দিন' }}
          </button>
        </div>

        <div class="bg-white rounded-2xl shadow-card divide-y divide-gray-100">
          <p v-if="!products.length" class="p-6 text-center text-sm text-gray-400">{{ tr('এখনও কোনো পণ্য নেই।') }}</p>
          <div v-for="p in products" :key="p.id" class="p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <img :src="imgUrl(p.primary_image || p.images?.[0])" class="w-14 h-14 rounded-lg object-cover bg-gray-100 shrink-0" alt=""/>
                <div class="min-w-0">
                  <p class="font-bold text-gray-800 truncate">{{ p.name }}</p>
                  <p class="text-xs text-gray-500">৳{{ p.price }} · স্টক {{ p.quantity }} · ছবি {{ p.images?.length ?? 0 }}টি</p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs px-2 py-1 rounded-full" :class="p.is_active ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700'">
                  {{ p.is_active ? 'সক্রিয়' : 'অনুমোদনের অপেক্ষায়' }}
                </span>
                <button @click="openEdit(p)" class="text-xs px-2.5 py-1 rounded-lg border border-primary text-primary hover:bg-primary/10 font-bold">{{ tr('এডিট') }}</button>
                <button @click="removeProduct(p)" class="text-xs px-2.5 py-1 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 font-bold">{{ tr('মুছুন') }}</button>
              </div>
            </div>
            <!-- Gallery manager -->
            <div class="flex flex-wrap items-center gap-2 mt-3">
              <div v-for="im in (p.images || [])" :key="im.id" class="relative">
                <img :src="imgUrl(im)" class="w-16 h-16 rounded-lg object-cover border" :class="im.is_primary ? 'border-primary' : 'border-gray-200'" alt=""/>
                <span v-if="im.is_primary" class="absolute bottom-0 left-0 right-0 text-[10px] text-center bg-primary text-white rounded-b-lg">{{ tr('প্রধান') }}</span>
                <button @click="removeImage(p, im)" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white text-xs leading-5" :title="tr('মুছুন')">×</button>
              </div>
              <label v-if="(p.images?.length ?? 0) < 8" class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-300 text-gray-400 flex flex-col items-center justify-center text-[11px] cursor-pointer hover:border-primary hover:text-primary">
                <span class="text-lg leading-none">+</span>{{ tr('ছবি') }}
                <input type="file" accept="image/*" multiple class="hidden" @change="e => addImages(p, e)"/>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Product edit modal (details + variants) -->
      <div v-if="editing" class="fixed inset-0 z-50 bg-black/50 flex items-start justify-center overflow-y-auto p-4" @click.self="editing = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-6 p-5">
          <div class="flex items-center justify-between mb-3">
            <p class="font-bold text-gray-800">পণ্য এডিট: {{ editing.name }}</p>
            <button @click="editing = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
          </div>
          <div class="grid md:grid-cols-2 gap-3">
            <input v-model="ef.name" class="input-field md:col-span-2" :placeholder="tr('পণ্যের নাম *')"/>
            <select v-model="ef.category_id" class="input-field">
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="ef.unit" class="input-field" :placeholder="tr('একক (pcs, kg...)')"/>
            <input v-model.number="ef.price" type="number" min="1" class="input-field" :placeholder="tr('দাম (৳) *')"/>
            <input v-model.number="ef.compare_price" type="number" min="0" class="input-field" :placeholder="tr('আগের দাম (ঐচ্ছিক)')"/>
            <input v-model.number="ef.quantity" type="number" min="0" class="input-field" :disabled="hasVariants" :placeholder="hasVariants ? 'স্টক ভ্যারিয়েন্ট থেকে হিসাব হচ্ছে' : 'স্টক *'"/>
            <input v-model.number="ef.weight" type="number" min="0" step="0.01" class="input-field" :placeholder="tr('ওজন (কেজি)')"/>
            <input v-model="ef.short_description" class="input-field md:col-span-2" :placeholder="tr('সংক্ষিপ্ত বিবরণ')"/>
            <textarea v-model="ef.description" rows="4" class="input-field md:col-span-2 resize-none" :placeholder="tr('বিস্তারিত বিবরণ')"></textarea>
          </div>
          <label class="flex items-center gap-2 text-sm text-gray-600 mt-3" v-if="editing.is_active">
            <input type="checkbox" v-model="ef.is_active"/> {{ tr('পণ্যটি সাইটে দেখান (বন্ধ করলে লুকানো থাকবে)') }}
          </label>
          <p v-else class="text-xs text-yellow-700 mt-3">{{ tr('এই পণ্য এখনো অ্যাডমিনের অনুমোদনের অপেক্ষায়।') }}</p>
          <button @click="saveEdit" :disabled="saving" class="btn-primary mt-3 disabled:opacity-60">{{ saving ? 'সেভ হচ্ছে...' : 'পরিবর্তন সেভ করুন' }}</button>

          <!-- Variants -->
          <div class="border-t border-gray-100 mt-5 pt-4">
            <p class="font-bold text-gray-800 mb-1">{{ tr('ভ্যারিয়েন্ট (সাইজ / রঙ ইত্যাদি)') }}</p>
            <p class="text-xs text-gray-400 mb-3">{{ tr('ভ্যারিয়েন্ট যোগ করলে মোট স্টক ভ্যারিয়েন্টগুলোর যোগফল হবে।') }}</p>
            <div v-for="v in editVariants" :key="v.id" class="flex items-center justify-between gap-2 text-sm border border-gray-100 rounded-xl px-3 py-2 mb-2">
              <div class="min-w-0">
                <p class="font-bold text-gray-700 truncate">{{ variantLabel(v) }}</p>
                <p class="text-xs text-gray-500">৳{{ v.price }} · স্টক {{ v.quantity }}<span v-if="v.sku"> · {{ v.sku }}</span><span v-if="!v.is_active" class="text-red-500"> {{ tr('· বন্ধ') }}</span></p>
              </div>
              <div class="flex gap-2 shrink-0">
                <button @click="startVariant(v)" class="text-xs text-primary font-bold">{{ tr('এডিট') }}</button>
                <button @click="removeVariant(v)" class="text-xs text-red-600 font-bold">{{ tr('মুছুন') }}</button>
              </div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 mt-2">
              <p class="text-xs font-bold text-gray-600 mb-2">{{ vf.id ? 'ভ্যারিয়েন্ট এডিট' : 'নতুন ভ্যারিয়েন্ট' }}</p>
              <div v-for="(a, i) in vf.attrs" :key="i" class="flex gap-2 mb-2">
                <input v-model="a.attribute" class="input-field" :placeholder="tr('যেমন: রঙ')"/>
                <input v-model="a.value" class="input-field" :placeholder="tr('যেমন: লাল')"/>
                <button v-if="vf.attrs.length > 1" @click="vf.attrs.splice(i, 1)" class="text-red-500 px-2">×</button>
              </div>
              <button v-if="vf.attrs.length < 5" @click="vf.attrs.push({ attribute: '', value: '' })" class="text-xs text-primary font-bold mb-2">{{ tr('+ আরেকটি বৈশিষ্ট্য') }}</button>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <input v-model.number="vf.price" type="number" min="1" class="input-field" :placeholder="tr('দাম (৳) *')"/>
                <input v-model.number="vf.compare_price" type="number" min="0" class="input-field" :placeholder="tr('আগের দাম')"/>
                <input v-model.number="vf.quantity" type="number" min="0" class="input-field" :placeholder="tr('স্টক *')"/>
                <input v-model="vf.sku" class="input-field" placeholder="SKU"/>
              </div>
              <label class="flex items-center gap-2 text-xs text-gray-600 mt-2"><input type="checkbox" v-model="vf.is_active"/> {{ tr('সক্রিয়') }}</label>
              <div class="flex gap-2 mt-2">
                <button @click="saveVariant" :disabled="saving" class="btn-primary text-sm disabled:opacity-60">{{ vf.id ? 'আপডেট' : 'ভ্যারিয়েন্ট যোগ করুন' }}</button>
                <button v-if="vf.id" @click="resetVariant" class="text-sm text-gray-500">{{ tr('বাতিল') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Shop profile -->
      <div v-if="tab === 'profile'" class="bg-white rounded-2xl shadow-card p-4">
        <p class="font-bold text-gray-800 mb-1">{{ tr('শপ প্রোফাইল') }}</p>
        <p class="text-xs text-gray-400 mb-3">{{ tr('শপের নাম ও কমিশন শুধু অ্যাডমিন বদলাতে পারে। বাকি তথ্য এখান থেকে বদলান।') }}</p>
        <div class="relative rounded-xl overflow-hidden bg-gray-100 h-28 mb-3">
          <img v-if="shop.banner_preview || shop.banner" :src="shop.banner_preview || shop.banner" class="w-full h-full object-cover" alt=""/>
          <div class="absolute -bottom-0 left-3 bottom-3 w-16 h-16 rounded-xl border-2 border-white bg-white overflow-hidden shadow">
            <img :src="shop.logo_preview || shop.logo || '/images/placeholder.svg'" class="w-full h-full object-cover" alt=""/>
          </div>
        </div>
        <div class="grid md:grid-cols-2 gap-3">
          <div><label class="block text-xs font-bold text-gray-600 mb-1">{{ tr('লোগো') }}</label><input type="file" accept="image/*" @change="e => pickShopFile('logo', e)" class="text-sm"/></div>
          <div><label class="block text-xs font-bold text-gray-600 mb-1">{{ tr('ব্যানার') }}</label><input type="file" accept="image/*" @change="e => pickShopFile('banner', e)" class="text-sm"/></div>
          <input :value="shop.shop_name" disabled class="input-field bg-gray-50 text-gray-500" :placeholder="tr('শপের নাম')"/>
          <input v-model="shop.shop_name_bn" class="input-field" :placeholder="tr('শপের নাম (বাংলা)')"/>
          <input v-model="shop.phone" class="input-field" :placeholder="tr('ফোন')"/>
          <input v-model="shop.email" type="email" class="input-field" :placeholder="tr('ইমেইল')"/>
          <input v-model="shop.address" class="input-field md:col-span-2" :placeholder="tr('ঠিকানা')"/>
          <textarea v-model="shop.description" rows="3" class="input-field md:col-span-2 resize-none" :placeholder="tr('শপ সম্পর্কে')"></textarea>
          <input v-model="shop.bkash_number" class="input-field" :placeholder="tr('bKash/Nagad নম্বর (পেআউট)')"/>
          <input v-model="shop.bank_name" class="input-field" :placeholder="tr('ব্যাংকের নাম')"/>
          <input v-model="shop.bank_account" class="input-field md:col-span-2" :placeholder="tr('ব্যাংক অ্যাকাউন্ট নম্বর')"/>
        </div>
        <button @click="saveProfile" :disabled="saving" class="btn-primary mt-3 disabled:opacity-60">{{ saving ? 'সেভ হচ্ছে...' : 'প্রোফাইল সেভ করুন' }}</button>
      </div>

      <!-- Earnings & withdrawals -->
      <div v-if="tab === 'earnings'">
        <div class="bg-white rounded-2xl shadow-card p-4 mb-4">
          <p class="font-bold text-gray-800 mb-1">{{ tr('টাকা উত্তোলনের অনুরোধ') }}</p>
          <p class="text-xs text-gray-500 mb-3">
            {{ tr('বর্তমান ব্যালেন্স') }} <b>৳{{ stats.balance ?? 0 }}</b> {{ tr('· সর্বনিম্ন উত্তোলন') }} <b>৳{{ stats.min_withdrawal ?? 500 }}</b>
            <span v-if="stats.pending_withdrawals"> · অপেক্ষমাণ অনুরোধ {{ stats.pending_withdrawals }}টি</span>
          </p>
          <div v-if="!(stats.payout_methods || []).length" class="text-sm text-yellow-700 bg-yellow-50 rounded-xl p-3">
            {{ tr('আপনার প্রোফাইলে কোনো bKash/Nagad/ব্যাংক তথ্য নেই। অ্যাডমিনের সাথে যোগাযোগ করে তথ্য যোগ করুন।') }}
          </div>
          <div v-else class="grid md:grid-cols-3 gap-3">
            <input v-model.number="wf.amount" type="number" :min="stats.min_withdrawal || 1" class="input-field" :placeholder="tr('পরিমাণ (৳) *')"/>
            <select v-model="wf.idx" class="input-field">
              <option v-for="(m, i) in stats.payout_methods" :key="i" :value="i">{{ methodLabel(m.method) }} — {{ m.account }}</option>
            </select>
            <button @click="requestWithdrawal" :disabled="saving" class="btn-primary disabled:opacity-60">
              {{ saving ? 'জমা হচ্ছে...' : 'অনুরোধ পাঠান' }}
            </button>
          </div>
          <p class="text-xs text-gray-400 mt-2">{{ tr('অনুরোধ করলেই পরিমাণটি ব্যালেন্স থেকে আটকে থাকে। অ্যাডমিন পেমেন্ট করলে "পরিশোধিত", বাতিল করলে টাকা ব্যালেন্সে ফেরত আসে।') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-card divide-y divide-gray-100 mb-4">
          <p class="p-4 font-bold text-gray-800">{{ tr('উত্তোলনের ইতিহাস') }}</p>
          <p v-if="!withdrawals.length" class="p-6 text-center text-sm text-gray-400">{{ tr('এখনও কোনো অনুরোধ নেই।') }}</p>
          <div v-for="w in withdrawals" :key="w.id" class="p-4 flex items-center justify-between gap-3 flex-wrap">
            <div>
              <p class="font-bold text-gray-800">৳{{ w.amount }} · {{ methodLabel(w.method) }}</p>
              <p class="text-xs text-gray-500">{{ w.account }} · {{ fmtDate(w.created_at) }}</p>
              <p v-if="w.reference" class="text-xs text-gray-500">রেফারেন্স: {{ w.reference }}</p>
              <p v-if="w.admin_note" class="text-xs text-red-500">{{ w.admin_note }}</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full" :class="wClass(w.status)">{{ wLabel(w.status) }}</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-card divide-y divide-gray-100">
          <p class="p-4 font-bold text-gray-800">{{ tr('আয়-ব্যয়ের খাতা') }}</p>
          <p v-if="!ledger.length" class="p-6 text-center text-sm text-gray-400">{{ tr('কোনো লেনদেন নেই।') }}</p>
          <div v-for="t in ledger" :key="t.id" class="p-4 flex items-center justify-between gap-3">
            <div>
              <p class="text-sm text-gray-800">{{ t.description || t.type }}</p>
              <p class="text-xs text-gray-400">{{ fmtDate(t.created_at) }}</p>
            </div>
            <p class="font-bold" :class="Number(t.amount) >= 0 && !['withdrawal','order_payout_reversal'].includes(t.type) ? 'text-green-600' : 'text-red-600'">
              {{ ['withdrawal','order_payout_reversal'].includes(t.type) ? '−' : '+' }}৳{{ Math.abs(Number(t.amount)) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Notifications -->
      <div v-if="tab === 'notifications'" class="bg-white rounded-2xl shadow-card divide-y divide-gray-100">
        <div class="p-4 flex items-center justify-between">
          <p class="font-bold text-gray-800">{{ tr('নোটিফিকেশন') }}</p>
          <button v-if="unread > 0" @click="markRead" class="text-xs text-primary hover:underline">{{ tr('সব পড়া হয়েছে') }}</button>
        </div>
        <p v-if="!notifs.length" class="p-6 text-center text-sm text-gray-400">{{ tr('কোনো নোটিফিকেশন নেই।') }}</p>
        <div v-for="n in notifs" :key="n.id" class="p-4" :class="[n.read ? '' : 'bg-primary/5', n.order_id ? 'cursor-pointer hover:bg-gray-50' : '']" @click="n.order_id && openOrder(n.order_id)">
          <p class="font-bold text-gray-800 text-sm">{{ n.title }}</p>
          <p class="text-sm text-gray-600">{{ n.body }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ fmtDate(n.created_at) }}</p>
        </div>
      </div>
    </template>


    <!-- Order details modal -->
    <div v-if="od.open" class="fixed inset-0 z-50 bg-black/50 flex items-end md:items-center justify-center p-0 md:p-4" @click.self="od.open = false">
      <div class="bg-white w-full md:max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-2xl md:rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="font-display font-bold text-lg">{{ tr('অর্ডার') }} #{{ od.data?.order_number || od.id }}</p>
          <button @click="od.open = false" class="text-2xl leading-none text-gray-400">×</button>
        </div>
        <p v-if="od.loading" class="py-8 text-center text-sm text-gray-400">{{ tr('লোড হচ্ছে...') }}</p>
        <p v-else-if="od.error" class="py-6 text-center text-sm text-red-600">{{ od.error }}</p>
        <div v-else-if="od.data" class="space-y-4 text-sm">
          <div class="flex flex-wrap gap-2 items-center">
            <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-bold">{{ statusLabel(od.data.status) }}</span>
            <span class="text-xs text-gray-500">{{ od.data.payment_method }} · {{ od.data.payment_status }}</span>
            <span class="text-xs text-gray-400">{{ fmtDate(od.data.created_at) }}</span>
          </div>
          <div class="border border-gray-100 rounded-xl p-3">
            <p class="font-bold text-gray-700 mb-1">{{ tr('গ্রাহক ও ঠিকানা') }}</p>
            <p>{{ od.data.shipping_name || od.data.user?.name }} · {{ od.data.shipping_phone || od.data.user?.phone }}</p>
            <p class="text-gray-600">{{ [od.data.shipping_address, od.data.shipping_upazila, od.data.shipping_district, od.data.shipping_division, od.data.shipping_postal_code].filter(Boolean).join(', ') }}</p>
            <p v-if="od.data.customer_note" class="text-xs text-gray-500 mt-1">{{ tr('নোট') }}: {{ od.data.customer_note }}</p>
          </div>
          <div class="border border-gray-100 rounded-xl divide-y divide-gray-100">
            <div v-for="it in od.data.items" :key="it.id" class="p-3 flex justify-between gap-3">
              <div class="min-w-0">
                <p class="font-bold text-gray-800">{{ it.product_name }}</p>
                <p v-if="orderOptLabel(it)" class="text-xs text-gray-500">{{ orderOptLabel(it) }}</p>
                <p class="text-xs text-gray-500">৳{{ it.unit_price }} × {{ it.quantity }}</p>
              </div>
              <p class="font-bold shrink-0">৳{{ it.total_price }}</p>
            </div>
          </div>
          <div class="bg-gray-50 rounded-xl p-3 space-y-1">
            <div class="flex justify-between"><span>{{ tr('সাবটোটাল') }}</span><span>৳{{ od.data.subtotal }}</span></div>
            <div v-if="+od.data.discount_amount || +od.data.coupon_discount" class="flex justify-between"><span>{{ tr('ডিসকাউন্ট') }}</span><span>−৳{{ (+od.data.discount_amount || 0) + (+od.data.coupon_discount || 0) }}</span></div>
            <div class="flex justify-between"><span>{{ tr('ডেলিভারি চার্জ') }}</span><span>৳{{ od.data.shipping_charge }}</span></div>
            <div class="flex justify-between font-bold"><span>{{ tr('মোট') }}</span><span>৳{{ od.data.total_amount }}</span></div>
            <div class="flex justify-between text-gray-500"><span>{{ tr('কমিশন') }}</span><span>−৳{{ od.data.commission_amount }}</span></div>
            <div class="flex justify-between font-bold text-green-700"><span>{{ tr('আপনার প্রাপ্য') }}</span><span>৳{{ od.data.seller_payout }}</span></div>
          </div>
          <div v-if="od.data.courier_tracking_id" class="text-xs text-gray-600">{{ tr('কুরিয়ার ট্র্যাকিং') }}: {{ od.data.courier_tracking_id }}</div>
          <div v-if="od.data.status_histories?.length">
            <p class="font-bold text-gray-700 mb-1">{{ tr('স্ট্যাটাস ইতিহাস') }}</p>
            <ul class="text-xs text-gray-600 space-y-1">
              <li v-for="h in od.data.status_histories" :key="h.id">{{ statusLabel(h.status) }} · {{ fmtDate(h.created_at) }}<span v-if="h.note"> — {{ h.note }}</span></li>
            </ul>
          </div>
          <div class="flex gap-2 pt-1">
            <button v-for="a in nextActions(od.data.status)" :key="a.to" @click="setStatus(od.data, a.to).then(() => openOrder(od.id))"
                    class="px-4 py-2 rounded-lg text-sm font-bold border"
                    :class="a.danger ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-primary text-primary hover:bg-primary/10'">{{ a.label }}</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
const { tr } = useLang()
definePageMeta({ middleware: 'auth' })
useHead({ title: 'বিক্রেতা ড্যাশবোর্ড' })

const api = useApi()
const apiBase = useRuntimeConfig().public.apiBase
const stats      = ref({})
const orders     = ref([])
const products   = ref([])
const categories = ref([])
const blocked    = ref(null)
const tab        = ref('orders')
const msg        = ref('')
const msgOk      = ref(false)
const saving     = ref(false)
//const pf = reactive({ name: '', category_id: '', price: null, quantity: null, description: '', image: null, gallery: [] })


const pf = reactive({ name: '', category_id: '', price: null, quantity: null, description: '', image: null, gallery: [], variants: [] })
// draft variant for the "add product" form
const blankNv = () => ({ attrs: [{ attribute: '', value: '' }], price: null, compare_price: null, quantity: null, sku: '', is_active: true })
const nv = reactive(blankNv())
const stageVariant = () => {
  const attrs = nv.attrs.filter(a => a.attribute.trim() && a.value.trim()).map(a => ({ attribute: a.attribute.trim(), value: a.value.trim() }))
  if (!attrs.length || !nv.price || nv.quantity === null || nv.quantity === '') { flash('বৈশিষ্ট্য, দাম ও স্টক দিন।'); return }
  if (pf.variants.length >= 50) { flash('সর্বোচ্চ ৫০টি ভ্যারিয়েন্ট দেওয়া যায়।'); return }
  pf.variants.push({ attribute_values: attrs, price: nv.price, compare_price: nv.compare_price || null, quantity: nv.quantity, sku: nv.sku || null, is_active: nv.is_active })
  Object.assign(nv, blankNv())
}

const withdrawals = ref([])
const ledger      = ref([])
const notifs      = ref([])
const wf = reactive({ amount: null, idx: 0 })
const unread = computed(() => notifs.value.length ? notifs.value.filter(n => !n.read).length : (stats.value.unread_notifications ?? 0))

const tabs = [
  { key: 'orders', label: 'অর্ডার' }, { key: 'products', label: 'পণ্য' },
  { key: 'earnings', label: 'আয় ও উত্তোলন' }, { key: 'profile', label: 'শপ প্রোফাইল' }, { key: 'notifications', label: 'নোটিফিকেশন' },
]
const METHODS = { bkash: 'bKash', nagad: 'Nagad', bank: 'ব্যাংক' }
const methodLabel = (m) => METHODS[m] || m
const W_LABELS = { pending: 'অপেক্ষমাণ', paid: 'পরিশোধিত', rejected: 'বাতিল' }
const wLabel = (s) => W_LABELS[s] || s
const wClass = (s) => ({ pending: 'bg-yellow-50 text-yellow-700', paid: 'bg-green-50 text-green-700', rejected: 'bg-red-50 text-red-600' }[s] || 'bg-gray-100 text-gray-600')
const fmtDate = (d) => { try { return d ? new Date(d).toLocaleDateString('bn-BD', { year: 'numeric', month: 'short', day: 'numeric' }) : '' } catch { return '' } }
const cards = computed(() => [
  { label: 'মোট অর্ডার',      value: stats.value.total_orders ?? 0 },
  { label: 'পেন্ডিং অর্ডার',   value: stats.value.pending_orders ?? 0 },
  { label: 'ব্যালেন্স',        value: '৳' + (stats.value.balance ?? 0) },
  { label: 'ফলোয়ার',         value: stats.value.followers ?? 0 },
])

const LABELS = { pending: 'পেন্ডিং', confirmed: 'নিশ্চিত', processing: 'প্রসেসিং', shipped: 'শিপড', delivered: 'ডেলিভারড', cancelled: 'বাতিল', returned: 'রিটার্ন' }
const statusLabel = (s) => LABELS[s] || s
// Sellers may only move orders up to "shipped"; delivery is confirmed by the courier.
const nextActions = (s) => ({
  pending:    [{ to: 'confirmed', label: 'নিশ্চিত করুন' }, { to: 'cancelled', label: 'বাতিল', danger: true }],
  confirmed:  [{ to: 'processing', label: 'প্রসেসিং শুরু' }, { to: 'cancelled', label: 'বাতিল', danger: true }],
  processing: [{ to: 'shipped', label: 'শিপ করুন' }],
}[s] || [])

const flash = (text, ok = false) => { msg.value = text; msgOk.value = ok; setTimeout(() => (msg.value = ''), 4000) }
const list = (res) => res?.data ?? res ?? []

const handleBlocked = (e) => {
  const code = e?.data?.code || e?.code
  if (e?.status === 403 || code === 'NOT_A_SELLER' || code === 'SELLER_PENDING') {
    blocked.value = code === 'SELLER_PENDING'
      ? { icon: 'hourglass_top', title: 'আবেদন পর্যালোচনায় আছে', text: 'আপনার বিক্রেতা আবেদন অনুমোদনের অপেক্ষায়। অনুমোদন হলে এখানে ড্যাশবোর্ড চালু হবে।' }
      : (code === 'NOT_A_SELLER'
        ? { icon: 'storefront', title: 'আপনি এখনও বিক্রেতা নন', text: 'বিক্রি শুরু করতে বিক্রেতা হিসেবে আবেদন করুন।', link: '/seller/register', linkLabel: 'বিক্রেতা হোন' }
        : { icon: 'block', title: 'অ্যাক্সেস নেই', text: e.message || 'আপনার বিক্রেতা অ্যাকাউন্ট সক্রিয় নয়।' })
    return true
  }
  return false
}

const load = async () => {
  try {
    stats.value = await api.get('/seller/dashboard')
    const [o, p, c] = await Promise.all([api.get('/seller/orders'), api.get('/seller/products'), api.get('/categories')])
    orders.value = list(o); products.value = list(p)
    categories.value = Array.isArray(c) ? c : (c?.data ?? [])
  } catch (e) {
    if (!handleBlocked(e)) flash(e.message || 'ডেটা লোড করা যায়নি।')
  }
}

const loadEarnings = async () => {
  try {
    const [w, e] = await Promise.all([api.get('/seller/withdrawals'), api.get('/seller/earnings')])
    withdrawals.value = list(w)
    ledger.value = list(e?.transactions)
  } catch (e) { flash(e.message || 'আয়ের তথ্য লোড করা যায়নি।') }
}
const loadNotifs = async () => {
  try { notifs.value = list(await api.get('/seller/notifications')) } catch (e) { /* non-critical */ }
}
const markRead = async () => {
  try {
    await api.post('/seller/notifications/read', {})
    notifs.value = notifs.value.map(n => ({ ...n, read: true }))
    stats.value = { ...stats.value, unread_notifications: 0 }
  } catch (e) { flash(e.message || 'আপডেট করা যায়নি।') }
}
const requestWithdrawal = async () => {
  const m = (stats.value.payout_methods || [])[wf.idx]
  if (!m || !wf.amount) { flash('পরিমাণ ও মাধ্যম দিন।'); return }
  if (wf.amount < (stats.value.min_withdrawal || 0)) { flash(`সর্বনিম্ন উত্তোলন ৳${stats.value.min_withdrawal}।`); return }
  saving.value = true
  try {
    await api.post('/seller/withdrawals', { amount: wf.amount, method: m.method, account: m.account })
    flash('উত্তোলনের অনুরোধ জমা হয়েছে।', true)
    wf.amount = null
    stats.value = await api.get('/seller/dashboard')
    await loadEarnings()
  } catch (e) { flash(e?.data?.message || e.message || 'অনুরোধ জমা দেওয়া যায়নি।') }
  finally { saving.value = false }
}
watch(tab, (t) => { if (t === 'earnings') loadEarnings(); if (t === 'notifications') loadNotifs() })

const od = reactive({ open: false, loading: false, error: '', id: null, data: null })
const openOrder = async (id) => {
  od.open = true; od.loading = true; od.error = ''; od.id = id
  try { const r = await api.get(`/seller/orders/${id}`); od.data = r?.data ?? r }
  catch (e) { od.data = null; od.error = e.message || 'অর্ডার লোড করা যায়নি।' }
  finally { od.loading = false }
}
const orderOptLabel = (it) => {
  const o = it.variant_options
  if (!o) return ''
  if (Array.isArray(o)) return o.map(a => (a && typeof a === 'object') ? `${a.attribute ?? a.name ?? ''}: ${a.value ?? ''}` : String(a)).join(' | ')
  if (typeof o === 'object') return Object.entries(o).map(([k, v]) => `${k}: ${v}`).join(' | ')
  return String(o)
}

const setStatus = async (o, to) => {
  try {
    await api.put(`/seller/orders/${o.id}/status`, { status: to })
    o.status = to
    flash('স্ট্যাটাস আপডেট হয়েছে।', true)
    stats.value = await api.get('/seller/dashboard')
  } catch (e) { flash(e.message || 'আপডেট করা যায়নি।') }
}

const imgUrl = (im) => {
  const u = im?.full_url_image || im?.image_path || ''
  if (!u) return 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#eef1ef"/></svg>')
  return u.startsWith('http') ? u : `${apiBase}/storage/${u}`
}

// ── Product edit / delete ────────────────────────────────────
const editing = ref(null)
const editVariants = ref([])
const ef = reactive({ name: '', category_id: '', price: null, compare_price: null, quantity: 0, weight: null, unit: '', short_description: '', description: '', is_active: true })
const hasVariants = computed(() => editVariants.value.length > 0)
const blankVf = () => ({ id: null, attrs: [{ attribute: '', value: '' }], price: null, compare_price: null, quantity: 0, sku: '', is_active: true })
const vf = reactive(blankVf())
const resetVariant = () => Object.assign(vf, blankVf())
const variantLabel = (v) => (v.attribute_values || []).map(a => `${a.attribute}: ${a.value}`).join(' | ') || 'ভ্যারিয়েন্ট'

const reloadProducts = async () => {
  products.value = list(await api.get('/seller/products'))
  if (editing.value) {
    const fresh = products.value.find(x => x.id === editing.value.id)
    if (fresh) { editing.value = fresh; editVariants.value = fresh.variants || []; ef.quantity = fresh.quantity }
  }
}

const openEdit = (p) => {
  editing.value = p
  editVariants.value = p.variants || []
  Object.assign(ef, {
    name: p.name, category_id: p.category_id, price: Number(p.price), compare_price: p.compare_price ? Number(p.compare_price) : null,
    quantity: p.quantity, weight: p.weight ? Number(p.weight) : null, unit: p.unit || '', short_description: p.short_description || '',
    description: p.description || '', is_active: !!p.is_active,
  })
  resetVariant()
}

const saveEdit = async () => {
  if (!ef.name.trim() || !ef.price) { flash('নাম ও দাম দিন।'); return }
  saving.value = true
  try {
    const body = {
      name: ef.name.trim(), category_id: ef.category_id, price: ef.price, compare_price: ef.compare_price || null,
      weight: ef.weight || null, unit: ef.unit || null, short_description: ef.short_description || null, description: ef.description || null,
    }
    if (!hasVariants.value) body.quantity = ef.quantity ?? 0
    if (editing.value.is_active) body.is_active = ef.is_active
    await api.put(`/seller/products/${editing.value.id}`, body)
    flash('পণ্য আপডেট হয়েছে।', true)
    await reloadProducts()
  } catch (e) { flash(e.message || 'আপডেট করা যায়নি।') }
  finally { saving.value = false }
}

const removeProduct = async (p) => {
  if (!confirm(`"${p.name}" পণ্যটি মুছে ফেলবেন?`)) return
  try {
    await api.delete(`/seller/products/${p.id}`)
    flash('পণ্য মোছা হয়েছে।', true)
    if (editing.value?.id === p.id) editing.value = null
    await reloadProducts()
  } catch (e) { flash(e.message || 'মোছা যায়নি।') }
}

const startVariant = (v) => {
  Object.assign(vf, {
    id: v.id, attrs: (v.attribute_values?.length ? v.attribute_values : [{ attribute: '', value: '' }]).map(a => ({ attribute: a.attribute, value: a.value })),
    price: Number(v.price), compare_price: v.compare_price ? Number(v.compare_price) : null, quantity: v.quantity, sku: v.sku || '', is_active: !!v.is_active,
  })
}

const saveVariant = async () => {
  const attrs = vf.attrs.filter(a => a.attribute.trim() && a.value.trim())
  if (!attrs.length || !vf.price || vf.quantity === null || vf.quantity === '') { flash('বৈশিষ্ট্য, দাম ও স্টক দিন।'); return }
  const body = { attribute_values: attrs, price: vf.price, compare_price: vf.compare_price || null, quantity: vf.quantity, sku: vf.sku || null, is_active: vf.is_active }
  saving.value = true
  try {
    if (vf.id) await api.put(`/seller/products/${editing.value.id}/variants/${vf.id}`, body)
    else await api.post(`/seller/products/${editing.value.id}/variants`, body)
    flash('ভ্যারিয়েন্ট সেভ হয়েছে।', true)
    resetVariant()
    await reloadProducts()
  } catch (e) { flash(e.message || 'ভ্যারিয়েন্ট সেভ করা যায়নি।') }
  finally { saving.value = false }
}

const removeVariant = async (v) => {
  if (!confirm('এই ভ্যারিয়েন্ট মুছবেন?')) return
  try {
    await api.delete(`/seller/products/${editing.value.id}/variants/${v.id}`)
    resetVariant()
    await reloadProducts()
  } catch (e) { flash(e.message || 'মোছা যায়নি।') }
}

// ── Shop profile ─────────────────────────────────────────────
const shop = reactive({ shop_name: '', shop_name_bn: '', description: '', phone: '', email: '', address: '', bkash_number: '', bank_name: '', bank_account: '', logo: null, banner: null, logo_preview: null, banner_preview: null, logo_file: null, banner_file: null })
const loadProfile = async () => {
  try { Object.assign(shop, (await api.get('/seller/profile')) ?? {}, { logo_file: null, banner_file: null, logo_preview: null, banner_preview: null }) } catch { /* ignore */ }
}
const pickShopFile = (field, e) => {
  const f = e.target.files?.[0]
  if (!f) return
  shop[field + '_file'] = f
  shop[field + '_preview'] = URL.createObjectURL(f)
}
const saveProfile = async () => {
  saving.value = true
  try {
    const fd = new FormData()
    for (const k of ['shop_name_bn', 'description', 'phone', 'email', 'address', 'bkash_number', 'bank_name', 'bank_account']) fd.append(k, shop[k] ?? '')
    if (shop.logo_file) fd.append('logo', shop.logo_file)
    if (shop.banner_file) fd.append('banner', shop.banner_file)
    await api.upload('/seller/profile', fd)
    flash('প্রোফাইল সেভ হয়েছে।', true)
    await loadProfile()
  } catch (e) { flash(e.message || 'প্রোফাইল সেভ করা যায়নি।') }
  finally { saving.value = false }
}
watch(tab, (t) => { if (t === 'profile') loadProfile() })

const addImages = async (p, e) => {
  const files = Array.from(e.target.files || []).slice(0, 8)
  e.target.value = ''
  if (!files.length) return
  const fd = new FormData()
  files.forEach(f => fd.append('gallery[]', f))
  try {
    await api.upload(`/seller/products/${p.id}/images`, fd)
    flash('ছবি যোগ হয়েছে।', true)
    products.value = list(await api.get('/seller/products'))
  } catch (err) { flash(err.message || 'ছবি আপলোড করা যায়নি।') }
}

const removeImage = async (p, im) => {
  if (!confirm('এই ছবিটি মুছবেন?')) return
  try {
    await api.delete(`/seller/products/${p.id}/images/${im.id}`)
    products.value = list(await api.get('/seller/products'))
  } catch (err) { flash(err.message || 'ছবি মোছা যায়নি।') }
}

const addProduct = async () => {
  const hasVars = pf.variants.length > 0

  if (!pf.name.trim() || !pf.category_id || !pf.price || (!hasVars && (pf.quantity === null || pf.quantity === ''))) {
    flash('নাম, ক্যাটাগরি, দাম ও স্টক দিন।'); return
  }

  const fd = new FormData()
  fd.append('name', pf.name.trim()); fd.append('category_id', pf.category_id)
  fd.append('price', pf.price)

  if (hasVars) fd.append('variants', JSON.stringify(pf.variants))
  else fd.append('quantity', pf.quantity)

  if (pf.description) fd.append('description', pf.description)
  if (pf.image) fd.append('image', pf.image)
  pf.gallery.forEach(f => fd.append('gallery[]', f))
  saving.value = true
  try {
    await api.upload('/seller/products', fd)
    flash('পণ্য জমা হয়েছে। অনুমোদনের পর সাইটে দেখা যাবে।', true)
    Object.assign(pf, { name: '', category_id: '', price: null, quantity: null, description: '', image: null, gallery: [], variants: [] })
    Object.assign(nv, blankNv())
    products.value = list(await api.get('/seller/products'))
  } catch (e) { flash(e.message || 'পণ্য জমা দেওয়া যায়নি।') }
  finally { saving.value = false }
}

onMounted(async () => {
  await load(); if (!blocked.value) loadNotifs()
  const oid = Number(useRoute().query.order)
  if (oid && !blocked.value) { tab.value = 'orders'; openOrder(oid) }
})
</script>

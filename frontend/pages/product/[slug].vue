<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <!-- ═══════════════════════════════════════════════════════════════
         LOADING SKELETON
    ════════════════════════════════════════════════════════════════════ -->
    <div v-if="loading" class="skeleton-root">
      <nav class="sk-breadcrumb">
        <div class="sk sk-pill" style="width:36px;height:14px;"></div>
        <div class="sk sk-chevron"></div>
        <div class="sk sk-pill" style="width:64px;height:14px;"></div>
        <div class="sk sk-chevron"></div>
        <div class="sk sk-pill" style="width:140px;height:14px;"></div>
      </nav>
      <div class="sk-main-grid">
        <div class="sk-image-col">
          <div class="sk sk-main-img">
            <div class="sk-abs-tl">
              <div class="sk-inner sk-pill" style="width:60px;height:26px;"></div>
              <div class="sk-inner sk-pill" style="width:78px;height:26px;margin-top:6px;"></div>
            </div>
            <div class="sk-abs-tr">
              <div class="sk-inner sk-circle" style="width:40px;height:40px;"></div>
            </div>
            <div class="sk-abs-br">
              <div class="sk-inner sk-pill" style="width:80px;height:24px;"></div>
            </div>
          </div>
          <div class="sk-thumb-grid">
            <div v-for="i in 4" :key="i" class="sk sk-thumb"></div>
          </div>
        </div>
        <div class="sk-info-col">
          <div class="sk-badge-row">
            <div class="sk sk-pill" style="width:96px;height:24px;"></div>
            <div class="sk sk-pill" style="width:64px;height:24px;"></div>
          </div>
          <div class="sk-title-block">
            <div class="sk sk-rounded" style="width:90%;height:20px;"></div>
            <div class="sk sk-rounded" style="width:62%;height:20px;margin-top:8px;"></div>
          </div>
          <div class="sk-rating-row">
            <div class="sk-stars">
              <div v-for="i in 5" :key="i" class="sk sk-star"></div>
            </div>
            <div class="sk sk-pill" style="width:90px;height:14px;"></div>
            <div class="sk sk-pill" style="width:104px;height:14px;"></div>
          </div>
          <div class="sk sk-price-card">
            <div class="sk-inner sk-rounded" style="height:46px;width:100%;margin-bottom:10px;"></div>
            <div class="sk-price-row">
              <div class="sk-inner sk-pill" style="width:110px;height:38px;"></div>
              <div class="sk-inner sk-pill" style="width:72px;height:22px;"></div>
              <div class="sk-inner sk-pill" style="width:58px;height:22px;"></div>
            </div>
            <div class="sk-inner sk-pill" style="width:155px;height:14px;margin:8px 0;"></div>
            <div class="sk-inner sk-rounded" style="height:38px;width:100%;margin-bottom:6px;"></div>
            <div class="sk-inner sk-rounded" style="height:38px;width:100%;"></div>
          </div>
          <div class="sk-variants-block">
            <div class="sk sk-pill" style="width:90px;height:14px;margin-bottom:8px;"></div>
            <div class="sk-variant-pills">
              <div v-for="i in 3" :key="i" class="sk sk-rounded" style="width:78px;height:36px;"></div>
            </div>
          </div>
          <div style="margin-bottom:14px;">
            <div class="sk sk-pill" style="width:140px;height:14px;"></div>
          </div>
          <div class="sk-cart-row">
            <div class="sk sk-rounded" style="width:116px;height:50px;flex-shrink:0;"></div>
            <div class="sk sk-rounded" style="flex:1;height:50px;"></div>
          </div>
          <div class="sk sk-rounded" style="width:100%;height:50px;margin-bottom:18px;"></div>
          <div class="sk-delivery-rows">
            <div v-for="i in 4" :key="i" class="sk sk-rounded" style="width:100%;height:56px;"></div>
          </div>
        </div>
      </div>
      <div class="sk-tabs-card">
        <div class="sk-tabs-header">
          <div v-for="(w,i) in [90,106,52,88]" :key="i" class="sk sk-pill sk-tab-label" :style="`width:${w}px;height:14px;`"></div>
        </div>
        <div class="sk-tabs-body">
          <div v-for="(w,i) in ['100%','93%','97%','76%','88%','70%','94%','58%']" :key="i" class="sk sk-rounded sk-line" :style="`width:${w};`"></div>
        </div>
      </div>
      <section>
        <div class="sk-section-title">
          <div class="sk sk-rounded" style="width:20px;height:20px;"></div>
          <div class="sk sk-pill" style="width:110px;height:18px;"></div>
        </div>
        <div class="sk-related-grid">
          <div v-for="(item,i) in skRelated" :key="i" class="sk-product-card">
            <div class="sk sk-rounded sk-product-img"></div>
            <div class="sk sk-pill sk-product-line" :style="`width:${item.name}%;`"></div>
            <div class="sk sk-pill sk-product-line" :style="`width:${item.price}%;`"></div>
          </div>
        </div>
      </section>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         ERROR STATE
    ════════════════════════════════════════════════════════════════════ -->
    <div v-else-if="error" class="text-center py-20">
      <span class="mi text-6xl text-gray-200 block mb-4">error_outline</span>
      <p class="font-bengali text-gray-500 mb-4">{{ tr('পণ্যটি লোড করা যায়নি।') }}</p>
      <button @click="loadProduct" class="btn-primary">{{ tr('আবার চেষ্টা করুন') }}</button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         PRODUCT PAGE
    ════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="product">

      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1 text-sm font-bengali text-gray-500 mb-6 flex-wrap">
        <NuxtLink to="/" class="hover:text-primary flex items-center gap-0.5 shrink-0 transition-colors">
          <span class="mi mi-sm">home</span>{{ tr('হোম') }}
        </NuxtLink>
        <span class="mi mi-sm">chevron_right</span>
        <NuxtLink v-if="product.category" :to="`/category/${product.category.slug}`" class="hover:text-primary shrink-0 transition-colors">{{ product.category.name }}</NuxtLink>
        <span class="mi mi-sm">chevron_right</span>
        <span class="text-gray-800 truncate">{{ product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">

        <!-- ══════════════════════════════
             IMAGE COLUMN
        ═══════════════════════════════ -->
        <div class="space-y-4">
          <div class="bg-white rounded-2xl shadow-card overflow-hidden relative cursor-zoom-in" @click="openZoom">
            <img :src="activeImg" :alt="product.name" class="w-full aspect-square object-cover transition-opacity duration-200"/>
            <!-- Badges: top-left -->
            <div class="absolute top-4 left-4 flex flex-col gap-2">
              <!-- Flash sale discount badge -->
              <span v-if="flashSaleItem && !flashSaleExpired" class="badge bg-accent text-white text-sm px-3 py-1 flex items-center gap-1">
                <span class="mi mi-sm">flash_on</span>{{ Number(flashSaleItem.discount_percentage) }}% ছাড়
              </span>
              <!-- Regular discount (only when no active flash sale) -->
              <span v-else-if="product.discount_percentage" class="badge bg-accent text-white text-sm px-3 py-1">{{ product.discount_percentage }}% ছাড়</span>

              <span v-if="product.is_new" class="badge bg-blue-500 text-white text-sm px-3 py-1">{{ tr('নতুন') }}</span>
              <span v-if="product.is_trending" class="badge bg-orange-500 text-white text-sm px-3 py-1 flex items-center gap-1">
                <span class="mi mi-sm">trending_up</span>{{ tr('ট্রেন্ডিং') }}
              </span>
            </div>

            <!-- Wishlist: top-right -->
            <button @click.stop="toggleWish" class="absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center hover:bg-accent/10 transition-colors">
              <span class="mi mi-sm" :class="wishListed ? 'mi-filled text-accent' : 'text-gray-400'">favorite</span>
            </button>

            <!-- Zoom hint: bottom-right -->
            <div class="absolute bottom-3 right-3 bg-black/40 text-white px-2 py-1 rounded-lg text-xs font-bengali flex items-center gap-1">
              <span class="mi mi-sm">zoom_in</span>{{ tr('জুম করুন') }}
            </div>

            <!-- Free shipping badge: bottom-left -->
            <div v-if="product.free_shipping" class="absolute bottom-3 left-3 bg-primary text-white px-2 py-1 rounded-lg text-xs font-bengali flex items-center gap-1">
              <span class="mi mi-sm">local_shipping</span>{{ tr('ফ্রি ডেলিভারি') }}
            </div>
          </div>

          <!-- Thumbnails -->
          <div v-if="product.images?.length > 1" class="grid grid-cols-4 gap-3">
            <button v-for="(img, i) in product.images" :key="img.id" @click="activeImgIdx = i" class="aspect-square rounded-xl border-2 overflow-hidden transition-all" :class="activeImgIdx === i ? 'border-primary ring-2 ring-primary/20' : 'border-gray-100 hover:border-gray-300'">
              <img :src="img.full_url_image" :alt="`Thumbnail ${i + 1}`" class="w-full h-full object-cover"/>
            </button>
          </div>
        </div>

        <!-- ══════════════════════════════
             INFO COLUMN
        ═══════════════════════════════ -->
        <div>
          <!-- Seller + Brand + SKU badges -->
          <div class="flex items-center gap-2 mb-3 flex-wrap">
            <NuxtLink v-if="product.seller" :to="`/seller/${product.seller.slug}`" class="badge bg-primary/10 text-primary hover:bg-primary/20 flex items-center gap-1 transition-colors">
              <span class="mi mi-sm">storefront</span>{{ product.seller.shop_name }}
              <span v-if="product.seller.rating" class="flex items-center gap-0.5 ml-1 text-gold">
                <span class="mi mi-sm" style="font-size:11px">star</span>{{ product.seller.rating }}
              </span>
            </NuxtLink>
            <span v-if="product.brand" class="badge bg-blue-100 text-blue-700 flex items-center gap-1">
              <span class="mi mi-sm">verified</span>{{ product.brand.name }}
            </span>
            <span v-if="product.sku" class="badge bg-gray-100 text-gray-500 text-xs">SKU: {{ product.sku }}</span>
          </div>

          <!-- Name -->
          <h1 class="font-display font-extrabold text-xl md:text-2xl text-gray-900 mb-1 leading-tight">{{ product.name }}</h1>
          <p v-if="product.name_bn" class="font-bengali text-gray-500 text-base mb-3">{{ product.name_bn }}</p>

          <!-- Rating + stats row -->
          <div class="flex items-center gap-3 mb-5 flex-wrap">
            <div class="flex gap-0.5">
              <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i <= Math.round(Number(product.rating) || 0) ? 'mi-filled text-gold' : 'text-gray-300'" style="font-size:18px">star</span>
            </div>
            <a href="#reviews" class="text-sm text-gray-500 font-bengali hover:text-primary transition-colors">{{ product.rating }} ({{ product.total_reviews }} রিভিউ)</a>
            <span v-if="product.total_sales" class="text-sm text-green-600 font-bengali flex items-center gap-1">
              <span class="mi mi-sm">check_circle</span>{{ Number(product.total_sales).toLocaleString() }} জন কিনেছেন
            </span>
            <span v-if="product.total_views" class="text-sm text-gray-400 font-bengali flex items-center gap-1">
              <span class="mi mi-sm">visibility</span>{{ Number(product.total_views).toLocaleString() }}
            </span>
          </div>

          <!-- ── PRICE CARD ── -->
          <div class="my-7">

            <!-- Flash sale banner -->
            <div v-if="flashSaleItem && !flashSaleExpired" class="mb-3">
              <div class="flex items-center gap-2 bg-accent/10 border border-accent/20 rounded-xl p-3 mb-2">
                <span class="mi text-accent text-2xl">flash_on</span>
                <div class="flex-1 min-w-0">
                  <p class="font-bengali text-accent font-bold text-sm leading-tight">{{ flashSaleItem.flash_sale?.title_bn || flashSaleItem.flash_sale?.title || 'ফ্ল্যাশ সেল চলছে!' }}</p>
                  <p class="font-bengali text-xs text-accent/70">শেষ হবে: {{ flashSaleEndsAt }}</p>
                </div>
                <div class="shrink-0 text-right">
                  <p class="font-display font-extrabold text-accent text-lg">{{ Number(flashSaleItem.discount_percentage) }}% ছাড়</p>
                </div>
              </div>

              <!-- Flash sale stock progress -->
              <div v-if="flashSaleItem.quantity_limit && flashSaleItem.sold_count !== undefined" class="bg-orange-50 border border-orange-100 rounded-xl p-3">
                <div class="flex justify-between items-center mb-1.5">
                  <span class="font-bengali text-xs text-orange-700 font-semibold flex items-center gap-1">
                    <span class="mi mi-sm" style="font-size:14px">local_fire_department</span>
                    {{ tr('দ্রুত শেষ হচ্ছে!') }}
                  </span>
                  <span class="font-bengali text-xs text-orange-600">
                    {{ flashSaleItem.sold_count }} / {{ flashSaleItem.quantity_limit }} বিক্রি
                  </span>
                </div>
                <div class="w-full h-2 bg-orange-100 rounded-full overflow-hidden">
                  <div
                      class="h-full bg-gradient-to-r from-orange-400 to-red-500 rounded-full transition-all"
                      :style="`width:${Math.min(100, Math.round((flashSaleItem.sold_count / flashSaleItem.quantity_limit) * 100))}%`"
                  ></div>
                </div>
                <p class="font-bengali text-xs text-orange-500 mt-1">
                  মাত্র {{ flashSaleItem.quantity_limit - flashSaleItem.sold_count }} টি বাকি আছে
                </p>
              </div>
            </div>

            <!-- Price row -->
            <div class="flex items-baseline gap-3 mb-2 flex-wrap">
              <span class="font-display font-extrabold text-3xl text-accent">
                ৳{{ Number(activePrice).toLocaleString() }}
              </span>
              <!-- Original price: flash sale → product.price | regular → compare_price -->
              <span v-if="activeSavings > 0" class="text-xl text-gray-400 line-through font-bengali">
                ৳{{ Number(originalPrice).toLocaleString() }}
              </span>
              <span v-if="activeDiscountPct > 0" class="badge bg-accent text-white">
                {{ activeDiscountPct }}% ছাড়
              </span>
            </div>

            <!-- Savings line -->
            <p v-if="activeSavings > 0"
               class="text-green-600 font-bengali text-sm font-semibold mb-3 flex items-center gap-1">
              <span class="mi mi-sm">savings</span>আপনি সাশ্রয় করছেন: ৳{{ Number(activeSavings).toLocaleString() }}
            </p>

            <!-- EMI options -->
<!--            <div class="space-y-2">
              <div class="flex items-center gap-3 bg-pink-50 border border-pink-200 rounded-xl p-3">
                <span class="mi text-bkash">phone_iphone</span>
                <span class="font-bengali text-sm text-bkash font-semibold">{{ tr('bKash EMI উপলব্ধ') }}</span>
              </div>
              <div class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl p-3">
                <span class="mi text-blue-600">credit_card</span>
                <span class="font-bengali text-sm text-blue-700 font-semibold">{{ tr('ক্রেডিট কার্ড EMI: ৩-১২ মাসে পরিশোধ') }}</span>
              </div>
            </div>-->
          </div>

          <!-- ── VARIANTS ── -->
          <!-- Case A: variants have attribute_values filled -->
          <div v-if="product.variants?.length && hasVariantAttributes" class="mb-4">
            <p class="font-display font-semibold text-gray-700 mb-2 flex items-center gap-1">
              <span class="mi mi-sm text-primary">tune</span>
              {{ tr('ভ্যারিয়েন্ট:') }} <span class="text-primary ml-1">{{ selectedVariantLabel }}</span>
            </p>
            <div class="flex gap-2 flex-wrap">
              <button v-for="v in product.variants" :key="v.id" @click="selectVariant(v)" :disabled="!v.is_active || v.quantity === 0" class="px-4 py-2 rounded-xl border-2 text-sm font-bengali transition-all relative" :class="[selectedVariant?.id === v.id ? 'border-primary bg-primary/5 text-primary font-bold' : 'border-gray-200 text-gray-600 hover:border-gray-300', (!v.is_active || v.quantity === 0) ? 'opacity-50 cursor-not-allowed line-through' : '']">
                {{ getVariantLabel(v) }}
                <span v-if="Number(v.price) !== Number(product.price)" class="ml-1 text-accent text-xs">
                  ৳{{ Number(v.price).toLocaleString() }}
                </span>
              </button>
            </div>
          </div>

          <!-- Case B: variants exist but attribute_values are empty arrays — show SKU-based picker -->
          <div v-else-if="product.variants?.length && !hasVariantAttributes" class="mb-4">
            <p class="font-display font-semibold text-gray-700 mb-2 flex items-center gap-1">
              <span class="mi mi-sm text-primary">tune</span>
              {{ tr('অপশন নির্বাচন করুন') }}
              <span class="text-primary ml-1 text-sm font-bengali">({{ selectedVariant?.sku }})</span>
            </p>
            <div class="flex gap-2 flex-wrap">
              <client-only>
                <template v-for="(variant, idx) in product.variants" :key="variant.id" @click="selectVariant(variant)">
                  <button class="px-4 py-2 rounded-xl border-2 text-sm font-bengali transition-all" :disabled="!variant.is_active || variant.quantity === 0" :class="[selectedVariant?.id === variant.id ? 'border-primary bg-primary/5 text-primary font-bold' : 'border-gray-200 text-gray-600 hover:border-gray-300', (!variant.is_active || variant.quantity === 0) ? 'opacity-50 cursor-not-allowed' : '']">
                    অপশন {{ idx + 1 }}
                    <span v-if="Number(variant.price) !== Number(product.price)" class="ml-1 text-accent text-xs">৳{{ Number(variant.price).toLocaleString() }}</span>
                  </button>
                </template>
              </client-only>
            </div>
          </div>

          <!-- ── STOCK ── -->
          <div class="mb-4">
            <p class="font-bengali text-sm font-semibold flex items-center gap-1" :class="inStock ? 'text-green-600' : 'text-accent'">
              <span class="mi mi-sm">{{ inStock ? 'check_circle' : 'cancel' }}</span>
              <template v-if="inStock">
                {{ tr('স্টকে আছে') }}
                <span v-if="currentStock <= (product.low_stock_threshold ?? 5)" class="text-orange-500 ml-1">(মাত্র {{ currentStock }} টি বাকি!)</span>
                <span v-else class="text-green-600 ml-1">({{ currentStock }} টি স্টকে)</span>
              </template>
              <template v-else>{{ tr('স্টক শেষ') }}</template>
            </p>
          </div>

          <!-- ── QTY + CART ── -->
          <div class="flex items-center gap-3 mb-3">
            <div class="cus-product-quantity flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
              <button @click="qty = Math.max(1, qty - 1)" class="px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors"><span class="mi mi-sm">remove</span></button>
              <span class="px-5 py-3 font-display font-bold text-base min-w-[3rem] text-center">{{ qty }}</span>
              <button @click="qty = Math.min(currentStock, qty + 1)" :disabled="qty >= currentStock" class="px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors disabled:opacity-40"><span class="mi mi-sm">add</span></button>
            </div>
            <button @click="addToCart" :disabled="!inStock || addingToCart" class="cus-details-add-to-cart flex-1 btn-primary justify-center py-3.5 text-base disabled:opacity-60 disabled:cursor-not-allowed">
              <span v-if="addingToCart" class="mi mi-sm animate-spin">refresh</span>
              <span v-else class="mi">shopping_cart</span>
              {{ addingToCart ? 'যোগ হচ্ছে...' : 'কার্টে যোগ করুন' }}
            </button>
          </div>

          <NuxtLink to="/checkout" @click.native="addToCart" class="cus-details-buy-now w-full btn-secondary justify-center py-3.5 text-base block text-center mb-5">
            <span class="mi mi-sm">bolt</span> {{ tr('এখনই কিনুন') }}
          </NuxtLink>

          <!-- ── DELIVERY INFO ── -->
          <div class="space-y-2">
            <div v-for="d in deliveryInfo" :key="d.title" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
              <span class="mi text-primary">{{ d.icon }}</span>
              <div class="flex-1">
                <p class="font-bengali font-semibold text-gray-800 text-sm">{{ d.title }}</p>
                <p class="font-bengali text-gray-500 text-xs">{{ d.desc }}</p>
              </div>
              <span v-if="d.badge" class="badge bg-green-100 text-green-700 text-xs">{{ d.badge }}</span>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="product.tags?.length" class="mt-4 flex flex-wrap gap-2">
            <span v-for="tag in product.tags" :key="tag" class="badge bg-gray-100 text-gray-500 text-xs">#{{ tag }}</span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════
           TABS
      ════════════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-2xl shadow-card overflow-hidden mb-8">
        <div class="flex overflow-x-auto border-b border-gray-100">
          <button v-for="tab in tabs" :key="tab.label" @click="activeTab = tab.label" class="px-6 py-4 font-display font-semibold text-sm whitespace-nowrap border-b-2 transition-colors flex items-center gap-2" :class="activeTab === tab.label ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'">
            <span class="mi mi-sm">{{ tab.icon }}</span>{{ tab.label }}
          </button>
        </div>

        <!-- ── Description ── -->
        <div v-if="activeTab === 'বিস্তারিত তথ্য'" class="p-6">
          <div class="prose prose-sm font-bengali text-gray-700 max-w-none" v-html="product.description || product.description_bn || 'বিস্তারিত তথ্য পাওয়া যায়নি।'"></div>
          <div v-if="product.short_description" class="mt-4 p-4 bg-gray-50 rounded-xl">
            <p class="font-bengali text-gray-600 text-sm">{{ product.short_description }}</p>
          </div>
        </div>

        <!-- ── Specifications ── -->
        <div v-if="activeTab === 'স্পেসিফিকেশন'" class="p-6">
          <!-- Variants table (always shown if variants exist) -->
          <div v-if="product.variants?.length" class="mb-6">
            <h3 class="font-display font-bold text-gray-800 mb-3 text-sm flex items-center gap-2">
              <span class="mi mi-sm text-primary">tune</span>{{ tr('ভ্যারিয়েন্ট তথ্য') }}
            </h3>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
              <table class="w-full text-sm font-bengali">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="text-left p-3 font-semibold text-gray-600">#</th>
                    <th class="text-left p-3 font-semibold text-gray-600">SKU</th>
                    <th class="text-left p-3 font-semibold text-gray-600">{{ tr('বিক্রয় মূল্য') }}</th>
                    <th class="text-left p-3 font-semibold text-gray-600">{{ tr('আসল মূল্য') }}</th>
                    <th class="text-left p-3 font-semibold text-gray-600">{{ tr('স্টক') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="(v, idx) in product.variants" :key="v.id" class="transition-colors" :class="selectedVariant?.id === v.id ? 'bg-primary/5' : 'hover:bg-gray-50/50'">
                    <td class="p-3 text-gray-400 text-xs">{{ idx + 1 }}</td>
                    <td class="p-3 text-gray-500">{{ v.sku }}</td>
                    <td class="p-3 font-semibold text-accent">৳{{ Number(v.price).toLocaleString() }}</td>
                    <td class="p-3 text-gray-400 line-through">৳{{ Number(v.compare_price).toLocaleString() }}</td>
                    <td class="p-3">
                        <span :class="v.quantity > 0 ? 'text-green-600' : 'text-red-500'">{{ v.quantity }} টি</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Product attributes (from API) -->
          <div v-if="product.attributes?.length" class="divide-y divide-gray-50">
            <div v-for="attr in product.attributes" :key="attr.name" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ attr.name }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm">{{ attr.value }}</span>
            </div>
          </div>

          <!-- Fallback: basic info from product fields -->
          <div v-if="!product.attributes?.length" class="divide-y divide-gray-50">
            <div class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('পণ্যের নাম') }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm">{{ product.name }}</span>
            </div>
            <div v-if="product.brand" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('ব্র্যান্ড') }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm flex items-center gap-2">
                {{ product.brand.name }}
                <a v-if="product.brand.website" :href="product.brand.website" target="_blank" class="text-primary text-xs hover:underline">{{ tr('ওয়েবসাইট') }}</a>
              </span>
            </div>
            <div v-if="product.sku" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">SKU</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm font-mono">{{ product.sku }}</span>
            </div>
            <div v-if="product.weight" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('ওজন') }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm">{{ product.weight }} kg</span>
            </div>
            <div v-if="product.unit" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('একক') }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm">{{ product.unit }}</span>
            </div>
            <div v-if="product.category" class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('ক্যাটাগরি') }}</span>
              <span class="flex-1 font-bengali text-gray-800 text-sm">{{ product.category.name }}</span>
            </div>
            <div class="flex items-center py-3">
              <span class="w-1/3 font-bengali font-semibold text-gray-600 text-sm">{{ tr('বৈশিষ্ট্য') }}</span>
              <div class="flex-1 flex flex-wrap gap-2">
                <span v-if="product.is_featured" class="badge bg-purple-100 text-purple-700 text-xs">{{ tr('ফিচার্ড') }}</span>
                <span v-if="product.is_new" class="badge bg-blue-100 text-blue-700 text-xs">{{ tr('নতুন') }}</span>
                <span v-if="product.is_trending" class="badge bg-orange-100 text-orange-700 text-xs">{{ tr('ট্রেন্ডিং') }}</span>
                <span v-if="product.free_shipping" class="badge bg-green-100 text-green-700 text-xs">{{ tr('ফ্রি ডেলিভারি') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Reviews ── -->
        <div v-if="activeTab === 'রিভিউ'" id="reviews" class="p-6">
          <div v-if="loadingReviews" class="text-center py-8">
            <span class="mi animate-spin text-primary text-3xl">refresh</span>
          </div>
          <div v-else>
            <!-- Rating summary card -->
            <div class="flex items-center gap-6 md:gap-8 mb-6 p-5 bg-gray-50 rounded-2xl flex-wrap">
              <div class="text-center shrink-0">
                <p class="font-display font-extrabold text-5xl text-gray-800">{{ product.rating ?? '0' }}</p>
                <div class="flex gap-0.5 justify-center my-1">
                  <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i <= Math.round(Number(product.rating) || 0) ? 'mi-filled text-gold' : 'text-gray-300'" style="font-size:16px">star</span>
                </div>
                <p class="text-xs font-bengali text-gray-500">{{ product.total_reviews }} রিভিউ</p>
              </div>
              <div class="flex-1 space-y-1.5 min-w-[140px]">
                <div v-for="(pct, i) in ratingBars" :key="i" class="flex items-center gap-2">
                  <span class="text-xs font-bengali text-gray-500 w-3 shrink-0">{{ 5 - i }}</span>
                  <span class="mi mi-sm text-gold shrink-0" style="font-size:12px">star</span>
                  <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gold rounded-full transition-all duration-500" :style="`width:${pct}%`"></div>
                  </div>
                  <span class="text-xs text-gray-400 w-8 text-right shrink-0">{{ pct }}%</span>
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="reviews.length === 0" class="text-center py-8">
              <span class="mi text-4xl text-gray-200 block mb-2">rate_review</span>
              <p class="font-bengali text-gray-400">{{ tr('এখনো কোনো রিভিউ নেই।') }}</p>
            </div>

            <!-- Review list -->
            <div v-else class="space-y-5">
              <div v-for="rev in reviews" :key="rev.id" class="border-b border-gray-50 pb-5 last:border-0">
                <div class="flex items-start gap-3 mb-2">
                  <img :src="rev.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(rev.user?.name || 'User')}&background=006A4E&color=fff`" :alt="rev.user?.name" class="w-10 h-10 rounded-full object-cover shrink-0" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between flex-wrap gap-1">
                      <p class="font-bengali font-semibold text-gray-800 text-sm">{{ rev.user?.name }}</p>
                      <p class="text-xs font-bengali text-gray-400 flex items-center gap-1">
                        <span class="mi mi-sm">calendar_today</span>
                        {{ new Date(rev.created_at).toLocaleDateString('bn-BD') }}
                      </p>
                    </div>
                    <!-- Star rating -->
                    <div class="flex gap-0.5 my-1">
                      <span
                          v-for="i in 5"
                          :key="i"
                          class="mi mi-sm"
                          :class="i <= rev.rating ? 'mi-filled text-gold' : 'text-gray-300'"
                          style="font-size:14px"
                      >star</span>
                    </div>
                    <!-- Review title -->
                    <p v-if="rev.title" class="font-bengali text-gray-800 text-sm font-semibold mb-1">{{ rev.title
                      }}</p>
                    <!-- Review body -->
                    <p class="font-bengali text-gray-700 text-sm leading-relaxed">{{ rev.review }}</p>
                    <!-- Review images -->
                    <div v-if="rev.images?.length" class="flex gap-2 mt-2 flex-wrap">
                      <img
                          v-for="img in rev.images"
                          :key="img"
                          :src="img"
                          class="w-16 h-16 object-cover rounded-xl cursor-pointer hover:opacity-90 transition-opacity"
                      />
                    </div>
                    <!-- Badges row -->
                    <div class="flex gap-2 mt-2 flex-wrap">
                      <span v-if="rev.order_id"
                            class="badge bg-green-100 text-green-700 text-xs flex items-center gap-1 w-fit">
                        <span class="mi mi-sm" style="font-size:12px">verified</span>{{ tr('যাচাইকৃত কেনাকাটা') }}
                      </span>
                      <span v-if="rev.is_featured"
                            class="badge bg-purple-100 text-purple-700 text-xs flex items-center gap-1 w-fit">
                        <span class="mi mi-sm" style="font-size:12px">star</span>{{ tr('ফিচার্ড') }}
                      </span>
                    </div>
                    <!-- Admin reply -->
                    <div v-if="rev.admin_reply" class="mt-3 p-3 bg-primary/5 border-l-2 border-primary rounded-r-xl">
                      <p class="font-bengali text-xs text-primary font-semibold mb-1 flex items-center gap-1">
                        <span class="mi mi-sm">support_agent</span>{{ tr('বিক্রেতার উত্তর') }}
                        <span v-if="rev.admin_replied_at" class="text-gray-400 font-normal ml-1">
                          · {{ new Date(rev.admin_replied_at).toLocaleDateString('bn-BD') }}
                        </span>
                      </p>
                      <p class="font-bengali text-sm text-gray-700">{{ rev.admin_reply }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add review form -->
            <div v-if="canReview" class="mt-6 bg-gray-50 rounded-2xl p-5">
              <h3 class="font-display font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="mi text-primary">rate_review</span>{{ tr('আপনার রিভিউ দিন') }}
              </h3>
              <div v-if="alreadyReviewed"
                   class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-yellow-700 text-sm font-bengali flex items-center gap-2">
                <span class="mi mi-sm">info</span>{{ tr('আপনি ইতিমধ্যে এই পণ্যটি রিভিউ দিয়েছেন।') }}
              </div>
              <template v-else>
                <div v-if="reviewError"
                     class="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm font-bengali flex items-center gap-2">
                  <span class="mi mi-sm">error</span>{{ reviewError }}
                </div>
                <div v-if="reviewSuccess"
                     class="mb-3 p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-bengali flex items-center gap-2">
                  <span class="mi mi-sm">check_circle</span>{{ tr('রিভিউ সফলভাবে জমা হয়েছে!') }}
                </div>
                <div class="mb-4">
                  <p class="font-bengali text-sm text-gray-600 mb-2 font-semibold">{{ tr('রেটিং দিন *') }}</p>
                  <div class="flex gap-1">
                    <button
                        v-for="i in 5"
                        :key="i"
                        @click="newRating = i"
                        @mouseenter="hoverRating = i"
                        @mouseleave="hoverRating = 0"
                        class="mi text-3xl transition-transform hover:scale-110"
                        :class="i <= (hoverRating || newRating) ? 'mi-filled text-gold' : 'text-gray-300'"
                    >star
                    </button>
                  </div>
                  <p class="font-bengali text-xs text-gray-400 mt-1">
                    {{ ['', 'খুব খারাপ', 'খারাপ', 'ঠিক আছে', 'ভালো', 'চমৎকার'][hoverRating || newRating] }}
                  </p>
                </div>
                <div class="mb-3">
                  <input v-model="newTitle" type="text" :placeholder="tr('রিভিউয়ের শিরোনাম (ঐচ্ছিক)')"
                         class="input-field w-full" maxlength="100"/>
                </div>
                <div class="mb-3">
                  <textarea
                      v-model="newComment"
                      rows="4"
                      :placeholder="tr('আপনার অভিজ্ঞতা বিস্তারিত লিখুন... (কমপক্ষে ৫ অক্ষর)')"
                      class="input-field resize-none w-full"
                      maxlength="2000"
                  ></textarea>
                  <p class="text-xs text-gray-400 text-right mt-1">{{ newComment.length }}/2000</p>
                </div>
                <!-- Image upload -->
                <div class="mb-4">
                  <p class="font-bengali text-sm text-gray-600 mb-2 font-semibold flex items-center gap-1">
                    <span class="mi mi-sm">photo_camera</span>{{ tr('ছবি যোগ করুন (ঐচ্ছিক, সর্বোচ্চ ৫টি)') }}
                  </p>
                  <div
                      class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-primary transition-colors"
                      :class="isDragging ? 'border-primary bg-primary/5' : ''"
                      @click="$refs.imageInput.click()"
                      @dragover.prevent="isDragging = true"
                      @dragleave="isDragging = false"
                      @drop.prevent="onImageDrop"
                  >
                    <span class="mi text-3xl text-gray-300 block mb-1">upload</span>
                    <p class="font-bengali text-sm text-gray-400">{{ tr('ক্লিক করুন বা ছবি টেনে আনুন') }}</p>
                    <p class="font-bengali text-xs text-gray-300 mt-1">{{ tr('JPEG, PNG, WebP — সর্বোচ্চ ২MB প্রতিটি') }}</p>
                  </div>
                  <input ref="imageInput" type="file" multiple accept="image/jpeg,image/png,image/jpg,image/webp"
                         class="hidden" @change="onImageSelect"/>
                  <div v-if="selectedImages.length" class="flex flex-wrap gap-3 mt-3">
                    <div v-for="(img, i) in selectedImages" :key="i"
                         class="relative w-20 h-20 rounded-xl overflow-hidden border-2 border-gray-100 group">
                      <img :src="img.preview" :alt="`Preview ${i+1}`" class="w-full h-full object-cover"/>
                      <button @click="removeImage(i)"
                              class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <span class="mi text-white">delete</span>
                      </button>
                      <span class="absolute bottom-0 left-0 right-0 bg-black/40 text-white text-xs text-center py-0.5">
                        {{ (img.file.size / 1024).toFixed(0) }}KB
                      </span>
                    </div>
                    <button
                        v-if="selectedImages.length < 5"
                        @click="$refs.imageInput.click()"
                        class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-200 hover:border-primary transition-colors flex flex-col items-center justify-center gap-1"
                    >
                      <span class="mi text-gray-300">add_photo_alternate</span>
                      <span class="text-xs font-bengali text-gray-300">{{ tr('যোগ করুন') }}</span>
                    </button>
                  </div>
                  <div v-if="imageErrors.length" class="mt-2 space-y-1">
                    <p v-for="err in imageErrors" :key="err"
                       class="text-xs text-red-500 font-bengali flex items-center gap-1">
                      <span class="mi mi-sm">warning</span>{{ err }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <button
                      @click="submitReview"
                      :disabled="!newRating || !newComment.trim() || submittingReview"
                      class="btn-primary disabled:opacity-60 flex items-center gap-2"
                  >
                    <span v-if="submittingReview" class="mi mi-sm animate-spin">refresh</span>
                    <span v-else class="mi mi-sm">send</span>
                    {{ submittingReview ? 'জমা হচ্ছে...' : 'রিভিউ জমা দিন' }}
                  </button>
                  <span class="font-bengali text-xs text-gray-400">{{ tr('* আবশ্যিক ঘর') }}</span>
                </div>
              </template>
            </div>

            <!-- Cannot review notice -->
            <div v-else class="mt-6 p-4 bg-gray-50 rounded-2xl text-center">
              <p class="font-bengali text-gray-500 text-sm flex items-center justify-center gap-2">
                <span class="mi mi-sm text-gray-400">lock</span>
                {{ tr('রিভিউ দিতে লগইন করুন এবং পণ্যটি কিনুন।') }}
              </p>
            </div>
          </div>
        </div>

        <!-- ── Delivery tab ── -->
        <div v-if="activeTab === 'ডেলিভারি তথ্য'" class="p-6 space-y-4">
          <div v-for="d in deliveryDetails" :key="d.title" class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
            <span class="mi text-2xl text-primary shrink-0">{{ d.icon }}</span>
            <div>
              <p class="font-display font-bold text-gray-800 mb-1">{{ d.title }}</p>
              <p class="font-bengali text-gray-600 text-sm">{{ d.desc }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════
           RELATED PRODUCTS
      ════════════════════════════════════════════════════════════════════ -->
      <section v-if="relatedProducts.length">
        <h2 class="section-title mb-5 flex items-center gap-2">
          <span class="mi text-primary">recommend</span>{{ tr('সংশ্লিষ্ট পণ্য') }}
        </h2>
        <div class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in relatedProducts" :key="p.id" :product="p"/>
        </div>
      </section>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════
         ZOOM MODAL
    ════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="zoom-modal">
        <div
            v-if="zoomOpen"
            ref="zoomModalEl"
            class="zoom-overlay"
            tabindex="-1"
            @keydown.esc="closeZoom"
            @keydown.left="prevImage"
            @keydown.right="nextImage"
        >
          <div class="zoom-backdrop" @click="closeZoom"></div>

          <!-- Toolbar -->
          <div class="zoom-toolbar">
            <div class="zoom-thumbs">
              <button
                  v-for="(img, i) in product?.images"
                  :key="img.id"
                  @click="activeImgIdx = i; resetZoomTransform()"
                  class="zoom-thumb-btn"
                  :class="activeImgIdx === i ? 'zoom-thumb-active' : ''"
              >
                <img :src="img.full_url_image" class="w-full h-full object-cover"/>
              </button>
            </div>
            <div class="zoom-toolbar-actions">
              <span class="zoom-counter font-bengali">
                {{ activeImgIdx + 1 }} / {{ product?.images?.length ?? 1 }}
              </span>
              <button @click="toggleFullscreen" class="zoom-icon-btn"
                      :title="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'">
                <span class="mi">{{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}</span>
              </button>
              <button @click="closeZoom" class="zoom-icon-btn" title="Close">
                <span class="mi">close</span>
              </button>
            </div>
          </div>

          <button v-if="(product?.images?.length ?? 0) > 1 && activeImgIdx > 0" @click="prevImage"
                  class="zoom-arrow zoom-arrow-left">
            <span class="mi">chevron_left</span>
          </button>
          <button v-if="(product?.images?.length ?? 0) > 1 && activeImgIdx < (product?.images?.length ?? 1) - 1"
                  @click="nextImage" class="zoom-arrow zoom-arrow-right">
            <span class="mi">chevron_right</span>
          </button>

          <div
              class="zoom-stage"
              @wheel.prevent="onZoomWheel"
              @mousedown="onDragStart"
              @mousemove="onDragMove"
              @mouseup="onDragEnd"
              @mouseleave="onDragEnd"
              @touchstart.passive="onTouchStart"
              @touchmove.prevent="onTouchMove"
              @touchend="onTouchEnd"
              @dblclick="onDoubleTap"
          >
            <img
                :src="activeImg"
                :alt="product?.name"
                class="zoom-image"
                :style="zoomImgStyle"
                draggable="false"
            />
          </div>

          <div class="zoom-controls">
            <button @click="zoomOut" class="zoom-ctrl-btn" :disabled="zoomScale <= 1"><span class="mi">remove</span>
            </button>
            <div class="zoom-scale-bar">
              <div class="zoom-scale-fill" :style="`width:${((zoomScale - 1) / 3) * 100}%`"></div>
            </div>
            <button @click="zoomIn" class="zoom-ctrl-btn" :disabled="zoomScale >= 4"><span class="mi">add</span>
            </button>
            <span class="zoom-pct">{{ Math.round(zoomScale * 100) }}%</span>
            <button @click="resetZoomTransform" class="zoom-reset-btn font-bengali"
                    :disabled="zoomScale === 1 && zoomPan.x === 0 && zoomPan.y === 0">{{ tr('রিসেট') }}
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
const { tr } = useLang()
const { shopName, helpline, helplineHref } = useShopName()
const { layout, productGridCols } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import {useCartStore} from '~/stores/cart'
import {normalizeProducts} from '../../utils/normalizeProduct.js'

definePageMeta({layout: 'default'})

const route = useRoute()
const cartStore = useCartStore()
const api = useApi()
const dataLayer = useDataLayer()

// ── State ────────────────────────────────────────────────────────────────
const product = ref(null)
const flashSaleItem = ref(null)   // product.flash_sale (the pivot row with nested flash_sale)
const loading = ref(true)
const error = ref(null)
const activeImgIdx = ref(0)
const selectedVariant = ref(null)
const qty = ref(1)
const addingToCart = ref(false)
const activeTab = ref('বিস্তারিত তথ্য')
const canReview = ref(false)

const skRelated = [
  {name: 80, price: 52}, {name: 75, price: 48},
  {name: 85, price: 58}, {name: 70, price: 44}, {name: 78, price: 50},
]

// ── Active image ─────────────────────────────────────────────────────────
const activeImg = computed(() =>
    product.value?.images?.[activeImgIdx.value]?.full_url_image ?? ''
)

// ── Flash sale helpers ───────────────────────────────────────────────────
// The API returns: data.flash_sale = { discount_percentage, special_price, ends_at }
// AND: data.product.flash_sale = { id, discount_percentage, special_price, quantity_limit, sold_count, flash_sale: { title, title_bn, ends_at, ... } }
// We primarily use data.product.flash_sale for the full detail.

const flashSaleExpired = computed(() => {
  // Use the nested flash_sale.ends_at for accuracy
  const endsAt = flashSaleItem.value?.flash_sale?.ends_at
      || flashSaleItem.value?.ends_at
  if (!endsAt) return true
  return new Date(endsAt) < new Date()
})

const flashSaleEndsAt = computed(() => {
  const endsAt = flashSaleItem.value?.flash_sale?.ends_at
      || flashSaleItem.value?.ends_at
  if (!endsAt) return ''
  return new Date(endsAt).toLocaleString('bn-BD', {
    day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit'
  })
})

// Flash sale remaining stock (quantity_limit - sold_count)
const flashSaleRemaining = computed(() => {
  if (!flashSaleItem.value) return null
  const {quantity_limit, sold_count} = flashSaleItem.value
  if (quantity_limit === undefined) return null
  return Math.max(0, quantity_limit - (sold_count ?? 0))
})

const flashSaleSoldPct = computed(() => {
  if (!flashSaleItem.value?.quantity_limit) return 0
  return Math.min(100, Math.round(((flashSaleItem.value.sold_count ?? 0) / flashSaleItem.value.quantity_limit) * 100))
})

// ── Variant helpers ──────────────────────────────────────────────────────
const hasVariantAttributes = computed(() => {
  if (!product.value?.variants?.length) return false
  return product.value.variants.some(v => {
    const av = v.attribute_values
    if (!av) return false
    if (Array.isArray(av)) return av.length > 0
    if (typeof av === 'object') return Object.keys(av).length > 0
    if (typeof av === 'string') return av.trim().length > 0
    return false
  })
})

const getVariantLabel = (v) => {
  const fmt = (x) => {
    if (x == null) return ''
    if (typeof x === 'string' || typeof x === 'number') return String(x)
    if (Array.isArray(x)) return x.map(fmt).filter(Boolean).join(' | ')
    if (typeof x === 'object') {
      const name = x.attribute ?? x.name ?? x.key
      const val  = x.value ?? x.val
      if (name !== undefined && val !== undefined) return `${name}: ${val}`
      return Object.entries(x).map(([k, v2]) => `${k}: ${v2}`).join(' | ')
    }
    return ''
  }
  let av = v?.attribute_values
  if (typeof av === 'string') { try { av = JSON.parse(av) } catch (e) { /* plain text */ } }
  return fmt(av) || v?.name || v?.sku || ''
}

const selectedVariantLabel = computed(() =>
    selectedVariant.value ? getVariantLabel(selectedVariant.value) : ''
)

const selectVariant = (v) => {
  if (!v.is_active || v.quantity === 0) return
  selectedVariant.value = v
  qty.value = Math.min(qty.value, v.quantity || 1)
}

// ── Stock / Price ────────────────────────────────────────────────────────
const currentStock = computed(() =>
    selectedVariant.value
        ? (selectedVariant.value.quantity ?? 0)
        : (product.value?.quantity ?? 0)
)

const inStock = computed(() =>
    !!product.value?.is_active && currentStock.value > 0
)

// The price to show as final (special_price when flash sale active)
const activePrice = computed(() => {
  if (flashSaleItem.value && !flashSaleExpired.value) {
    return Number(flashSaleItem.value.special_price)
  }
  if (selectedVariant.value) return Number(selectedVariant.value.price)
  return Number(product.value?.price ?? 0)
})

// The strikethrough original price
const originalPrice = computed(() => {
  if (flashSaleItem.value && !flashSaleExpired.value) {
    // Flash sale: cross out the normal selling price
    return Number(product.value?.price ?? 0)
  }
  // Regular: cross out compare_price
  if (selectedVariant.value) return Number(selectedVariant.value.compare_price ?? 0)
  return Number(product.value?.compare_price ?? 0)
})

const activeSavings = computed(() => {
  const orig = originalPrice.value
  const curr = activePrice.value
  if (!orig || orig <= curr) return 0
  return orig - curr
})

const activeDiscountPct = computed(() => {
  if (flashSaleItem.value && !flashSaleExpired.value) {
    return Number(flashSaleItem.value.discount_percentage)
  }
  if (product.value?.discount_percentage) return Number(product.value.discount_percentage)
  if (activeSavings.value > 0 && originalPrice.value > 0) {
    return Math.round((activeSavings.value / originalPrice.value) * 100)
  }
  return 0
})

// ── Load product ──────────────────────────────────────────────────────────
// Everything that happens once the product payload is in hand (server render, client hydration or refetch).
const applyProductData = (data) => {
  canReview.value = data.can_review || false
  product.value = data.product ?? data

  // Flash sale: use the richer product.flash_sale (nested pivot), falling back to top-level
  flashSaleItem.value = product.value.flash_sale ?? data.flash_sale ?? null

  // Set primary image as initial
  const primaryIdx = (product.value.images ?? []).findIndex(img => img.is_primary)
  activeImgIdx.value = primaryIdx >= 0 ? primaryIdx : 0

  // Related products
  if (data.related?.length) {
    relatedProducts.value = normalizeProducts(data.related)
  }

  // Select first active variant by default
  if (product.value.variants?.length) {
    selectedVariant.value =
        product.value.variants.find(v => v.is_active && v.quantity > 0)
        ?? product.value.variants.find(v => v.is_active)
        ?? product.value.variants[0]
  }

  // Reviews
  if (product.value.reviews?.length) {
    reviews.value = product.value.reviews
    computeRatingBars()
  }
}

const fireViewItem = () => {
  if (import.meta.client && product.value) {
    dataLayer.viewItem({ ...product.value, price: activePrice.value })
  }
}

// Client-side (re)load — used when the server render had no data, and to refresh
// logged-in-only fields (can_review) after hydration.
const loadProduct = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await api.get(`/products/${route.params.slug || route.params.id}`)
    applyProductData(data)
    fireViewItem()
  } catch (e) {
    error.value = e.message
    if (process.client && window.$toast) {
      window.$toast({type: 'error', title: e.message, duration: 2500})
    }
  } finally {
    loading.value = false
  }
}

// ── SEO: title / description / Open Graph / schema.org Product (rendered on the server) ──
const plainText = (html) => String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
const _siteUrl = useRuntimeConfig().public.siteUrl   // read in setup, not inside the head getter
useHead(computed(() => {
  const pr = product.value
  if (!pr) return {}
  const desc = plainText(pr.short_description || pr.description).slice(0, 160)
    || `${pr.name} কিনুন ${shopName.value}-এ সেরা দামে। ৳${pr.price}`
  const img = activeImg.value
  const inStock = pr.variants?.length ? pr.variants.some(v => v.is_active && v.quantity > 0) : Number(pr.quantity ?? 1) > 0
  return {
    title: `${pr.name} — ${shopName.value}`,
    meta: [
      { name: 'description', content: desc },
      { property: 'og:title', content: pr.name },
      { property: 'og:description', content: desc },
      { property: 'og:image', content: img },
      { property: 'og:type', content: 'product' },
      { name: 'twitter:card', content: 'summary_large_image' },
    ],
    link: [{ rel: 'canonical', href: `${_siteUrl}/product/${pr.slug || route.params.slug}` }],
    script: [{
      type: 'application/ld+json',
      children: JSON.stringify({
        '@context': 'https://schema.org', '@type': 'Product',
        name: pr.name, description: desc, image: img ? [img] : undefined,
        sku: pr.sku || String(pr.id),
        brand: pr.brand?.name ? { '@type': 'Brand', name: pr.brand.name } : undefined,
        offers: {
          '@type': 'Offer', priceCurrency: 'BDT', price: String(pr.price),
          availability: inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        },
      }),
    }],
  }
}))

// ── Wishlist ──────────────────────────────────────────────────────────────
const {isWishlisted, toggleWishlist} = useWishlist()
const wishListed = computed(() => isWishlisted(Number(product.value?.id)))
const toggleWish = async () => {
  if (!product.value) return
  const wasWishlisted = !!wishListed.value
  await toggleWishlist({id: product.value.id})
  if (!wasWishlisted) {
    dataLayer.addToWishlist({ ...product.value, price: activePrice.value })
  }
}

// ── Cart ──────────────────────────────────────────────────────────────────
const addToCart = async () => {
  if (!product.value || !inStock.value) return
  addingToCart.value = true
  const ok = await cartStore.add(product.value.id, qty.value, selectedVariant.value?.id ?? null)
  if (ok) {
    dataLayer.addToCart(
        { ...product.value, price: activePrice.value },
        qty.value,
        selectedVariant.value
    )
    if (process.client && window.$toast) {
      window.$toast({
        type: 'success',
        title: 'কার্টে যোগ হয়েছে!',
        message: product.value.name?.substring(0, 40),
        duration: 2500
      })
    }
  }
  addingToCart.value = false
}

// ── Reviews ────────────────────────────────────────────────────────────────
const reviews = ref([])
const loadingReviews = ref(false)
const newRating = ref(0)
const hoverRating = ref(0)
const newTitle = ref('')
const newComment = ref('')
const submittingReview = ref(false)
const reviewSuccess = ref(false)
const reviewError = ref(null)
const alreadyReviewed = ref(false)
const ratingBars = ref([0, 0, 0, 0, 0])

const computeRatingBars = () => {
  if (!reviews.value.length) {
    ratingBars.value = [0, 0, 0, 0, 0];
    return
  }
  const counts = [0, 0, 0, 0, 0]
  reviews.value.forEach(r => {
    if (r.rating >= 1 && r.rating <= 5) counts[5 - r.rating]++
  })
  ratingBars.value = counts.map(c => Math.round((c / reviews.value.length) * 100))
}

// ── Image upload ─────────────────────────────────────────────────────────
const imageInput = ref(null)
const selectedImages = ref([])
const imageErrors = ref([])
const isDragging = ref(false)
const MAX_IMAGES = 5
const MAX_SIZE_BYTES = 2 * 1024 * 1024

const validateAndAddImages = (files) => {
  imageErrors.value = []
  const remaining = MAX_IMAGES - selectedImages.value.length
  Array.from(files).slice(0, remaining).forEach(file => {
    if (file.size > MAX_SIZE_BYTES) {
      imageErrors.value.push(`${file.name} — ফাইলটি ২MB এর বেশি।`);
      return
    }
    if (!['image/jpeg', 'image/png', 'image/jpg', 'image/webp'].includes(file.type)) {
      imageErrors.value.push(`${file.name} — সমর্থিত ফরম্যাট নয়।`);
      return
    }
    if (selectedImages.value.some(s => s.file.name === file.name && s.file.size === file.size)) return
    selectedImages.value.push({file, preview: URL.createObjectURL(file)})
  })
  if (Array.from(files).length > remaining) imageErrors.value.push(`সর্বোচ্চ ${MAX_IMAGES}টি ছবি যোগ করা যাবে।`)
}
const onImageSelect = (e) => {
  validateAndAddImages(e.target.files);
  e.target.value = ''
}
const onImageDrop = (e) => {
  isDragging.value = false;
  validateAndAddImages(e.dataTransfer.files)
}
const removeImage = (i) => {
  URL.revokeObjectURL(selectedImages.value[i].preview);
  selectedImages.value.splice(i, 1)
}

const submitReview = async () => {
  reviewError.value = null;
  reviewSuccess.value = false;
  submittingReview.value = true
  try {
    const form = new FormData()
    form.append('product_id', product.value.id)
    form.append('rating', newRating.value)
    form.append('comment', newComment.value)
    if (newTitle.value.trim()) form.append('title', newTitle.value.trim())
    selectedImages.value.forEach(({file}) => form.append('images[]', file))
    const res = await api.post('/reviews', form)
    const newReview = res.data ?? res
    if (newReview?.id) {
      reviews.value = [newReview, ...reviews.value];
      computeRatingBars()
    }
    reviewSuccess.value = true;
    alreadyReviewed.value = true
    newRating.value = 0;
    newTitle.value = '';
    newComment.value = ''
    selectedImages.value.forEach(img => URL.revokeObjectURL(img.preview))
    selectedImages.value = [];
    imageErrors.value = []
  } catch (e) {
    const msg = e?.data?.message ?? e?.message ?? ''
    if (msg.includes('ইতিমধ্যে')) alreadyReviewed.value = true
    reviewError.value = msg || 'রিভিউ পাঠানো যায়নি।'
  } finally {
    submittingReview.value = false
  }
}

// ══════════════════════════════════════════════════════════════════════
// ZOOM MODAL
// ══════════════════════════════════════════════════════════════════════
const zoomOpen = ref(false)
const zoomModalEl = ref(null)
const zoomScale = ref(1)
const zoomPan = ref({x: 0, y: 0})
const isFullscreen = ref(false)
const isDraggingZoom = ref(false)
const dragOrigin = ref({x: 0, y: 0})
const panOrigin = ref({x: 0, y: 0})
const lastTouchDist = ref(0)

const zoomImgStyle = computed(() => ({
  transform: `scale(${zoomScale.value}) translate(${zoomPan.value.x / zoomScale.value}px, ${zoomPan.value.y / zoomScale.value}px)`,
  cursor: zoomScale.value > 1 ? (isDraggingZoom.value ? 'grabbing' : 'grab') : 'zoom-in',
  transition: isDraggingZoom.value ? 'none' : 'transform 0.15s ease',
}))

const openZoom = () => {
  zoomOpen.value = true;
  nextTick(() => zoomModalEl.value?.focus())
}
const closeZoom = async () => {
  if (document.fullscreenElement) await document.exitFullscreen().catch(() => {
  })
  zoomOpen.value = false;
  resetZoomTransform()
}
const resetZoomTransform = () => {
  zoomScale.value = 1;
  zoomPan.value = {x: 0, y: 0}
}
const clampPan = () => {
  const m = 300 * (zoomScale.value - 1)
  zoomPan.value.x = Math.max(-m, Math.min(m, zoomPan.value.x))
  zoomPan.value.y = Math.max(-m, Math.min(m, zoomPan.value.y))
}
const zoomIn = () => {
  zoomScale.value = Math.min(4, parseFloat((zoomScale.value + 0.5).toFixed(1)))
}
const zoomOut = () => {
  zoomScale.value = Math.max(1, parseFloat((zoomScale.value - 0.5).toFixed(1)))
  if (zoomScale.value === 1) zoomPan.value = {x: 0, y: 0}
}
const prevImage = () => {
  if (activeImgIdx.value > 0) {
    activeImgIdx.value--;
    resetZoomTransform()
  }
}
const nextImage = () => {
  if (product.value && activeImgIdx.value < product.value.images.length - 1) {
    activeImgIdx.value++;
    resetZoomTransform()
  }
}
const onZoomWheel = (e) => {
  const d = e.deltaY > 0 ? -0.15 : 0.15
  zoomScale.value = Math.min(4, Math.max(1, parseFloat((zoomScale.value + d).toFixed(2))))
  if (zoomScale.value === 1) zoomPan.value = {x: 0, y: 0}
}
const onDragStart = (e) => {
  if (zoomScale.value <= 1) return
  isDraggingZoom.value = true
  dragOrigin.value = {x: e.clientX, y: e.clientY}
  panOrigin.value = {...zoomPan.value}
}
const onDragMove = (e) => {
  if (!isDraggingZoom.value) return
  zoomPan.value = {
    x: panOrigin.value.x + (e.clientX - dragOrigin.value.x),
    y: panOrigin.value.y + (e.clientY - dragOrigin.value.y)
  }
  clampPan()
}
const onDragEnd = () => {
  isDraggingZoom.value = false
}
const onDoubleTap = () => {
  if (zoomScale.value > 1) resetZoomTransform(); else zoomScale.value = 2
}
const getTouchDist = (t) => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY)
const onTouchStart = (e) => {
  if (e.touches.length === 2) lastTouchDist.value = getTouchDist(e.touches)
  else if (e.touches.length === 1 && zoomScale.value > 1) {
    isDraggingZoom.value = true
    dragOrigin.value = {x: e.touches[0].clientX, y: e.touches[0].clientY}
    panOrigin.value = {...zoomPan.value}
  }
}
const onTouchMove = (e) => {
  if (e.touches.length === 2) {
    const dist = getTouchDist(e.touches)
    zoomScale.value = Math.min(4, Math.max(1, parseFloat((zoomScale.value * (dist / lastTouchDist.value)).toFixed(2))))
    lastTouchDist.value = dist
    if (zoomScale.value === 1) zoomPan.value = {x: 0, y: 0}
  } else if (e.touches.length === 1 && isDraggingZoom.value) {
    zoomPan.value = {
      x: panOrigin.value.x + (e.touches[0].clientX - dragOrigin.value.x),
      y: panOrigin.value.y + (e.touches[0].clientY - dragOrigin.value.y)
    }
    clampPan()
  }
}
const onTouchEnd = (e) => {
  if (e.touches.length < 2) isDraggingZoom.value = false
}
const toggleFullscreen = async () => {
  if (!document.fullscreenElement) {
    await zoomModalEl.value?.requestFullscreen().catch(() => {
    })
    isFullscreen.value = true
  } else {
    await document.exitFullscreen().catch(() => {
    })
    isFullscreen.value = false
  }
}
const onFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

watch(activeImgIdx, resetZoomTransform)
watch(zoomOpen, (v) => {
  if (process.client) document.body.style.overflow = v ? 'hidden' : ''
})

// ── Related products ──────────────────────────────────────────────────────
const relatedProducts = ref([])

// ── Static content ────────────────────────────────────────────────────────
const tabs = [
  {label: 'বিস্তারিত তথ্য', icon: 'info'},
  {label: 'স্পেসিফিকেশন', icon: 'list_alt'},
  {label: 'রিভিউ', icon: 'rate_review'},
  {label: 'ডেলিভারি তথ্য', icon: 'local_shipping'},
]
const deliveryInfo = [
  //{icon: 'local_shipping', title: 'ঢাকায় আজই ডেলিভারি', desc: 'সকাল ১১টার আগে অর্ডার করুন', badge: 'বিনামূল্যে'},
  {icon: 'inventory_2', title: 'সারাদেশে ২-৪ কার্যদিবস', desc: 'SteadFast, Pathao মাধ্যমে', badge: 'দ্রুত ডেলিভারি'},
  //{icon: 'assignment_return', title: '৭ দিনের রিটার্ন পলিসি', desc: 'পণ্যে সমস্যা থাকলে ফেরত দিন', badge: null},
  {icon: 'verified_user', title: 'ওয়ারেন্টি সহ', desc: 'বিক্রেতার ওয়ারেন্টি নীতি প্রযোজ্য', badge: null},
]
const deliveryDetails = [
  {icon: 'location_city', title: 'ঢাকা মেট্রো', desc: 'সকাল ১১টার আগে অর্ডার করলে একই দিনে ডেলিভারি পাবেন।'},
  {icon: 'map', title: 'সারা বাংলাদেশ', desc: 'বিভাগীয় শহরে ১-২ দিন, জেলা শহরে ২-৩ দিন, উপজেলায় ৩-৫ কার্যদিবস।'},
  {icon: 'payments', title: 'ডেলিভারি চার্জ', desc: '৳১৫০০ বা তার বেশি অর্ডারে বিনামূল্যে ডেলিভারি।'},
  {
    icon: 'cached',
    title: 'রিটার্ন পলিসি',
    desc: 'পণ্য পেয়ে ৭ দিনের মধ্যে সমস্যা জানান। ক্ষতিগ্রস্ত বা ভুল পণ্য হলে বিনামূল্যে রিটার্ন।'
  },
]

// ── Server-side data (SEO) ────────────────────────────────────────────────
// useAsyncData runs on the server and the result travels to the browser inside the page,
// so crawlers and first paint get the full product without waiting for JavaScript.
const { data: ssrProduct } = await useAsyncData(
  `product:${route.params.slug || route.params.id}`,
  () => api.get(`/products/${route.params.slug || route.params.id}`).catch(e => ({ __error: e.message || 'error' })),
)
const hadServerData = !!ssrProduct.value && !ssrProduct.value.__error
if (hadServerData) {
  applyProductData(ssrProduct.value)
  loading.value = false
} else if (ssrProduct.value?.__error) {
  error.value = ssrProduct.value.__error
  loading.value = false
}

// ── Lifecycle ─────────────────────────────────────────────────────────────
onMounted(() => {
  if (hadServerData) {
    fireViewItem()
    // refresh fields that depend on the logged-in user (e.g. can_review) without a loading flash
    if (useAuthStore().isLoggedIn) api.get(`/products/${route.params.slug || route.params.id}`).then(d => { canReview.value = d.can_review || false }).catch(() => {})
  } else {
    loadProduct()
  }
  if (process.client) document.addEventListener('fullscreenchange', onFullscreenChange)
})
onUnmounted(() => {
  selectedImages.value.forEach(img => URL.revokeObjectURL(img.preview))
  if (process.client) {
    document.removeEventListener('fullscreenchange', onFullscreenChange)
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════════════
   SKELETON
══════════════════════════════════════════════════════════════════════ */
@keyframes shimmer {
  0% {
    background-position: -700px 0;
  }
  100% {
    background-position: 700px 0;
  }
}

.sk {
  display: block;
  background: linear-gradient(90deg, #f0f0f0 25%, #e4e4e4 50%, #f0f0f0 75%);
  background-size: 700px 100%;
  animation: shimmer 1.6s infinite linear;
}

.sk-inner {
  display: block;
  background: linear-gradient(90deg, rgba(255, 255, 255, .20) 25%, rgba(255, 255, 255, .40) 50%, rgba(255, 255, 255, .20) 75%);
  background-size: 700px 100%;
  animation: shimmer 1.6s infinite linear;
}

.sk-pill {
  border-radius: 999px !important;
}

.sk-circle {
  border-radius: 50% !important;
}

.sk-rounded {
  border-radius: 8px;
}

.sk-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 24px;
}

.sk-chevron {
  width: 8px;
  height: 8px;
  border-radius: 2px;
  background: #e4e4e4;
}

.sk-main-grid {
  display: grid;
  grid-template-columns:1fr 1fr;
  gap: 32px;
  margin-bottom: 40px;
}

@media (max-width: 767px) {
  .sk-main-grid {
    grid-template-columns:1fr;
  }
}

.sk-image-col {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.sk-main-img {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 16px;
  position: relative;
  overflow: hidden;
}

.sk-abs-tl {
  position: absolute;
  top: 16px;
  left: 16px;
  display: flex;
  flex-direction: column;
}

.sk-abs-tr {
  position: absolute;
  top: 16px;
  right: 16px;
}

.sk-abs-br {
  position: absolute;
  bottom: 12px;
  right: 12px;
}

.sk-thumb-grid {
  display: grid;
  grid-template-columns:repeat(4, 1fr);
  gap: 12px;
}

.sk-thumb {
  aspect-ratio: 1/1;
  border-radius: 12px;
}

.sk-info-col {
  display: flex;
  flex-direction: column;
}

.sk-badge-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.sk-title-block {
  margin-bottom: 14px;
}

.sk-rating-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.sk-stars {
  display: flex;
  gap: 4px;
}

.sk-star {
  width: 18px;
  height: 18px;
  border-radius: 3px;
}

.sk-price-card {
  border-radius: 16px;
  padding: 18px;
  margin-bottom: 18px;
  flex-shrink: 0;
}

.sk-price-row {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 4px;
}

.sk-variants-block {
  margin-bottom: 14px;
}

.sk-variant-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.sk-cart-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.sk-delivery-rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sk-tabs-card {
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 32px;
  box-shadow: 0 1px 8px 0 rgba(0, 0, 0, .06);
  background: #fff;
}

.sk-tabs-header {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 0 16px;
  border-bottom: 1px solid #f3f4f6;
}

.sk-tab-label {
  margin: 14px 16px 14px 0;
  flex-shrink: 0;
}

.sk-tabs-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sk-line {
  height: 13px;
}

.sk-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
}

.sk-related-grid {
  display: grid;
  grid-template-columns:repeat(2, 1fr);
  gap: 14px;
}

@media (min-width: 640px) {
  .sk-related-grid {
    grid-template-columns:repeat(3, 1fr);
  }
}

@media (min-width: 768px) {
  .sk-related-grid {
    grid-template-columns:repeat(4, 1fr);
  }
}

@media (min-width: 1024px) {
  .sk-related-grid {
    grid-template-columns:repeat(5, 1fr);
  }
}

.sk-product-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sk-product-img {
  aspect-ratio: 1/1;
  width: 100%;
  border-radius: 12px;
}

.sk-product-line {
  height: 13px;
  margin: 0 auto;
}

/* ══════════════════════════════════════════════════════════════════════
   ZOOM MODAL
══════════════════════════════════════════════════════════════════════ */
.zoom-modal-enter-active, .zoom-modal-leave-active {
  transition: opacity 0.2s ease;
}

.zoom-modal-enter-from, .zoom-modal-leave-to {
  opacity: 0;
}

.zoom-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  outline: none;
}

.zoom-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.93);
}

.zoom-toolbar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), transparent);
  z-index: 10;
}

.zoom-thumbs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  max-width: calc(100% - 160px);
  padding-bottom: 2px;
  scrollbar-width: none;
}

.zoom-thumbs::-webkit-scrollbar {
  display: none;
}

.zoom-thumb-btn {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.15);
  opacity: 0.55;
  transition: opacity 0.15s, border-color 0.15s, transform 0.15s;
}

.zoom-thumb-btn:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.zoom-thumb-active {
  border-color: #fff !important;
  opacity: 1 !important;
}

.zoom-toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.zoom-counter {
  color: rgba(255, 255, 255, 0.7);
  font-size: 13px;
  min-width: 36px;
  text-align: center;
}

.zoom-icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  transition: background 0.15s, transform 0.15s;
}

.zoom-icon-btn:hover {
  background: rgba(255, 255, 255, 0.22);
  transform: scale(1.08);
}

.zoom-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  transition: background 0.15s, transform 0.15s;
}

.zoom-arrow:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-50%) scale(1.08);
}

.zoom-arrow-left {
  left: 14px;
}

.zoom-arrow-right {
  right: 14px;
}

.zoom-stage {
  position: relative;
  z-index: 5;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.zoom-image {
  max-width: 90vw;
  max-height: 80vh;
  object-fit: contain;
  user-select: none;
  -webkit-user-drag: none;
  will-change: transform;
}

.zoom-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px 20px 16px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
  z-index: 10;
}

.zoom-ctrl-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  transition: background 0.15s;
  flex-shrink: 0;
}

.zoom-ctrl-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
}

.zoom-ctrl-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.zoom-scale-bar {
  width: 100px;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 99px;
  overflow: hidden;
  flex-shrink: 0;
}

.zoom-scale-fill {
  height: 100%;
  background: #fff;
  border-radius: 99px;
  transition: width 0.15s;
}

.zoom-pct {
  color: rgba(255, 255, 255, 0.75);
  font-size: 13px;
  min-width: 40px;
  text-align: center;
  flex-shrink: 0;
}

.zoom-reset-btn {
  padding: 5px 14px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  transition: background 0.15s;
  flex-shrink: 0;
}

.zoom-reset-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
}

.zoom-reset-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .zoom-thumb-btn {
    width: 38px;
    height: 38px;
  }

  .zoom-arrow {
    width: 40px;
    height: 40px;
  }

  .zoom-arrow-left {
    left: 6px;
  }

  .zoom-arrow-right {
    right: 6px;
  }

  .zoom-image {
    max-width: 100vw;
    max-height: 75vh;
  }

  .zoom-scale-bar {
    width: 70px;
  }

  .cus-product-quantity button .mi, .cus-product-quantity span{
    padding: 0px;
  }
  .cus-product-quantity button{
    padding: 10px;
    display: flex;
    align-item: center;
  }
  .cus-details-add-to-cart, .cus-details-buy-now{
    padding: 10px;
  }
}
</style>
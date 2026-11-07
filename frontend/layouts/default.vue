<template>
  <div class="min-h-screen flex flex-col" :style="rootStyle">
    <ToastNotification ref="toastRef"/>
    <LoginPopup/>
    <AnnouncementBar/>

    <header class="shadow-sm sticky top-0 z-50" :style="{ backgroundColor: colors.navbar_color || '#ffffff' }">
      <div :class="[containerWidth, 'cus-mobile-header mx-auto px-4 py-3 flex items-center gap-3']">
        <div class="cus-mobile-header-left">
          <button @click="drawerOpen = true" class="cus-menu-icon lg:hidden p-2 hover:bg-gray-100 rounded-xl transition-colors">
            <span class="mi mi-sm">menu</span>
          </button>

          <NuxtLink to="/" class="flex items-center gap-2 shrink-0">
            <!-- Logo image when set, fallback to text avatar -->
            <img v-if="branding.shop_logo" :src="branding.shop_logo" :alt="branding.shop_name" class="cus-mobile-logo h-9 sm:h-10 w-auto max-w-[130px] sm:max-w-[200px] object-contain" />
            <template v-else>
              <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center shrink-0">
                <span class="text-white font-display font-bold text-sm">{{ (branding.shop_name_bn || branding.shop_name || 'বা').slice(0,2) }}</span>
              </div>
              <div class="hidden sm:block">
                <div class="font-display font-bold text-xl text-primary leading-none">{{ branding.shop_name_bn || branding.shop_name }}</div>
                <div class="text-xs text-gray-400 font-bengali leading-none">{{ branding.shop_tagline }}</div>
              </div>
            </template>
          </NuxtLink>
        </div>

        <!-- ── Search Bar ──────────────────────────────────────────────────── -->
        <div ref="searchWrapper" class="flex-1 max-w-2xl mx-auto md:relative"
             :class="mobileSearchOpen ? 'absolute left-0 right-0 top-full z-[60] bg-white px-4 py-3 shadow-lg border-t border-gray-100 md:static md:z-auto md:bg-transparent md:p-0 md:shadow-none md:border-0' : 'hidden md:block'">
          <div class="relative">
          <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">search</span>
          <input v-model="inputQuery" @keyup.enter="goSearch" @keyup.esc="search.closeSuggestions" @input="onInput" @focus="onFocus" type="text" :placeholder="tr('পণ্য খুঁজুন... (Search products)')" class="w-full border-2 border-gray-200 focus:border-primary rounded-xl pl-10 pr-28 py-2.5 text-sm font-bengali outline-none transition-colors" autocomplete="off"/>
          <button @click="goSearch" class="absolute right-2 top-1/2 -translate-y-1/2 bg-primary text-white px-3 py-1.5 rounded-lg text-sm font-display font-semibold hover:bg-green-800 transition-colors flex items-center gap-1">
            <span class="mi mi-sm">search</span>
            <span class="hidden sm:inline">{{ tr('খুঁজুন') }}</span>
          </button>

          <!-- Suggestions Dropdown -->
          <Transition name="dropdown">
            <div v-if="search.showSuggestions" class="absolute top-full mt-1.5 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-xl z-[300] overflow-hidden">

              <!-- Loading skeleton -->
              <div v-if="search.suggestLoading" class="p-3 space-y-1">
                <div class="flex items-center justify-between px-1 pt-1 pb-2">
                  <div class="sk sk-pill" style="width:60px;height:11px;"></div>
                  <div class="sk sk-pill" style="width:24px;height:11px;"></div>
                </div>
                <div v-for="i in 4" :key="i" class="flex items-center gap-3 px-1 py-1.5">
                  <div class="sk" style="width:36px;height:36px;border-radius:8px;flex-shrink:0;"></div>
                  <div class="flex-1 space-y-1.5">
                    <div class="sk sk-pill" :style="`width:${[72,60,80,65][i-1]}%;height:12px;`"></div>
                    <div class="sk sk-pill" :style="`width:${[38,45,32,42][i-1]}%;height:10px;`"></div>
                  </div>
                  <div class="sk sk-pill" style="width:44px;height:12px;"></div>
                </div>
                <div class="border-t border-gray-100 mt-1 pt-2 pb-1 flex justify-center">
                  <div class="sk sk-pill" style="width:140px;height:11px;"></div>
                </div>
              </div>

              <template v-else-if="search.hasResults">
                <!-- Categories -->
                <template v-if="search.suggestionCategories.length">
                  <div class="px-4 pt-3 pb-1 flex items-center justify-between">
                    <span class="text-xs font-bengali text-gray-400 font-medium flex items-center gap-1">
                      <span class="mi" style="font-size:13px">category</span> {{ tr('ক্যাটাগরি') }}
                    </span>
                    <span class="text-xs font-bengali text-gray-400">{{ search.suggestionCategories.length }} টি</span>
                  </div>
                  <div class="pb-1">
                    <button v-for="cat in search.suggestionCategories" :key="cat.id" @mousedown.prevent="selectCategory(cat)" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors text-left group">
                      <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                        <span class="mi text-primary" style="font-size:18px">{{ cat.icon || 'category' }}</span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bengali text-gray-800 group-hover:text-primary transition-colors" v-html="highlight(cat.name_bn || cat.name)"></p>
                        <p v-if="cat.name_bn && cat.name" class="text-xs text-gray-400 font-bengali">{{ cat.name }}</p>
                      </div>
                      <span class="mi mi-sm text-gray-300 group-hover:text-primary transition-colors">arrow_forward</span>
                    </button>
                  </div>
                </template>

                <!-- Sellers -->
                <template v-if="search.suggestionSellers.length">
                  <div :class="search.suggestionCategories.length ? 'border-t border-gray-100' : ''" class="px-4 pt-3 pb-1 flex items-center justify-between">
                    <span class="text-xs font-bengali text-gray-400 font-medium flex items-center gap-1">
                      <span class="mi" style="font-size:13px">storefront</span> {{ tr('সেলার') }}
                    </span>
                    <span class="text-xs font-bengali text-gray-400">{{ search.suggestionSellers.length }} টি</span>
                  </div>
                  <div class="pb-1">
                    <button v-for="seller in search.suggestionSellers" :key="seller.id" @mousedown.prevent="selectSeller(seller)" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors text-left group">
                      <img v-if="seller.logo" :src="seller.logo" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-gray-100"/>
                      <div v-else class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center shrink-0 text-primary font-bold text-sm">{{ seller.shop_name?.charAt(0) ?? 'S' }}</div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bengali text-gray-800 truncate group-hover:text-primary transition-colors" v-html="highlight(seller.shop_name)"></p>
                        <p class="text-xs text-gray-400 font-bengali flex items-center gap-1">
                          <span class="mi" style="font-size:11px;vertical-align:-1px">verified</span> {{ tr('অফিশিয়াল স্টোর') }}
                        </p>
                      </div>
                      <span class="mi mi-sm text-gray-300 group-hover:text-primary transition-colors">arrow_forward</span>
                    </button>
                  </div>
                </template>

                <!-- Products -->
                <template v-if="search.suggestionProducts.length">
                  <div :class="(search.suggestionCategories.length || search.suggestionSellers.length) ? 'border-t border-gray-100' : ''" class="px-4 pt-3 pb-1 flex items-center justify-between">
                    <span class="text-xs font-bengali text-gray-400 font-medium flex items-center gap-1">
                      <span class="mi" style="font-size:13px">inventory_2</span> {{ tr('পণ্য') }}
                    </span>
                    <span class="text-xs font-bengali text-gray-400">{{ search.suggestionProducts.length }} টি</span>
                  </div>
                  <div class="pb-1">
                    <button v-for="item in search.suggestionProducts" :key="item.id" @mousedown.prevent="selectProduct(item)" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors text-left group">
                      <img v-if="item.primary_image?.full_url_image || item.primary_image?.image_path" :src="item.primary_image?.full_url_image || item.primary_image?.image_path" class="w-10 h-10 rounded-lg object-cover shrink-0 border border-gray-100"/>
                      <div v-else class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                        <span class="mi text-gray-400" style="font-size:18px">inventory_2</span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bengali text-gray-800 truncate group-hover:text-primary transition-colors" v-html="highlight(item.name_bn || item.name)"></p>
                        <p v-if="item.category_name" class="text-xs font-bengali text-gray-400 truncate mt-0.5">
                          <span class="mi mi-sm" style="font-size:11px;vertical-align:-1px">category</span>
                          {{ item.category_name }}
                        </p>
                      </div>
                      <div class="shrink-0 text-right space-y-0.5">
                        <p class="text-sm font-bold text-primary">৳{{ Number(item.price).toLocaleString('bn-BD') }}</p>
                        <p v-if="item.compare_price && Number(item.compare_price) > Number(item.price)" class="text-xs text-gray-400 line-through">৳{{ Number(item.compare_price).toLocaleString('bn-BD') }}</p>
                      </div>
                    </button>
                  </div>
                </template>

                <!-- View all -->
                <div class="border-t border-gray-100 bg-gray-50/60">
                  <button @mousedown.prevent="goSearch" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bengali text-primary hover:text-green-800 font-semibold transition-colors">
                    <span class="mi mi-sm">search</span>
                    "<span class="mx-0.5">{{ inputQuery }}</span>{{ tr('" এর সব ফলাফল দেখুন') }}
                    <span class="mi mi-sm">arrow_forward</span>
                  </button>
                </div>
              </template>
              <!-- No results -->
              <div v-else class="flex flex-col items-center gap-1 px-4 py-6 text-center">
                <span class="mi text-3xl text-gray-200">search_off</span>
                <p class="text-sm font-bengali text-gray-500 mt-1">
                  "<span class="font-semibold">{{ inputQuery }}</span>{{ tr('" পাওয়া যায়নি') }}
                </p>
                <p class="text-xs font-bengali text-gray-400">{{ tr('অন্য কিছু লিখে চেষ্টা করুন') }}</p>
              </div>
            </div>
          </Transition>
          </div>
        </div>

        <div class="cus-mobile-header-right">
          <!-- Header Actions -->
          <div class="flex items-center gap-1">
            <NuxtLink to="/deals" class="cus-header-flash-sales hidden xl:flex items-center gap-1.5 bg-pink-50 border border-pink-200 px-3 py-1.5 rounded-sm">
              <div class="w-2 h-2 rounded-full bg-bkash animate-ping-slow"></div>
              <span class="text-xs font-bengali text-bkash font-semibold">Flash Sales</span>
            </NuxtLink>

            <!-- Language switch (Bangla / English) -->
            <button type="button" @click="toggleLang" :title="isEn ? 'বাংলায় দেখুন' : 'View in English'"
                    class="cus-lang-button mr-1 rounded-xl border border-gray-200 text-xs font-display font-bold text-gray-600 hover:border-primary hover:text-primary transition-colors">
              {{ isEn ? 'বাং' : 'EN' }}
            </button>

            <!-- Mobile search toggle (< 768px): shows the search bar as a panel under the header -->
            <button type="button" @click="toggleMobileSearch" class="cus-menu-icon md:hidden p-2 hover:bg-gray-100 rounded-xl transition-colors" :aria-label="tr('খুঁজুন')">
              <span class="mi text-gray-600">{{ mobileSearchOpen ? 'close' : 'search' }}</span>
            </button>

            <NuxtLink to="/wishlist" class="cus-menu-icon-fav-cart relative p-2 hover:bg-gray-100 rounded-xl transition-colors">
              <span class="mi text-gray-600">favorite_border</span>
              <ClientOnly>
                <span class="cus-mobile-header-quantity absolute -top-1 -right-1 w-4 h-4 bg-accent text-white text-xs rounded-full flex items-center justify-center font-bold">{{ wishListItems?.length || 0 }}</span>
                <template #fallback>
                  <span class="absolute -top-1 -right-1 w-4 h-4 sk-circle-sm"></span>
                </template>
              </ClientOnly>
            </NuxtLink>

            <NuxtLink to="/cart" class="cus-menu-icon-fav-cart relative p-2 hover:bg-gray-100 rounded-xl transition-colors">
              <span class="mi text-gray-600">shopping_cart</span>
              <ClientOnly>
                <span class="cus-mobile-header-quantity absolute -top-1 -right-1 w-4 h-4 bg-accent text-white text-xs rounded-full flex items-center justify-center font-bold">{{ cartItems?.length || 0 }}</span>
                <template #fallback>
                  <span class="absolute -top-1 -right-1 w-4 h-4 sk-circle-sm"></span>
                </template>
              </ClientOnly>
            </NuxtLink>

            <ClientOnly>
              <NuxtLink v-if="auth.isLoggedIn" to="/account" class="cus-header-user-icon hidden md:flex items-center gap-1.5 bg-primary text-white px-3 py-2 rounded-xl font-display font-semibold text-sm hover:bg-green-800 transition-colors">
                <span class="mi">person</span>
              </NuxtLink>
              <NuxtLink v-else to="/auth/login" class="cus-header-user-icon hidden md:flex items-center gap-1.5 bg-primary text-white px-3 py-2 rounded-xl font-display font-semibold text-sm hover:bg-green-800 transition-colors">
                <span class="mi mi-sm">person</span> {{ tr('লগইন') }}
              </NuxtLink>
              <template #fallback>
                <div class="hidden md:block sk sk-rounded" style="width:72px;height:36px;"></div>
              </template>
            </ClientOnly>
          </div>
        </div>
      </div>

      <!-- Category Nav -->
      <nav v-if="loadingCategories" class="cus-header-categories border-t border-gray-100 bg-gray-50/80">
        <div :class="[containerWidth, 'mx-auto px-4']">
          <div class="flex gap-2 overflow-x-auto py-2 scrollbar-hide">
            <div v-for="(w, i) in [72,88,64,96,76,80,68,90,74,82]" :key="i" class="sk sk-pill shrink-0" :style="`width:${w}px;height:32px;`"></div>
          </div>
        </div>
      </nav>
      <nav v-else class="cus-header-categories border-t border-gray-100 bg-gray-50/80">
        <div :class="[containerWidth, 'mx-auto px-4']">
          <div class="cus-header-categories flex gap-0.5 overflow-x-auto scrollbar-hide py-1">
            <NuxtLink v-for="cat in categories" :key="cat.slug" :to="`/category/${cat.slug}`" active-class="text-white bg-primary" class="whitespace-nowrap flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-bengali font-medium text-gray-600 hover:bg-primary hover:text-white transition-colors shrink-0">
              <span class="mi mi-sm">{{ cat.icon }}</span>
              {{ cat.name_bn || cat.name }}
            </NuxtLink>
          </div>
        </div>
      </nav>
    </header>

    <CategoryDrawer :isOpen="drawerOpen" @close="drawerOpen = false"/>
    <main class="flex-1">
      <slot/>
    </main>

    <!-- Footer -->
    <footer class="text-gray-300" :style="{ backgroundColor: colors.footer_color || '#1a1a2e' }">
      <div :class="[containerWidth, 'mx-auto px-4 py-12']">
        <div class="grid gap-8" :class="footerGridCols">
          <div>
            <div class="flex items-center gap-2 mb-4">
              <img v-if="branding.shop_logo" :src="branding.shop_logo" :alt="branding.shop_name" class="h-12 w-auto max-w-[220px] object-contain" />
              <template v-else>
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shrink-0">
                  <span class="text-white font-display font-bold text-lg">{{ (branding.shop_name_bn || branding.shop_name || 'বা').slice(0,2) }}</span>
                </div>
                <div>
                  <div class="font-display font-bold text-xl text-white leading-none">{{ branding.shop_name_bn || branding.shop_name }}</div>
                  <div class="text-xs text-gray-400 font-bengali leading-none">{{ branding.shop_tagline }}</div>
                </div>
              </template>
            </div>
            <p class="text-sm font-bengali text-gray-400 mb-4 leading-relaxed">{{ footerData.tagline }}</p>
            <div class="flex gap-2 flex-wrap">
              <div v-for="pay in payments" :key="pay" class="bg-gray-800 text-xs font-bold px-2.5 py-1.5 rounded-lg text-gray-300">{{ pay }}</div>
            </div>
          </div>
          <div>
            <h4 class="font-display font-bold text-white mb-4">{{ tr('কাস্টমার সার্ভিস') }}</h4>
            <ul class="space-y-2 text-sm font-bengali">
              <li>
                <NuxtLink to="/about" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">info</span>{{ tr('আমাদের সম্পর্কে') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/contact" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">call</span>{{ tr('যোগাযোগ করুন') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/support" class="hover:text-primary transition-colors flex items-center gap-1.5"><span
                    class="mi mi-sm">support_agent</span>{{ tr('লাইভ সাপোর্ট') }}
                </NuxtLink>
              </li>
              <!--
              <li>
                <NuxtLink to="/policy/return" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">assignment_return</span>{{ tr('রিটার্ন পলিসি') }}
                </NuxtLink>
              </li>
              -->
              <li>
                <NuxtLink to="/policy/terms" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">gavel</span>{{ tr('শর্তাবলী') }}
                </NuxtLink>
              </li>
              <li>
                <NuxtLink to="/policy/privacy" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">privacy_tip</span>{{ tr('গোপনীয়তা নীতি') }}
                </NuxtLink>
              </li>
              <!--
              <li>
                <NuxtLink to="/blog" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">article</span>{{ tr('ব্লগ') }}</NuxtLink>
              </li>
              -->
            </ul>
          </div>
          <div>
            <h4 class="font-display font-bold text-white mb-4">{{ tr('আমার অ্যাকাউন্ট') }}</h4>
            <ul class="space-y-2 text-sm font-bengali">
              <li>
                <NuxtLink to="/auth/login" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">login</span>{{ tr('লগইন / নিবন্ধন') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/order/track" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">local_shipping</span>{{ tr('অর্ডার ট্র্যাক') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/wishlist" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">favorite</span>{{ tr('উইশলিস্ট') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/account/orders" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">inventory_2</span>{{ tr('আমার অর্ডার') }}
                </NuxtLink>
              </li>
              <li>
                <NuxtLink to="/order/return" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">undo</span>{{ tr('রিটার্ন করুন') }}
                </NuxtLink>
              </li>
              <li>
                <NuxtLink to="/deals" class="hover:text-primary transition-colors flex items-center gap-1.5"><span class="mi mi-sm">local_offer</span>{{ tr('ডিলস ও অফার') }}</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/seller/register" class="hover:text-primary transition-colors flex items-center gap-1.5">
                  <span class="mi mi-sm">storefront</span>{{ tr('সেলার হন') }}
                </NuxtLink>
              </li>
            </ul>
          </div>
          <div>
            <h4 class="font-display font-bold text-white mb-4">{{ tr('যোগাযোগ') }}</h4>
            <ul class="space-y-3 text-sm font-bengali">
              <li v-if="footerData.address" class="flex items-start gap-2"><span class="mi mi-sm text-primary mt-0.5">location_on</span><span>{{ footerData.address }}</span></li>
              <li v-if="footerData.phone" class="flex items-center gap-2"><span class="mi mi-sm text-primary">call</span><span>হেল্পলাইন: {{ footerData.phone }}</span></li>
              <li v-if="footerData.email" class="flex items-center gap-2"><span class="mi mi-sm text-primary">mail</span><span>{{ footerData.email }}</span></li>
              <li v-for="h in (footerData.office_hours || []).slice(0, 2)" :key="h.day" class="flex items-center gap-2"><span class="mi mi-sm text-primary">schedule</span><span>{{ h.day }}<template v-if="h.time">: {{ h.time }}</template></span></li>
            </ul>
            <div class="flex gap-2 mt-4">
              <a v-for="s in socials" :key="s.icon" :href="s.href" target="_blank" rel="noopener" class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-xl flex items-center justify-center transition-colors">
                <span class="mi mi-sm text-gray-300">{{ s.icon }}</span>
              </a>
            </div>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-6 flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="text-xs text-gray-500 font-bengali">{{ footerData.copyright }}</p>
          <p class="text-xs text-gray-500">Made with <span class="text-accent">♥</span> in ADYA 🇧🇩</p>
        </div>
      </div>
    </footer>

    <!-- Floating WhatsApp button (Admin → Settings → Social → WhatsApp number) -->
    <a v-if="waLink" :href="waLink" target="_blank" rel="noopener" aria-label="WhatsApp"
       class="fixed right-4 bottom-20 md:bottom-6 z-40 w-12 h-12 rounded-full bg-green-500 hover:bg-green-600 text-white shadow-lg flex items-center justify-center transition-colors">
      <span class="mi">chat</span>
    </a>

    <!-- Mobile Bottom Nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
      <div class="grid grid-cols-5">
        <NuxtLink to="/" class="flex flex-col items-center gap-0.5 py-2" active-class="text-primary" exact-active-class="text-primary">
          <span class="mi">home</span>
          <span class="text-xs font-bengali">{{ tr('হোম') }}</span>
        </NuxtLink>
        <button @click="drawerOpen = true" class="flex flex-col items-center gap-0.5 py-2 text-gray-500">
          <span class="mi">category</span>
          <span class="text-xs font-bengali">{{ tr('ক্যাটাগরি') }}</span>
        </button>
        <NuxtLink to="/deals" class="flex flex-col items-center gap-0.5 py-1 relative -top-3">
          <div class="w-14 h-14 bg-accent rounded-full flex items-center justify-center shadow-btn">
            <span class="mi text-white mi-lg">local_offer</span>
          </div>
        </NuxtLink>
        <NuxtLink to="/cart" class="flex flex-col items-center gap-0.5 py-2 relative text-gray-500" active-class="text-primary">
          <span class="mi">shopping_cart</span>
          <span class="text-xs font-bengali">{{ tr('কার্ট') }}</span>
          <ClientOnly>
            <span class="absolute top-1.5 right-3 w-4 h-4 bg-accent text-white text-xs rounded-full flex items-center justify-center font-bold" v-text="cartItems?.length || 0"></span>
            <template #fallback>
              <span class="absolute top-1.5 right-3 w-4 h-4 sk-circle-sm"></span>
            </template>
          </ClientOnly>
        </NuxtLink>
        <NuxtLink to="/account" class="flex flex-col items-center gap-0.5 py-2 text-gray-500" active-class="text-primary">
          <span class="mi">person</span>
          <span class="text-xs font-bengali">{{ tr('আমার') }}</span>
        </NuxtLink>
      </div>
    </nav>
  </div>
</template>

<script setup>
const { tr, isEn, toggleLang } = useLang()
import {useAuth} from '../composables/useAuth.js'

const { colors, typography, branding, footer: footerData, paymentMethods, layout, footerGridCols } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

const rootStyle = computed(() => ({
  backgroundColor: colors.value.background_color || '#F8FAF9',
  color:           colors.value.text_color       || '#1a1a1a',
  fontFamily:      `'${typography.value.font_body || 'Hind Siliguri'}', sans-serif`,
  fontSize:        `${typography.value.font_size_base || 15}px`,
}))
import {useWishlistStore} from '../stores/wishlist.js'
import {useCartStore} from '../stores/cart.js'
import {useSearchStore} from '../stores/useSearchStore.js'
import {useLandingPageStore} from "../stores/LandingPage.js";
import {onMounted} from "vue";

const router = useRouter()
const route = useRoute()
const api = useApi()
const auth = useAuth()
const landingStore = useLandingPageStore()

const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const search = useSearchStore()

// Local input value — two-way binding with the input field only
// The store's query is only updated on submit/selection
const inputQuery = ref('')

const categories = computed(() => landingStore.categories)
const loadingCategories = computed(() => landingStore.loading)
const wishListItems = computed(() => wishlistStore.items)
const cartItems = computed(() => cartStore.items)
const drawerOpen = ref(false)
const searchWrapper = ref(null)
// Mobile (< 768px): header shows only a search icon; tapping it reveals the search bar under the header
const mobileSearchOpen = ref(false)
const toggleMobileSearch = () => {
  mobileSearchOpen.value = !mobileSearchOpen.value
  if (mobileSearchOpen.value) {
    nextTick(() => searchWrapper.value?.querySelector('input')?.focus())
  } else {
    search.closeSuggestions()
  }
}
watch(() => route.fullPath, () => { mobileSearchOpen.value = false })

// Keep input in sync when URL changes (e.g. browser back, popular chip click)
watch(() => route.query.q, (q) => {
  inputQuery.value = q || ''
}, {immediate: true})

// ── Search input handlers ──────────────────────────────────────────────────
const onInput = () => {
  search.fetchSuggestions(inputQuery.value, api.get.bind(api))
}

const onFocus = () => {
  if (inputQuery.value.trim().length >= 2 && search.hasResults) {
    search.showSuggestions = true
  }
}

const goSearch = () => {
  const q = inputQuery.value.trim()
  if (!q) return
  search.closeSuggestions()
  // Reset filters when a brand-new search is submitted from the header
  search.clearFilters()
  search.query = q
  mobileSearchOpen.value = false
  router.push({path: '/search', query: {q}})
}

const selectProduct = (item) => {
  search.closeSuggestions();
  router.push(`/product/${item.slug ?? item.id}`)
}
const selectCategory = (cat) => {
  search.closeSuggestions();
  router.push(`/category/${cat.slug}`)
}
const selectSeller = (seller) => {
  search.closeSuggestions();
  router.push(`/seller/${seller.slug ?? seller.id}`)
}

const highlight = (text) => {
  if (!inputQuery.value || !text) return text
  const esc = inputQuery.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  return text.replace(new RegExp(`(${esc})`, 'gi'), '<mark class="bg-yellow-100 text-gray-900 rounded px-0.5">$1</mark>')
}

// ── Click outside closes suggestions ──────────────────────────────────────
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (searchWrapper.value && !searchWrapper.value.contains(e.target)) {
      search.closeSuggestions()
    }
  })
})


const allPayments = [{key:'show_bkash',label:'bKash'},{key:'show_nagad',label:'Nagad'},{key:'show_rocket',label:'Rocket'},{key:'show_visa',label:'Visa'},{key:'show_mastercard',label:'Mastercard'},{key:'show_cod',label:'COD'},{key:'show_emi',label:'EMI'}]
const payments = computed(() => allPayments.filter(p => paymentMethods.value[p.key] !== false).map(p => p.label))
const socials = computed(() => [
  { icon: 'thumb_up', href: footerData.value.facebook }, { icon: 'camera_alt', href: footerData.value.instagram },
  { icon: 'play_circle', href: footerData.value.youtube }, { icon: 'chat', href: footerData.value.twitter },
].filter(s => s.href))
const waLink = computed(() => footerData.value.whatsapp ? 'https://wa.me/' + String(footerData.value.whatsapp).replace(/[^\d]/g, '') : '')
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.dropdown-enter-active, .dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

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

.sk-pill {
  border-radius: 999px !important;
}

.sk-rounded {
  border-radius: 8px;
}

.sk-circle-sm {
  display: block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: linear-gradient(90deg, #e0e0e0 25%, #d0d0d0 50%, #e0e0e0 75%);
  background-size: 700px 100%;
  animation: shimmer 1.6s infinite linear;
}




.cus-mobile-header-quantity{
  top: -0.1rem;
  right: -0.1rem;
}

.cus-lang-button {
    height: 30px;
    width: 30px;
    border-radius: 50%;
    padding: 3px;
}

.cus-header-categories{
  background: #ededed;
}

.cus-menu-icon-fav-cart{
    display: flex;
    justify-content: center;
    align-items: center;
}

.cus-header-user-icon{
  border-radius: 50%;
  height: 35px;
  width: 35px;
  padding: 3px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.cus-header-flash-sales{
  margin-right: 10px;
  border-radius: 50px;
  padding: 6px 8px;
}







@media screen and (max-width: 1024px){
  .cus-menu-icon {
    display: none;
  }
  .cus-header-categories a{
    font-size: 12px;
    line-height: 1.3em;
  }
  .cus-header-categories a .mi{
    font-size: 14px;
  }
}

@media screen and (max-width: 767px){ 
  .cus-menu-icon {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .cus-mobile-header{
    padding: 8px; 
    justify-content: space-between;
  }
  .cus-mobile-header-left {
    display: flex;
    gap: 6px;
  }
  .cus-menu-icon{
    padding: 5px;
  }
  .cus-mobile-header-right .mi{
    font-size: 20px;
  }
  .cus-mobile-logo{
    max-width: 100px;
  }
  .cus-header-user-icon{
    height: 28px;
    width: 28px;
  }
}


</style>
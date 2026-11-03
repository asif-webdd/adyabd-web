<template>
  <div class="min-h-screen bg-cream flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
      <span class="mi text-gray-200" style="font-size:120px">search_off</span>
      <h1 class="font-display font-extrabold text-6xl text-primary mt-4 mb-3">৪০৪</h1>
      <h2 class="font-display font-bold text-2xl text-gray-800 mb-3">{{ tr('পেজটি পাওয়া যায়নি!') }}</h2>
      <p class="font-bengali text-gray-500 mb-8 leading-relaxed">
        {{ tr('আপনি যে পেজটি খুঁজছেন সেটি হয়তো সরানো হয়েছে বা অনুপলব্ধ।') }}
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
        <NuxtLink to="/" class="btn-primary justify-center">
          <span class="mi mi-sm">home</span>{{ tr('হোমপেজে যান') }}
        </NuxtLink>
        <button @click="$router.back()" class="btn-outline justify-center">
          <span class="mi mi-sm">arrow_back</span>{{ tr('আগের পেজে যান') }}
        </button>
      </div>
      <div class="bg-white rounded-2xl shadow-card p-5">
        <p class="font-bengali font-semibold text-gray-700 mb-3 flex items-center justify-center gap-1">
          <span class="mi mi-sm text-primary">link</span>{{ tr('দ্রুত লিংক:') }}
        </p>
        <div class="flex flex-wrap gap-2 justify-center">
          <NuxtLink v-for="link in quickLinks" :key="link.href" :to="link.href"
                    class="flex items-center gap-1 text-sm font-bengali text-primary bg-primary/10 px-3 py-1.5 rounded-full hover:bg-primary/20 transition-colors">
            <span class="mi mi-sm">{{ link.icon }}</span>{{ link.label }}
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
definePageMeta({ layout: 'default' })
const { shopName } = useShopName()
useHead({ title: computed(() => `৪০৪ — পেজ পাওয়া যায়নি | ${shopName.value}`) })

const quickLinks = [
  { href:'/',                   label:'হোম',           icon:'home'           },
  { href:'/deals',              label:'অফার',           icon:'local_offer'    },
  { href:'/category/electronics', label:'ইলেকট্রনিক্স', icon:'smartphone'    },
  { href:'/category/fashion',   label:'ফ্যাশন',         icon:'checkroom'      },
  { href:'/account/orders',     label:'আমার অর্ডার',    icon:'inventory_2'    },
  { href:'/contact',            label:'সাহায্য',         icon:'support_agent'  },
]
</script>
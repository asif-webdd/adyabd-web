<template>
  <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-2xl p-8 text-white text-center mb-8">
      <h1 class="font-display font-extrabold text-3xl">{{ page.title }}</h1>
    </div>
    <!-- Content is sanitised server-side (SitePage::safeContent) -->
    <div class="bg-white rounded-2xl shadow-card p-6 md:p-8 cms-body font-bengali text-gray-700 leading-relaxed" v-html="page.content"></div>
  </div>
</template>

<script setup>
const props = defineProps({ page: { type: Object, required: true } })
useHead({ title: props.page.title })
</script>

<style>
.cms-body h1,.cms-body h2,.cms-body h3{font-weight:700;color:#1f2937;margin:1.25rem 0 .5rem}
.cms-body h2{font-size:1.25rem}.cms-body h3{font-size:1.1rem}
.cms-body p{margin:.5rem 0}
.cms-body ul{list-style:disc;padding-left:1.5rem;margin:.5rem 0}
.cms-body ol{list-style:decimal;padding-left:1.5rem;margin:.5rem 0}
.cms-body a{color:var(--color-primary,#006A4E);text-decoration:underline}
.cms-body img{max-width:100%;border-radius:.75rem}
</style>

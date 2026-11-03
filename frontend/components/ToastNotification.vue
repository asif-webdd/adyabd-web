<template>
  <ClientOnly>
    <Teleport to="body">
      <div class="fixed top-20 right-4 z-[999] space-y-2 pointer-events-none">
        <TransitionGroup name="toast">
          <div
              v-for="toast in toasts"
              :key="toast.id"
              class="pointer-events-auto flex items-start gap-3 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 max-w-xs"
              :class="
              toast.type === 'success' ? 'border-l-4 border-l-primary' :
              toast.type === 'error'   ? 'border-l-4 border-l-accent' :
                                         'border-l-4 border-l-blue-500'
            "
          >
            <span
                class="mi text-xl shrink-0"
                :class="
                toast.type === 'success' ? 'text-primary' :
                toast.type === 'error'   ? 'text-accent' :
                                           'text-blue-500'
              "
            >
              {{ toast.type === 'success' ? 'check_circle' : toast.type === 'error' ? 'error' : 'info' }}
            </span>

            <div class="flex-1 min-w-0">
              <p class="font-display font-bold text-gray-800 text-sm">{{ toast.title }}</p>
              <p v-if="toast.message" class="text-gray-500 text-xs mt-0.5">{{ toast.message }}</p>
            </div>

            <button
                @click="removeToast(toast.id)"
                class="text-gray-300 hover:text-gray-500 shrink-0"
            >
              <span class="mi mi-sm">close</span>
            </button>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </ClientOnly>
</template>

<script setup>
const toasts = ref([])

const addToast = (toast) => {
  const id = Date.now()
  toasts.value.push({ id, ...toast })
  setTimeout(() => removeToast(id), toast.duration || 3000)
}

const removeToast = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

if (process.client) window.$toast = addToast

defineExpose({ addToast })
</script>
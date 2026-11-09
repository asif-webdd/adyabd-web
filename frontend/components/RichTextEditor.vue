<template>
  <div class="rte border border-gray-200 rounded-xl overflow-hidden bg-white focus-within:border-primary">
    <div class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-gray-100 bg-gray-50">
      <button v-for="b in buttons" :key="b.title" type="button" :title="b.title"
        class="w-8 h-8 rounded-lg hover:bg-gray-200 flex items-center justify-center text-gray-700 text-sm font-bold"
        @mousedown.prevent="run(b)">
        <span v-if="b.icon" class="mi mi-sm">{{ b.icon }}</span>
        <span v-else>{{ b.text }}</span>
      </button>
    </div>
    <div ref="el" contenteditable="true" class="rte-body px-3 py-2 outline-none font-bengali text-sm text-gray-800 overflow-y-auto"
      :style="{ minHeight: minHeight + 'px', maxHeight: '420px' }"
      :data-placeholder="placeholder" @input="onInput" @blur="onInput" @paste="onPaste"></div>
  </div>
</template>

<script setup>
// Lightweight rich-text editor (no extra npm package). v-model = HTML string.
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  minHeight: { type: Number, default: 140 },
})
const emit = defineEmits(['update:modelValue'])
const el = ref(null)

const buttons = [
  { title: 'Bold', icon: 'format_bold', cmd: 'bold' },
  { title: 'Italic', icon: 'format_italic', cmd: 'italic' },
  { title: 'Underline', icon: 'format_underlined', cmd: 'underline' },
  { title: 'Strikethrough', icon: 'strikethrough_s', cmd: 'strikeThrough' },
  { title: 'Link', icon: 'link', cmd: 'link' },
  { title: 'Heading 2', text: 'H2', cmd: 'formatBlock', arg: 'h2' },
  { title: 'Heading 3', text: 'H3', cmd: 'formatBlock', arg: 'h3' },
  { title: 'Quote', icon: 'format_quote', cmd: 'formatBlock', arg: 'blockquote' },
  { title: 'Bullet list', icon: 'format_list_bulleted', cmd: 'insertUnorderedList' },
  { title: 'Numbered list', icon: 'format_list_numbered', cmd: 'insertOrderedList' },
  { title: 'Undo', icon: 'undo', cmd: 'undo' },
  { title: 'Redo', icon: 'redo', cmd: 'redo' },
]

const run = (b) => {
  el.value?.focus()
  if (b.cmd === 'link') {
    const url = window.prompt('Link URL (https://...)')
    if (url && /^(https?:\/\/|mailto:|tel:)/i.test(url.trim())) document.execCommand('createLink', false, url.trim())
  } else {
    document.execCommand(b.cmd, false, b.arg || null)
  }
  onInput()
}

const onInput = () => {
  const html = el.value?.innerHTML || ''
  emit('update:modelValue', el.value?.textContent.trim() || html.includes('<li') ? html : '')
}

// paste as plain text so no foreign styles/scripts get in
const onPaste = (e) => {
  e.preventDefault()
  const text = e.clipboardData?.getData('text/plain') || ''
  document.execCommand('insertText', false, text)
}

// plain-text values (old descriptions) -> keep line breaks when loading into the editor
const toHtml = (v) => (v && !/<[a-z][\s\S]*>/i.test(v)) ? v.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>') : (v || '')

onMounted(() => { if (el.value) el.value.innerHTML = toHtml(props.modelValue) })

// when the parent replaces the value (open edit form / reset after save), refresh the editor
watch(() => props.modelValue, (v) => {
  if (!el.value) return
  if ((v || '') !== (el.value.innerHTML || '') && document.activeElement !== el.value) el.value.innerHTML = toHtml(v)
})
</script>

<style scoped>
.rte-body:empty:before { content: attr(data-placeholder); color: #9ca3af; }
.rte-body :deep(h2) { font-size: 1.25rem; font-weight: 700; margin: .5rem 0; }
.rte-body :deep(h3) { font-size: 1.1rem; font-weight: 700; margin: .5rem 0; }
.rte-body :deep(ul) { list-style: disc; padding-left: 1.25rem; }
.rte-body :deep(ol) { list-style: decimal; padding-left: 1.25rem; }
.rte-body :deep(blockquote) { border-left: 3px solid #d1d5db; padding-left: .75rem; color: #6b7280; }
.rte-body :deep(a) { color: #2563eb; text-decoration: underline; }
</style>
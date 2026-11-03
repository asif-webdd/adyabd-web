import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync, statSync, writeFileSync, mkdtempSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { tmpdir } from 'node:os'
import { fileURLToPath } from 'node:url'
import { spawnSync } from 'node:child_process'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const walk = (dir) => readdirSync(dir).flatMap((f) => {
  const p = join(dir, f)
  return statSync(p).isDirectory() ? walk(p) : p.endsWith('.vue') ? [p] : []
})

test('every <script setup> block is valid JavaScript', () => {
  const tmp = mkdtempSync(join(tmpdir(), 'vuecheck-'))
  const failures = []
  for (const f of ['layouts', 'pages', 'components'].flatMap((d) => walk(join(root, d))).concat(join(root, 'app.vue'))) {
    const m = readFileSync(f, 'utf8').match(/<script setup[^>]*>([\s\S]*?)<\/script>/)
    if (!m) continue
    const out = join(tmp, 'x.mjs')
    writeFileSync(out, m[1])
    const r = spawnSync(process.execPath, ['--check', out], { encoding: 'utf8' })
    if (r.status !== 0) failures.push(`${f.replace(root, '')}: ${r.stderr.split('\n')[0]}`)
  }
  assert.deepEqual(failures, [])
})

test('template mustaches are balanced in every page', () => {
  const bad = []
  for (const f of ['layouts', 'pages', 'components'].flatMap((d) => walk(join(root, d)))) {
    const t = readFileSync(f, 'utf8').match(/<template>([\s\S]*)<\/template>/)
    if (t && (t[1].split('{{').length !== t[1].split('}}').length)) bad.push(f.replace(root, ''))
  }
  assert.deepEqual(bad, [])
})

test('no external stock-photo dependency remains in the UI', () => {
  const bad = ['layouts', 'pages', 'components'].flatMap((d) => walk(join(root, d))).filter((f) => /images\.unsplash\.com/.test(readFileSync(f, 'utf8')))
  assert.deepEqual(bad.map((f) => f.replace(root, '')), [])
})

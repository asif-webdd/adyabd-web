import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import en from '../locales/en.js'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const walk = (dir) => readdirSync(dir).flatMap((f) => {
  const p = join(dir, f)
  return statSync(p).isDirectory() ? walk(p) : p.endsWith('.vue') ? [p] : []
})
const vueFiles = ['layouts', 'pages', 'components'].flatMap((d) => walk(join(root, d)))

test('every English entry is a non-empty string without template syntax', () => {
  for (const [bn, e] of Object.entries(en)) {
    assert.equal(typeof e, 'string', bn)
    assert.ok(e.trim().length > 0, `empty translation for: ${bn}`)
    assert.ok(!e.includes('{{') && !e.includes('}}'), `template syntax in: ${bn}`)
  }
})

test('every tr(\'...\') used in a template has an English entry', () => {
  const missing = []
  for (const f of vueFiles) {
    const src = readFileSync(f, 'utf8')
    for (const m of src.matchAll(/\btr\('((?:[^'\\]|\\.)*)'\)/g)) {
      const key = m[1].replace(/\\'/g, "'").replace(/\\\\/g, '\\')
      if (!(key in en)) missing.push(`${f.replace(root, '')}: ${key}`)
    }
  }
  assert.deepEqual(missing, [])
})

test('files that call tr() also define it with useLang()', () => {
  const bad = vueFiles.filter((f) => {
    const src = readFileSync(f, 'utf8')
    return /\btr\('/.test(src) && !/const \{[^}]*\btr\b[^}]*\} = useLang\(\)/.test(src)
  })
  assert.deepEqual(bad.map((f) => f.replace(root, '')), [])
})

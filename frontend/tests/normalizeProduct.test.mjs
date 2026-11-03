import test from 'node:test'
import assert from 'node:assert/strict'
import { normalizeProduct } from '../utils/normalizeProduct.js'

test('discount is computed from compare_price', () => {
  const p = normalizeProduct({ id: 1, name: 'A', price: 80, compare_price: 100 })
  assert.equal(p.discount, 20)
  assert.equal(p.originalPrice, 100)
})

test('no discount when compare price is missing or lower', () => {
  assert.equal(normalizeProduct({ id: 1, price: 80 }).discount, 0)
  assert.equal(normalizeProduct({ id: 1, price: 80, compare_price: 50 }).discount, 0)
})

test('rating and review count fall back to the cached columns', () => {
  const p = normalizeProduct({ id: 1, price: 10, rating: '4.50', total_reviews: 7 })
  assert.equal(p.rating, 4.5)
  assert.equal(p.reviews, 7)
  const q = normalizeProduct({ id: 1, price: 10, reviews_avg_rating: 3, reviews_count: 2, rating: 5 })
  assert.equal(q.rating, 3, 'the live average wins when present')
  assert.equal(q.reviews, 2)
})

test('missing fields never produce NaN or undefined crashes', () => {
  const p = normalizeProduct({ id: 9 })
  assert.equal(p.price, 0)
  assert.equal(p.quantity, 0)
  assert.deepEqual(p.tags, [])
  assert.equal(p.image, '')
})

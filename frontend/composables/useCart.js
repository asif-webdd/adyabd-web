export const useCart = () => {
  const cart = useState('cart', () => [])

  const addToCart = (product, qty = 1, options = {}) => {
    const existingIndex = cart.value.findIndex(
      item => item.id === product.id &&
      JSON.stringify(item.options) === JSON.stringify(options)
    )

    if (existingIndex >= 0) {
      cart.value[existingIndex].qty += qty
    } else {
      cart.value.push({ ...product, qty, options, addedAt: Date.now() })
    }

    // Show toast notification
    if (process.client && window.$toast) {
      window.$toast({
        type: 'success',
        title: 'কার্টে যোগ হয়েছে!',
        message: `${product.name?.substring(0, 40)}...`,
        duration: 2500
      })
    }
  }

  const removeFromCart = (productId, options = {}) => {
    cart.value = cart.value.filter(
      item => !(item.id === productId && JSON.stringify(item.options) === JSON.stringify(options))
    )
  }

  const updateQty = (productId, qty, options = {}) => {
    const item = cart.value.find(
      item => item.id === productId && JSON.stringify(item.options) === JSON.stringify(options)
    )
    if (item) {
      if (qty <= 0) removeFromCart(productId, options)
      else item.qty = qty
    }
  }

  const clearCart = () => { cart.value = [] }

  const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.qty, 0))

  const cartSubtotal = computed(() =>
    cart.value.reduce((sum, item) => sum + (item.originalPrice || item.price) * item.qty, 0)
  )

  const cartTotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.qty, 0)
  )

  const cartSavings = computed(() => cartSubtotal.value - cartTotal.value)

  const isInCart = (productId) => cart.value.some(item => item.id === productId)

  return {
    cart: readonly(cart),
    addToCart,
    removeFromCart,
    updateQty,
    clearCart,
    cartCount,
    cartSubtotal,
    cartTotal,
    cartSavings,
    isInCart,
  }
}

// Delegates to useWishlistStore (which calls the real API).
// The old version used useState with no persistence and no API calls.
import { useWishlistStore } from './../stores/wishlist'

export const useWishlist = () => {
  const store = useWishlistStore()

  const toggleWishlist = async (product) => {
    const wasAdded = await store.toggle(product.id)
    if (process.client && window.$toast) {
      window.$toast({
        type: 'success',
        title: wasAdded ? 'is added to wishlist!' : 'is removed from wishlist',
        duration: 2000,
      })
    }
    return wasAdded
  }

  const isWishlisted = (productId) => store.isWishlisted(productId)

  return {
    useWishlistStore,
    wishlist:          computed(() => store.items),
    wishlistCount:     computed(() => store.count),
    addToWishlist:     (product) => store.toggle(product.id),
    removeFromWishlist:(productId) => store.remove(productId),
    toggleWishlist,
    isWishlisted,
  }
}

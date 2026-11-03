export const useCurrency = () => {
  const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']

  // Convert English digits to Bengali
  const toBnDigits = (num) =>
    String(num).replace(/[0-9]/g, d => bnDigits[d])

  // Format price with commas and ৳ symbol
  const formatPrice = (amount, useBnDigits = true) => {
    const formatted = Number(amount).toLocaleString('en-IN')
    return useBnDigits ? `৳${toBnDigits(formatted)}` : `৳${formatted}`
  }

  // Format price without symbol
  const formatNumber = (num, useBnDigits = true) => {
    const formatted = Number(num).toLocaleString('en-IN')
    return useBnDigits ? toBnDigits(formatted) : formatted
  }

  // Calculate discount percentage
  const discountPercent = (original, sale) =>
    Math.round(((original - sale) / original) * 100)

  // Calculate savings amount
  const savings = (original, sale) => original - sale

  // Check if price is valid
  const isValidPrice = (price) => !isNaN(price) && price >= 0

  return {
    toBnDigits,
    formatPrice,
    formatNumber,
    discountPercent,
    savings,
    isValidPrice,
  }
}

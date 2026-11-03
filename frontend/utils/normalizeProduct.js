/**
 * Normalizes a raw API product response into a consistent shape.
 * @param {Object} product - Raw product object from API
 * @returns {Object} Normalized product
 */
export function normalizeProduct(product) {
    const image = product.primary_image?.full_url_image || product.thumbnail || product.image || product.images?.[0]?.full_url_image || product.primary_image?.image_path || ''
    const originalPrice = Number(product.compare_price ?? product.original_price ?? product.price ?? 0)

    const price = Number(product.price ?? 0)

    const discount = originalPrice > price ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0

    return {
        // Identity
        id: product.id,
        slug: product.slug,
        sku: product.sku,

        // Names
        name: product.name,
        nameBn: product.name_bn,

        // Seller
        seller: product.seller?.shop_name ?? '',
        sellerBn: product.seller?.shop_name_bn ?? '',
        sellerId: product.seller?.id ?? product.seller_id,

        // Category & Brand
        categoryId: product.category_id,
        brandId: product.brand_id,

        // Pricing
        price,
        originalPrice,
        discount,

        // Ratings & Reviews
        //rating: Number(product.reviews_avg_rating ?? product.rating ?? 0),
        rating: Number(product.reviews_avg_rating ?? product.rating ?? 0),
        //reviews: Number(product.reviews_count ?? product.total_reviews ?? 0),
        reviews: Number(product.reviews_count ?? product.total_reviews ?? 0),

        // Stock
        quantity: Number(product.quantity ?? 0),
        lowStockThreshold: product.low_stock_threshold ?? 0,

        // Stats
        totalSales: Number(product.total_sales ?? 0),
        totalViews: Number(product.total_views ?? 0),

        // Descriptions
        shortDescription: product.short_description ?? '',
        description: product.description ?? '',
        descriptionBn: product.description_bn ?? '',

        // Media
        image,
        full_url_image: image,
        videoUrl: product.video_url ?? null,

        // Tags
        tags: product.tags ?? [],

        // Flags
        hasVariants: !!product.has_variants,
        isFeatured: !!product.is_featured,
        isNew: !!product.is_new,
        isTrending: !!product.is_trending,
        freeShipping: !!product.free_shipping,
        isActive: !!product.is_active,

        // Shipping
        weight: product.weight ?? null,
        unit: product.unit ?? 'piece',
        delivery: '২-৩ দিনে',

        // Timestamps
        createdAt: product.created_at,
        updatedAt: product.updated_at,
    }
}

/**
 * Normalizes an array of products.
 * @param {Array} products
 * @returns {Array}
 */
export function normalizeProducts(products = []) {
    return products.map(normalizeProduct)
}
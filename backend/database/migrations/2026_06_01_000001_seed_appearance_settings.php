<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Single consolidated migration for ALL appearance settings.
 * Replaces the earlier partial migration (2026_06_01_000001_seed_appearance_settings.php).
 * Safe to run multiple times — uses updateOrCreate on key.
 *
 * Groups:
 *   appearance.branding      — colours, logo, shop name
 *   appearance.typography    — fonts, font-size, border-radius
 *   appearance.layout        — container width, navbar style, card style, product grid
 *   appearance.hero          — hero banner visibility, style, height, promo tiles
 *   appearance.sections      — every homepage section toggle
 *   appearance.labels        — section headings, featured tab labels
 *   appearance.announcement  — up to 4 rotating messages
 *   appearance.trust         — 4 trust badge title+desc pairs
 *   appearance.local_brands  — CTA banner copy & link
 *   appearance.app_download  — app banner copy & store URLs
 *   appearance.payments      — payment strip heading + per-method toggles
 *   appearance.footer        — copyright, tagline, address, socials
 *   appearance.popup         — popup delay & dismiss-forever flag
 *   appearance.grids         — per-section desktop/mobile column counts
 *   appearance.custom_code   — custom CSS / head script / body script
 */
return new class extends Migration
{
    public function up(): void
    {
        $t = 'text';
        $b = 'boolean';
        $n = 'number';

        $settings = [

            // ════════════════════════════════════════════════════════════════
            // BRANDING
            // ════════════════════════════════════════════════════════════════
            ['key' => 'primary_color',     'value' => '#006A4E', 'type' => $t, 'label' => 'Primary Colour',          'is_public' => true],
            ['key' => 'accent_color',      'value' => '#E63946', 'type' => $t, 'label' => 'Accent / CTA Colour',     'is_public' => true],
            ['key' => 'background_color',  'value' => '#F8FAF9', 'type' => $t, 'label' => 'Page Background',         'is_public' => true],
            ['key' => 'navbar_color',      'value' => '#ffffff', 'type' => $t, 'label' => 'Navbar Background',       'is_public' => true],
            ['key' => 'footer_color',      'value' => '#1a1a2e', 'type' => $t, 'label' => 'Footer Background',       'is_public' => true],
            ['key' => 'text_color',        'value' => '#1a1a1a', 'type' => $t, 'label' => 'Body Text Colour',        'is_public' => true],
            ['key' => 'shop_logo',         'value' => '',        'type' => $t, 'label' => 'Store Logo (path)',       'is_public' => true],
            ['key' => 'shop_favicon',      'value' => '',        'type' => $t, 'label' => 'Favicon (path)',          'is_public' => true],
            ['key' => 'shop_name',         'value' => 'বাজার',   'type' => $t, 'label' => 'Store Name',              'is_public' => true],
            ['key' => 'shop_tagline',      'value' => 'BAZAR.BD','type' => $t, 'label' => 'Store Tagline',           'is_public' => true],
            ['key' => 'shop_name_bn',      'value' => 'বাজার',   'type' => $t, 'label' => 'Store Name (Bengali)',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // TYPOGRAPHY
            // ════════════════════════════════════════════════════════════════
            ['key' => 'font_body',         'value' => 'Hind Siliguri', 'type' => $t, 'label' => 'Body / Bengali Font',    'is_public' => true],
            ['key' => 'font_display',      'value' => 'Inter',         'type' => $t, 'label' => 'Display / UI Font',      'is_public' => true],
            ['key' => 'font_size_base',    'value' => '15',            'type' => $n, 'label' => 'Base Font Size (px)',     'is_public' => true],
            ['key' => 'border_radius',     'value' => '12',            'type' => $n, 'label' => 'Border Radius (px)',      'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // LAYOUT
            // ════════════════════════════════════════════════════════════════
            ['key' => 'container_width',   'value' => 'max-w-7xl', 'type' => $t, 'label' => 'Container Max-Width',   'is_public' => true],
            ['key' => 'navbar_style',      'value' => 'default',   'type' => $t, 'label' => 'Navbar Style',          'is_public' => true],
            ['key' => 'homepage_layout',   'value' => 'grid',      'type' => $t, 'label' => 'Homepage Product Layout','is_public' => true],
            ['key' => 'product_card_style','value' => 'default',   'type' => $t, 'label' => 'Product Card Style',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // HERO BANNER
            // ════════════════════════════════════════════════════════════════
            ['key' => 'show_hero',         'value' => '1',       'type' => $b, 'label' => 'Show Hero Banner',          'is_public' => true],
            ['key' => 'hero_style',        'value' => 'slider',  'type' => $t, 'label' => 'Hero Style',                'is_public' => true],
            ['key' => 'hero_height',       'value' => '420',     'type' => $n, 'label' => 'Hero Height (px)',           'is_public' => true],
            ['key' => 'show_promo_top',    'value' => '1',       'type' => $b, 'label' => 'Show Promo Top Tiles',       'is_public' => true],
            ['key' => 'show_promo_bottom', 'value' => '1',       'type' => $b, 'label' => 'Show Promo Bottom Strip',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // HOMEPAGE SECTION VISIBILITY
            // ════════════════════════════════════════════════════════════════
            ['key' => 'show_announcement_bar',    'value' => '1', 'type' => $b, 'label' => 'Show Announcement Bar',          'is_public' => true],
            ['key' => 'show_trust_badges',         'value' => '1', 'type' => $b, 'label' => 'Show Trust Badges',               'is_public' => true],
            ['key' => 'show_categories',           'value' => '1', 'type' => $b, 'label' => 'Show Category Icons Row',         'is_public' => true],
            ['key' => 'show_flash_sale',           'value' => '1', 'type' => $b, 'label' => 'Show Flash Sale',                 'is_public' => true],
            ['key' => 'show_featured',             'value' => '1', 'type' => $b, 'label' => 'Show Featured Products',          'is_public' => true],
            ['key' => 'show_sidebar_banners',      'value' => '1', 'type' => $b, 'label' => 'Show Special Offer Banners',      'is_public' => true],
            ['key' => 'show_local_brands_banner',  'value' => '1', 'type' => $b, 'label' => 'Show Local Brands CTA Banner',    'is_public' => true],
            ['key' => 'show_new_arrivals',         'value' => '1', 'type' => $b, 'label' => 'Show New Arrivals',               'is_public' => true],
            ['key' => 'show_trending',             'value' => '1', 'type' => $b, 'label' => 'Show Trending Products',          'is_public' => true],
            ['key' => 'show_payment_methods',      'value' => '1', 'type' => $b, 'label' => 'Show Payment Methods Strip',      'is_public' => true],
            ['key' => 'show_app_download',         'value' => '1', 'type' => $b, 'label' => 'Show App Download Banner',        'is_public' => true],
            ['key' => 'show_popup_banner',         'value' => '1', 'type' => $b, 'label' => 'Show Popup Banner on Landing',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // SECTION LABELS & FEATURED TABS
            // ════════════════════════════════════════════════════════════════
            ['key' => 'label_featured',       'value' => 'জনপ্রিয় পণ্যসমূহ', 'type' => $t, 'label' => 'Featured Section Heading',   'is_public' => true],
            ['key' => 'label_new_arrivals',   'value' => 'নতুন পণ্য',          'type' => $t, 'label' => 'New Arrivals Heading',        'is_public' => true],
            ['key' => 'label_trending',       'value' => 'Trending',            'type' => $t, 'label' => 'Trending Heading',            'is_public' => true],
            ['key' => 'label_flash_sale',     'value' => 'ফ্ল্যাশ সেল',        'type' => $t, 'label' => 'Flash Sale Heading',          'is_public' => true],
            ['key' => 'label_special_offers', 'value' => 'বিশেষ অফার',          'type' => $t, 'label' => 'Special Offers Heading',      'is_public' => true],
            ['key' => 'featured_tab_1',       'value' => 'সব পণ্য',            'type' => $t, 'label' => 'Featured Tab 1',              'is_public' => true],
            ['key' => 'featured_tab_2',       'value' => 'ইলেকট্রনিক্স',       'type' => $t, 'label' => 'Featured Tab 2',              'is_public' => true],
            ['key' => 'featured_tab_3',       'value' => 'ফ্যাশন',             'type' => $t, 'label' => 'Featured Tab 3',              'is_public' => true],
            ['key' => 'featured_tab_4',       'value' => 'গৃহস্থালি',           'type' => $t, 'label' => 'Featured Tab 4',              'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // ANNOUNCEMENT BAR (up to 4 messages)
            // ════════════════════════════════════════════════════════════════
            ['key' => 'announcement_1', 'value' => 'ঈদ স্পেশাল অফার! সকল পোশাকে ৪০% ছাড়',              'type' => $t, 'label' => 'Announcement Message 1', 'is_public' => true],
            ['key' => 'announcement_2', 'value' => 'আজই অর্ডার করুন — ঢাকায় বিনামূল্যে ডেলিভারি!',     'type' => $t, 'label' => 'Announcement Message 2', 'is_public' => true],
            ['key' => 'announcement_3', 'value' => 'bKash পেমেন্টে ৫% অতিরিক্ত ছাড় পান',               'type' => $t, 'label' => 'Announcement Message 3', 'is_public' => true],
            ['key' => 'announcement_4', 'value' => 'নতুন ব্যবহারকারীরা NEWUSER কোডে ৳১৫০ ছাড়!',        'type' => $t, 'label' => 'Announcement Message 4', 'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // TRUST BADGES (4 badges × title + desc)
            // ════════════════════════════════════════════════════════════════
            ['key' => 'trust_badge_1_title', 'value' => 'নিরাপদ পেমেন্ট',    'type' => $t, 'label' => 'Trust Badge 1 Title', 'is_public' => true],
            ['key' => 'trust_badge_1_desc',  'value' => 'SSL এনক্রিপ্টেড',   'type' => $t, 'label' => 'Trust Badge 1 Desc',  'is_public' => true],
            ['key' => 'trust_badge_2_title', 'value' => 'দ্রুত ডেলিভারি',    'type' => $t, 'label' => 'Trust Badge 2 Title', 'is_public' => true],
            ['key' => 'trust_badge_2_desc',  'value' => '৬৪ জেলায় ডেলিভারি','type' => $t, 'label' => 'Trust Badge 2 Desc',  'is_public' => true],
            ['key' => 'trust_badge_3_title', 'value' => 'সহজ রিটার্ন',        'type' => $t, 'label' => 'Trust Badge 3 Title', 'is_public' => true],
            ['key' => 'trust_badge_3_desc',  'value' => '৭ দিনের মধ্যে ফেরত','type' => $t, 'label' => 'Trust Badge 3 Desc',  'is_public' => true],
            ['key' => 'trust_badge_4_title', 'value' => '১০০% আসল পণ্য',     'type' => $t, 'label' => 'Trust Badge 4 Title', 'is_public' => true],
            ['key' => 'trust_badge_4_desc',  'value' => 'গ্যারান্টিসহ',       'type' => $t, 'label' => 'Trust Badge 4 Desc',  'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // LOCAL BRANDS CTA BANNER
            // ════════════════════════════════════════════════════════════════
            ['key' => 'local_brands_heading',      'value' => 'দেশীয় পণ্য কিনুন',                         'type' => $t, 'label' => 'Local Brands Heading',      'is_public' => true],
            ['key' => 'local_brands_subtext',      'value' => 'বাংলাদেশি উদ্যোক্তাদের পণ্য সরাসরি কিনুন।','type' => $t, 'label' => 'Local Brands Sub-text',     'is_public' => true],
            ['key' => 'local_brands_button_label', 'value' => 'দেশীয় পণ্য দেখুন',                         'type' => $t, 'label' => 'Local Brands Button Label', 'is_public' => true],
            ['key' => 'local_brands_button_link',  'value' => '/category/fashion',                          'type' => $t, 'label' => 'Local Brands Button Link',  'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // APP DOWNLOAD BANNER
            // ════════════════════════════════════════════════════════════════
            ['key' => 'app_banner_heading', 'value' => 'বাজার অ্যাপ ডাউনলোড করুন',    'type' => $t, 'label' => 'App Banner Heading',    'is_public' => true],
            ['key' => 'app_banner_subtext', 'value' => 'অ্যাপ থেকে কিনলে অতিরিক্ত ৫% ছাড়।', 'type' => $t, 'label' => 'App Banner Sub-text', 'is_public' => true],
            ['key' => 'app_store_url',      'value' => '', 'type' => $t, 'label' => 'App Store URL',      'is_public' => true],
            ['key' => 'play_store_url',     'value' => '', 'type' => $t, 'label' => 'Google Play URL',    'is_public' => true],
            ['key' => 'app_banner_image',   'value' => '', 'type' => $t, 'label' => 'App Banner Image',   'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // PAYMENT METHODS STRIP
            // ════════════════════════════════════════════════════════════════
            ['key' => 'payment_strip_heading',   'value' => 'আমরা গ্রহণ করি', 'type' => $t, 'label' => 'Payment Strip Heading',   'is_public' => true],
            ['key' => 'payment_show_bkash',      'value' => '1', 'type' => $b, 'label' => 'Show bKash',                           'is_public' => true],
            ['key' => 'payment_show_nagad',      'value' => '1', 'type' => $b, 'label' => 'Show Nagad',                           'is_public' => true],
            ['key' => 'payment_show_rocket',     'value' => '1', 'type' => $b, 'label' => 'Show Rocket',                          'is_public' => true],
            ['key' => 'payment_show_visa',       'value' => '1', 'type' => $b, 'label' => 'Show Visa',                            'is_public' => true],
            ['key' => 'payment_show_mastercard', 'value' => '1', 'type' => $b, 'label' => 'Show Mastercard',                      'is_public' => true],
            ['key' => 'payment_show_cod',        'value' => '1', 'type' => $b, 'label' => 'Show Cash on Delivery',                'is_public' => true],
            ['key' => 'payment_show_emi',        'value' => '1', 'type' => $b, 'label' => 'Show EMI',                             'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // FOOTER
            // ════════════════════════════════════════════════════════════════
            ['key' => 'footer_copyright', 'value' => '© 2026 ADYA BD',       'type' => $t, 'label' => 'Footer Copyright',    'is_public' => true],
            ['key' => 'footer_tagline',   'value' => 'Best online marketplace in Bangladesh',           'type' => $t, 'label' => 'Footer Tagline',     'is_public' => true],
            ['key' => 'footer_address',   'value' => 'Mohammadpur, Dhaka',                              'type' => $t, 'label' => 'Footer Address',     'is_public' => true],
            ['key' => 'footer_phone',     'value' => '',                                                'type' => $t, 'label' => 'Footer Phone',       'is_public' => true],
            ['key' => 'footer_email',     'value' => '',                                                'type' => $t, 'label' => 'Footer Email',       'is_public' => true],
            ['key' => 'social_facebook',  'value' => '', 'type' => $t, 'label' => 'Facebook URL',       'is_public' => true],
            ['key' => 'social_instagram', 'value' => '', 'type' => $t, 'label' => 'Instagram URL',      'is_public' => true],
            ['key' => 'social_youtube',   'value' => '', 'type' => $t, 'label' => 'YouTube URL',        'is_public' => true],
            ['key' => 'social_twitter',   'value' => '', 'type' => $t, 'label' => 'X / Twitter URL',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // POPUP BANNER
            // ════════════════════════════════════════════════════════════════
            ['key' => 'popup_delay',                'value' => '1500', 'type' => $n, 'label' => 'Popup Delay (ms)',               'is_public' => true],
            ['key' => 'popup_allow_dismiss_forever', 'value' => '1',   'type' => $b, 'label' => 'Allow "Don\'t show again"',      'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // GRID COLUMNS (per section, per breakpoint)
            // ════════════════════════════════════════════════════════════════
            // Product grids — homepage featured/new/trending, search, seller, product related
            ['key' => 'grid_products_desktop', 'value' => '5', 'type' => $n, 'label' => 'Products Grid — Desktop columns (lg+)', 'is_public' => true],
            ['key' => 'grid_products_tablet',  'value' => '4', 'type' => $n, 'label' => 'Products Grid — Tablet columns (md)',   'is_public' => true],
            ['key' => 'grid_products_mobile',  'value' => '2', 'type' => $n, 'label' => 'Products Grid — Mobile columns',        'is_public' => true],
            // Category icon strip
            ['key' => 'grid_categories_desktop', 'value' => '8', 'type' => $n, 'label' => 'Categories Grid — Desktop columns',  'is_public' => true],
            ['key' => 'grid_categories_mobile',  'value' => '4', 'type' => $n, 'label' => 'Categories Grid — Mobile columns',   'is_public' => true],
            // Flash sale
            ['key' => 'grid_flash_sale_desktop', 'value' => '4', 'type' => $n, 'label' => 'Flash Sale Grid — Desktop columns',  'is_public' => true],
            ['key' => 'grid_flash_sale_mobile',  'value' => '2', 'type' => $n, 'label' => 'Flash Sale Grid — Mobile columns',   'is_public' => true],
            // Wishlist
            ['key' => 'grid_wishlist_desktop', 'value' => '4', 'type' => $n, 'label' => 'Wishlist Grid — Desktop columns',      'is_public' => true],
            ['key' => 'grid_wishlist_mobile',  'value' => '2', 'type' => $n, 'label' => 'Wishlist Grid — Mobile columns',       'is_public' => true],
            // Blog listing
            ['key' => 'grid_blog_desktop', 'value' => '3', 'type' => $n, 'label' => 'Blog Grid — Desktop columns',              'is_public' => true],
            ['key' => 'grid_blog_mobile',  'value' => '1', 'type' => $n, 'label' => 'Blog Grid — Mobile columns',               'is_public' => true],
            // Deals page
            ['key' => 'grid_deals_desktop', 'value' => '5', 'type' => $n, 'label' => 'Deals Grid — Desktop columns',            'is_public' => true],
            ['key' => 'grid_deals_mobile',  'value' => '2', 'type' => $n, 'label' => 'Deals Grid — Mobile columns',             'is_public' => true],
            // Category listing page
            ['key' => 'grid_category_page_desktop', 'value' => '3', 'type' => $n, 'label' => 'Category Page Grid — Desktop',   'is_public' => true],
            ['key' => 'grid_category_page_mobile',  'value' => '2', 'type' => $n, 'label' => 'Category Page Grid — Mobile',    'is_public' => true],

            // ════════════════════════════════════════════════════════════════
            // CUSTOM CODE
            // ════════════════════════════════════════════════════════════════
            ['key' => 'custom_css',          'value' => '', 'type' => $t, 'label' => 'Custom CSS',          'is_public' => true],
            ['key' => 'custom_head_script',  'value' => '', 'type' => $t, 'label' => 'Custom Head Script',  'is_public' => false],
            ['key' => 'custom_body_script',  'value' => '', 'type' => $t, 'label' => 'Custom Body Script',  'is_public' => false],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                [
                    'value'     => $s['value'],
                    'group'     => 'appearance',
                    'type'      => $s['type'],
                    'label'     => $s['label'],
                    'is_public' => $s['is_public'],
                ]
            );
        }
    }

    public function down(): void
    {
        Setting::where('group', 'appearance')->delete();
    }
};

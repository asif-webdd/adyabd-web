<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AppearanceController extends Controller
{
    /**
     * GET /api/v1/appearance/public  (no auth — called on every page load)
     *
     * Returns ALL appearance settings the frontend needs.
     * NOTE: reads ALL settings regardless of group/is_public so that settings
     * saved from the Filament AppearancePage (which uses group='general') are
     * always included. Sensitive keys (scripts) are stripped server-side.
     */
    public function public(): JsonResponse
    {
        // Load every setting into a flat key→value map
        $raw = Setting::all()->pluck('value', 'key');

        $b = fn(string $key, mixed $default = true) => (bool)(int) $raw->get($key, $default ? 1 : 0);
        $s = fn(string $key, string $default = '') => (string) $raw->get($key, $default);
        $n = fn(string $key, int $default = 0) => (int) $raw->get($key, $default);

        // Converts a stored relative storage path into a full public URL.
        // Already-absolute URLs (http/https) are returned unchanged.
        $fileUrl = function (string $key) use ($raw): ?string {
            $path = (string) $raw->get($key, '');
            if (!$path) return null;
            if (str_starts_with($path, '[')) {   // a multi-file upload value stored as JSON
                $path = (string) (collect(json_decode($path, true) ?: [])->first() ?? '');
                if (!$path) return null;
            }
            return str_starts_with($path, 'http') ? $path : storage_url($path);
        };

        // ── Colours ──────────────────────────────────────────────────────────
        $colors = [
            'primary_color'    => $s('primary_color',    '#006A4E'),
            'accent_color'     => $s('accent_color',     '#E63946'),
            'background_color' => $s('background_color', '#F8FAF9'),
            'navbar_color'     => $s('navbar_color',     '#ffffff'),
            'footer_color'     => $s('footer_color',     '#1a1a2e'),
            'text_color'       => $s('text_color',       '#1a1a1a'),
        ];

        // ── Typography ───────────────────────────────────────────────────────
        $typography = [
            'font_body'      => $s('font_body',      'Hind Siliguri'),
            'font_display'   => $s('font_display',   'Inter'),
            'font_size_base' => $n('font_size_base', 15),
            'border_radius'  => $n('border_radius',  12),
        ];

        // ── Branding ─────────────────────────────────────────────────────────
        $branding = [
            'shop_name'    => $s('shop_name',    'Shop'),
            'shop_tagline' => $s('shop_tagline', 'BAZAR.BD'),
            'shop_name_bn' => $s('shop_name_bn', 'Shop'),
            'shop_logo'    => $fileUrl('shop_logo'),
            'shop_favicon' => $fileUrl('shop_favicon'),
        ];

        // ── Layout ───────────────────────────────────────────────────────────
        $layout = [
            'navbar_style'       => $s('navbar_style',       'default'),
            'homepage_layout'    => $s('homepage_layout',    'grid'),
            'product_card_style' => $s('product_card_style', 'default'),
            'container_width'    => $s('container_width',    'max-w-7xl'),
            'product_columns'    => $n('product_columns',    5),
            'category_columns'   => $n('category_columns',   8),
            'flash_sale_columns' => $n('flash_sale_columns', 4),
            'footer_columns'     => $n('footer_columns',     4),
        ];

        // ── Hero ─────────────────────────────────────────────────────────────
        $hero = [
            'show_hero'         => $b('show_hero'),
            'hero_style'        => $s('hero_style',  'slider'),
            'hero_height'       => $n('hero_height', 420),
            'show_promo_top'    => $b('show_promo_top'),
            'show_promo_bottom' => $b('show_promo_bottom'),
        ];

        // ── Section visibility ────────────────────────────────────────────────
        $sections = [
            'show_announcement_bar'    => $b('show_announcement_bar'),
            'show_trust_badges'        => $b('show_trust_badges'),
            'show_categories'          => $b('show_categories'),
            'show_flash_sale'          => $b('show_flash_sale'),
            'show_featured'            => $b('show_featured'),
            'show_sidebar_banners'     => $b('show_sidebar_banners'),
            'show_local_brands_banner' => $b('show_local_brands_banner'),
            'show_new_arrivals'        => $b('show_new_arrivals'),
            'show_trending'            => $b('show_trending'),
            'show_payment_methods'     => $b('show_payment_methods'),
            'show_app_download'        => $b('show_app_download'),
            'show_popup_banner'        => $b('show_popup_banner'),
        ];

        // ── Section labels & featured tabs ────────────────────────────────────
        $labels = [
            'label_featured'       => $s('label_featured',       'Popular Products'),
            'label_new_arrivals'   => $s('label_new_arrivals',   'New Arrivals'),
            'label_trending'       => $s('label_trending',       'Trending'),
            'label_flash_sale'     => $s('label_flash_sale',     'Flash Sale'),
            'label_special_offers' => $s('label_special_offers', 'Special Offers'),
            'featured_tab_1'       => $s('featured_tab_1',       'all'),
            'featured_tab_2'       => $s('featured_tab_2',       ''),
            'featured_tab_3'       => $s('featured_tab_3',       ''),
            'featured_tab_4'       => $s('featured_tab_4',       ''),
        ];

        // ── Announcement bar ─────────────────────────────────────────────────
        $announcement = [
            'announcement_1' => $s('announcement_1', 'Eid Special Offer! 40% off all clothing'),
            'announcement_2' => $s('announcement_2', 'Order today — Free delivery in Dhaka!'),
            'announcement_3' => $s('announcement_3', 'Get 5% extra discount on bKash payment'),
            'announcement_4' => $s('announcement_4', 'New users get BDT 150 off with code NEWUSER!'),
        ];

        // ── Trust badges ─────────────────────────────────────────────────────
        $trustBadges = [
            'trust_badge_1_title' => $s('trust_badge_1_title', 'Secure Payment'),
            'trust_badge_1_desc'  => $s('trust_badge_1_desc',  'SSL Encrypted'),
            'trust_badge_2_title' => $s('trust_badge_2_title', 'Fast Delivery'),
            'trust_badge_2_desc'  => $s('trust_badge_2_desc',  'Delivery to 64 districts'),
            'trust_badge_3_title' => $s('trust_badge_3_title', 'Easy Returns'),
            'trust_badge_3_desc'  => $s('trust_badge_3_desc',  'Return within 7 days'),
            'trust_badge_4_title' => $s('trust_badge_4_title', '100% Authentic Products'),
            'trust_badge_4_desc'  => $s('trust_badge_4_desc',  'Guaranteed'),
        ];

        // ── Local brands banner ───────────────────────────────────────────────
        $localBrands = [
            'local_brands_heading'      => $s('local_brands_heading',      'Buy Local Products'),
            'local_brands_subtext'      => $s('local_brands_subtext',      'Buy products directly from Bangladeshi entrepreneurs.'),
            'local_brands_button_label' => $s('local_brands_button_label', 'View Local Products'),
            'local_brands_button_link'  => $s('local_brands_button_link',  '/category/fashion'),
        ];

        // ── App download ──────────────────────────────────────────────────────
        $appDownload = [
            'app_banner_heading' => $s('app_banner_heading', 'Download the Shop App'),
            'app_banner_subtext' => $s('app_banner_subtext', 'Get an extra 5% discount when buying from the app.'),
            'app_store_url'      => $s('app_store_url',      ''),
            'play_store_url'     => $s('play_store_url',     ''),
            'app_banner_image'   => $s('app_banner_image',   ''),
        ];

        // ── Home image banner (above footer) ──────────────────────────────────
        $homeBanner = [
            'enabled'  => $b('home_banner_enabled'),
            'image'    => $fileUrl('home_banner_image'),
            'link'     => $s('home_banner_link', ''),
            'width'    => $s('home_banner_width', '100%') ?: '100%',
            'height'   => $s('home_banner_height', ''),
            'fit'      => $s('home_banner_fit', 'cover') ?: 'cover',
            'position' => $s('home_banner_position', 'center') ?: 'center',
        ];

        // ── Payment methods ───────────────────────────────────────────────────
        $paymentMethods = [
            'payment_strip_heading'   => $s('payment_strip_heading',   'We Accept'),
            'payment_show_bkash'      => $b('payment_show_bkash'),
            'payment_show_nagad'      => $b('payment_show_nagad'),
            'payment_show_rocket'     => $b('payment_show_rocket'),
            'payment_show_visa'       => $b('payment_show_visa'),
            'payment_show_mastercard' => $b('payment_show_mastercard'),
            'payment_show_cod'        => $b('payment_show_cod'),
            'payment_show_emi'        => $b('payment_show_emi'),
        ];

        // ── Footer ────────────────────────────────────────────────────────────
        $footer = [
            'footer_copyright' => $s('footer_copyright', '© 2025 Shop.BD — All rights reserved'),
            'footer_tagline'   => $s('footer_tagline',   'The best online marketplace in Bangladesh'),
            'footer_address'   => ($s('footer_address', '') ?: $s('shop_address', '')),
            'footer_phone'     => ($s('footer_phone', '') ?: $s('shop_phone', '')),
            'footer_email'     => ($s('footer_email', '') ?: $s('shop_email', '')),
            'social_facebook'  => ($s('social_facebook', '') ?: $s('facebook_url', '')),
            'social_instagram' => ($s('social_instagram', '') ?: $s('instagram_url', '')),
            'social_youtube'   => ($s('social_youtube', '') ?: $s('youtube_url', '')),
            'social_twitter'   => $s('social_twitter',   ''),
            'whatsapp_number'  => $s('whatsapp_number', ''),
            // "label | time" per line (Admin → Settings → General → Office hours)
            'office_hours'     => collect(preg_split('/\r\n|\r|\n/', $s('office_hours', '')))
                ->map(fn($l) => array_map('trim', array_pad(explode('|', $l, 2), 2, '')))
                ->filter(fn($p) => $p[0] !== '')->map(fn($p) => ['day' => $p[0], 'time' => $p[1]])->values()->all(),
        ];

        // ── Popup ─────────────────────────────────────────────────────────────
        $popup = [
            'popup_delay'                => $n('popup_delay', 1500),
            'popup_frequency'            => $s('popup_frequency', 'once_session'),
            'popup_allow_dismiss_forever'=> $b('popup_allow_dismiss_forever'),
        ];

        // ── Custom CSS + admin-entered head/body scripts ──────────────────────
        // (Admin → Appearance → Custom Code; only admins can write these. Without them the
        //  fields were saved but never reached the storefront.)
        $custom = [
            'custom_css'         => $s('custom_css', ''),
            'custom_head_script' => $s('custom_head_script', ''),
            'custom_body_script' => $s('custom_body_script', ''),
        ];

        // ── Tracking & Analytics (dataLayer) ─────────────────────────────────
        $tracking = [
            'datalayer_enabled'     => $b('datalayer_enabled', true),
            'gtm_id'                => $s('gtm_id', ''),
            'ga4_id'                => $s('ga4_id', ''),
            'fb_pixel_id'           => $s('fb_pixel_id', ''),
            'tiktok_pixel_id'       => $s('tiktok_pixel_id', ''),
            'gads_id'               => $s('gads_id', ''),
            'gads_conversion_label' => $s('gads_conversion_label', ''),
            'currency'              => $s('tracking_currency', 'BDT'),
            'events'                => [
                'page_view'         => $b('track_page_view', true),
                'view_item'         => $b('track_view_item', true),
                'view_item_list'    => $b('track_view_item_list', true),
                'select_item'       => $b('track_select_item', true),
                'add_to_cart'       => $b('track_add_to_cart', true),
                'remove_from_cart'  => $b('track_remove_from_cart', true),
                'view_cart'         => $b('track_view_cart', true),
                'add_to_wishlist'   => $b('track_add_to_wishlist', true),
                'begin_checkout'    => $b('track_begin_checkout', true),
                'add_shipping_info' => $b('track_add_shipping_info', true),
                'add_payment_info'  => $b('track_add_payment_info', true),
                'purchase'          => $b('track_purchase', true),
                'search'            => $b('track_search', true),
                'sign_up'           => $b('track_sign_up', true),
                'login'             => $b('track_login', true),
            ],
        ];

        // ── Google Fonts URL ──────────────────────────────────────────────────
        $fontsUrl = self::buildGoogleFontsUrl(
            $typography['font_body'],
            $typography['font_display']
        );

        return $this->success(array_merge(
            $colors,
            $typography,
            $branding,
            $layout,
            $hero,
            $sections,
            $labels,
            $announcement,
            $trustBadges,
            $localBrands,
            $appDownload,
            $paymentMethods,
            $footer,
            $popup,
            $custom,
            ['tracking' => $tracking],
            ['home_banner' => $homeBanner],
            ['fonts_url' => $fontsUrl]
        ));
    }

    private static function buildGoogleFontsUrl(string $bodyFont, string $displayFont): string
    {
        $families   = [];
        $families[] = 'family=' . urlencode($bodyFont) . ':wght@300;400;500;600;700';
        if ($displayFont !== $bodyFont) {
            $families[] = 'family=' . urlencode($displayFont) . ':wght@400;500;600;700;800;900';
        }
        return 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap';
    }
}

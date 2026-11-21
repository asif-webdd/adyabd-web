/**
 * useAppearance.js  —  v5
 *
 * Fully synced with AppearancePage.php + AppearanceController.php
 * New in v5:
 *  - navbar_style / homepage_layout / product_card_style reactive + CSS var
 *  - hero_height exposed as CSS var (--hero-height)
 *  - primary_light / accent_light computed (10% opacity tints for badges/hovers)
 *  - border_radius exposed on every card/input via --border-radius (already done)
 *  - custom_head_script / custom_body_script injected on client
 *  - favicon applied dynamically
 *  - navbar bg / footer bg also set as CSS vars for `:style` fallback
 *  - _lighten() helper for tint backgrounds
 *  - All 13 backend tabs fully mapped
 */

export const useAppearance = () => {
    const api = useApi()
    const _site = useRuntimeConfig().public.siteName || 'Shop'

    // ── Reactive state ──────────────────────────────────────────────────
    const colors = useState('ap:colors', () => ({
        primary_color: '#006A4E',
        accent_color: '#E63946',
        background_color: '#F8FAF9',
        navbar_color: '#ffffff',
        footer_color: '#1a1a2e',
        text_color: '#1a1a1a',
    }))

    const typography = useState('ap:typo', () => ({
        font_body: 'Hind Siliguri',
        font_display: 'Inter',
        font_size_base: 15,
        border_radius: 12,
    }))

    const branding = useState('ap:branding', () => ({
        shop_name: _site,
        shop_tagline: '',
        shop_name_bn: _site,
        shop_logo: null,
        shop_favicon: null,
    }))

    const layout = useState('ap:layout', () => ({
        navbar_style: 'default',
        homepage_layout: 'grid',
        product_card_style: 'default',
        container_width: 'max-w-7xl',
        product_columns: 5,
        category_columns: 8,
        flash_sale_columns: 4,
        footer_columns: 4,
    }))

    const hero = useState('ap:hero', () => ({
        show_hero: true,
        hero_style: 'slider',
        hero_height: 420,
        show_promo_top: true,
        show_promo_bottom: true,
    }))

    const sections = useState('ap:sections', () => ({
        show_announcement_bar: true,
        show_trust_badges: true,
        show_categories: true,
        show_flash_sale: true,
        show_featured: true,
        show_sidebar_banners: true,
        show_local_brands_banner: true,
        show_new_arrivals: true,
        show_trending: true,
        show_payment_methods: true,
        show_app_download: true,
        show_popup_banner: true,
    }))

    const labels = useState('ap:labels', () => ({
        label_featured: 'জনপ্রিয় পণ্যসমূহ',
        label_new_arrivals: 'নতুন পণ্য',
        label_trending: 'Trending',
        label_flash_sale: 'ফ্ল্যাশ সেল',
        label_special_offers: 'বিশেষ অফার',
        featured_tab_1: 'all',
        featured_tab_2: '',
        featured_tab_3: '',
        featured_tab_4: '',
    }))

    const announcement = useState('ap:announcement', () => ({
        show: true,
        messages: [
            'ঈদ স্পেশাল অফার! সকল পোশাকে ৪০% ছাড়',
            'আজই অর্ডার করুন — ঢাকায় বিনামূল্যে ডেলিভারি!',
            'bKash পেমেন্টে ৫% অতিরিক্ত ছাড় পান',
        ],
    }))

    const trustBadges = useState('ap:trust', () => ([
        {title: 'নিরাপদ পেমেন্ট', desc: 'SSL এনক্রিপ্টেড', icon: 'lock', bg: 'bg-primary/10', color: 'text-primary'},
        {
            title: 'দ্রুত ডেলিভারি',
            desc: '৬৪ জেলায় ডেলিভারি',
            icon: 'local_shipping',
            bg: 'bg-blue-50',
            color: 'text-blue-600'
        },
        {
            title: 'সহজ রিটার্ন',
            desc: '৭ দিনের মধ্যে ফেরত',
            icon: 'assignment_return',
            bg: 'bg-orange-50',
            color: 'text-orange-600'
        },
        {title: '১০০% আসল পণ্য', desc: 'গ্যারান্টিসহ', icon: 'verified', bg: 'bg-yellow-50', color: 'text-yellow-600'},
    ]))

    const localBrands = useState('ap:localBrands', () => ({
        heading: 'দেশীয় পণ্য কিনুন',
        subtext: 'বাংলাদেশি উদ্যোক্তাদের পণ্য সরাসরি কিনুন।',
        button_label: 'দেশীয় পণ্য দেখুন',
        button_link: '/category/fashion',
    }))

    const appDownload = useState('ap:appDownload', () => ({
        heading: `${_site} অ্যাপ ডাউনলোড করুন`,
        subtext: 'অ্যাপ থেকে কিনলে অতিরিক্ত ৫% ছাড়।',
        app_store_url: '',
        play_store_url: '',
        image: null,
    }))

    const homeBanner = useState('ap:homeBanner', () => ({
        enabled: true, image: null, link: '', width: '100%', height: '', fit: 'cover', position: 'center',
    }))

    const paymentMethods = useState('ap:payments', () => ({
        strip_heading: 'আমরা গ্রহণ করি',
        show_bkash: true,
        show_nagad: true,
        show_rocket: true,
        show_visa: true,
        show_mastercard: true,
        show_cod: true,
        show_emi: true,
    }))

    const footer = useState('ap:footer', () => ({
        copyright: `© ${new Date().getFullYear()} ${_site} — সর্বস্বত্ব সংরক্ষিত`,
        tagline: '',
        address: '',
        phone: '',
        email: '',
        facebook: '',
        instagram: '',
        youtube: '',
        twitter: '',
        whatsapp: '',
        office_hours: [],
    }))

    const popup = useState('ap:popup', () => ({
        delay: 1500,
        frequency: 'once_session',
        allow_dismiss_forever: true,
    }))

    const tracking = useState('ap:tracking', () => ({
        datalayer_enabled: true,
        gtm_id: '',
        ga4_id: '',
        fb_pixel_id: '',
        tiktok_pixel_id: '',
        gads_id: '',
        gads_conversion_label: '',
        currency: 'BDT',
        events: {
            page_view: true,
            view_item: true,
            view_item_list: true,
            select_item: true,
            add_to_cart: true,
            remove_from_cart: true,
            view_cart: true,
            add_to_wishlist: true,
            begin_checkout: true,
            add_shipping_info: true,
            add_payment_info: true,
            purchase: true,
            search: true,
            sign_up: true,
            login: true,
        },
    }))

    // ── Apply API response ───────────────────────────────────────────────
    const applyData = (data) => {
        if (!data) return

        colors.value = {
            primary_color: data.primary_color ?? '#006A4E',
            accent_color: data.accent_color ?? '#E63946',
            background_color: data.background_color ?? '#F8FAF9',
            navbar_color: data.navbar_color ?? '#ffffff',
            footer_color: data.footer_color ?? '#1a1a2e',
            text_color: data.text_color ?? '#1a1a1a',
        }
        typography.value = {
            font_body: data.font_body ?? 'Hind Siliguri',
            font_display: data.font_display ?? 'Inter',
            font_size_base: Number(data.font_size_base ?? 15),
            border_radius: Number(data.border_radius ?? 12),
        }
        branding.value = {
            shop_name: data.shop_name ?? _site,
            shop_tagline: data.shop_tagline ?? 'BAZAR.BD',
            shop_name_bn: data.shop_name_bn ?? _site,
            shop_logo: data.shop_logo ?? null,
            shop_favicon: data.shop_favicon ?? null,
        }
        layout.value = {
            navbar_style: data.navbar_style ?? 'default',
            homepage_layout: data.homepage_layout ?? 'grid',
            product_card_style: data.product_card_style ?? 'default',
            container_width: data.container_width ?? 'max-w-7xl',
            product_columns: Number(data.product_columns ?? 5),
            category_columns: Number(data.category_columns ?? 8),
            flash_sale_columns: Number(data.flash_sale_columns ?? 4),
            footer_columns: Number(data.footer_columns ?? 4),
        }
        hero.value = {
            show_hero: Boolean(data.show_hero),
            hero_style: data.hero_style ?? 'slider',
            hero_height: Number(data.hero_height ?? 420),
            show_promo_top: Boolean(data.show_promo_top ?? true),
            show_promo_bottom: Boolean(data.show_promo_bottom ?? true),
        }
        sections.value = {
            show_announcement_bar: Boolean(data.show_announcement_bar ?? true),
            show_trust_badges: Boolean(data.show_trust_badges ?? true),
            show_categories: Boolean(data.show_categories ?? true),
            show_flash_sale: Boolean(data.show_flash_sale ?? true),
            show_featured: Boolean(data.show_featured ?? true),
            show_sidebar_banners: Boolean(data.show_sidebar_banners ?? true),
            show_local_brands_banner: Boolean(data.show_local_brands_banner ?? true),
            show_new_arrivals: Boolean(data.show_new_arrivals ?? true),
            show_trending: Boolean(data.show_trending ?? true),
            show_payment_methods: Boolean(data.show_payment_methods ?? true),
            show_app_download: Boolean(data.show_app_download ?? true),
            show_popup_banner: Boolean(data.show_popup_banner ?? true),
        }
        labels.value = {
            label_featured: data.label_featured ?? 'জনপ্রিয় পণ্যসমূহ',
            label_new_arrivals: data.label_new_arrivals ?? 'নতুন পণ্য',
            label_trending: data.label_trending ?? 'Trending',
            label_flash_sale: data.label_flash_sale ?? 'ফ্ল্যাশ সেল',
            label_special_offers: data.label_special_offers ?? 'বিশেষ অফার',
            featured_tab_1: data.featured_tab_1 ?? 'all',
            featured_tab_2: data.featured_tab_2 ?? '',
            featured_tab_3: data.featured_tab_3 ?? '',
            featured_tab_4: data.featured_tab_4 ?? '',
        }
        const msgs = [
            data.announcement_1,
            data.announcement_2,
            data.announcement_3,
            data.announcement_4,
        ].filter(Boolean)
        announcement.value = {
            show: Boolean(data.show_announcement_bar ?? true),
            messages: msgs.length ? msgs : ['ঈদ স্পেশাল অফার! সকল পোশাকে ৪০% ছাড়'],
        }
        trustBadges.value = [
            {
                title: data.trust_badge_1_title ?? 'নিরাপদ পেমেন্ট',
                desc: data.trust_badge_1_desc ?? 'SSL এনক্রিপ্টেড',
                icon: 'lock',
                bg: 'bg-primary/10',
                color: 'text-primary'
            },
            {
                title: data.trust_badge_2_title ?? 'দ্রুত ডেলিভারি',
                desc: data.trust_badge_2_desc ?? '৬৪ জেলায় ডেলিভারি',
                icon: 'local_shipping',
                bg: 'bg-blue-50',
                color: 'text-blue-600'
            },
            {
                title: data.trust_badge_3_title ?? 'সহজ রিটার্ন',
                desc: data.trust_badge_3_desc ?? '৭ দিনের মধ্যে ফেরত',
                icon: 'assignment_return',
                bg: 'bg-orange-50',
                color: 'text-orange-600'
            },
            {
                title: data.trust_badge_4_title ?? '১০০% আসল পণ্য',
                desc: data.trust_badge_4_desc ?? 'গ্যারান্টিসহ',
                icon: 'verified',
                bg: 'bg-yellow-50',
                color: 'text-yellow-600'
            },
        ]
        localBrands.value = {
            heading: data.local_brands_heading ?? 'দেশীয় পণ্য কিনুন',
            subtext: data.local_brands_subtext ?? 'বাংলাদেশি উদ্যোক্তাদের পণ্য সরাসরি কিনুন।',
            button_label: data.local_brands_button_label ?? 'দেশীয় পণ্য দেখুন',
            button_link: data.local_brands_button_link ?? '/category/fashion',
        }
        appDownload.value = {
            heading: data.app_banner_heading ?? `${_site} অ্যাপ ডাউনলোড করুন`,
            subtext: data.app_banner_subtext ?? 'অ্যাপ থেকে কিনলে অতিরিক্ত ৫% ছাড়।',
            app_store_url: data.app_store_url ?? '',
            play_store_url: data.play_store_url ?? '',
            image: data.app_banner_image ?? null,
        }
        homeBanner.value = {
            enabled: data.home_banner?.enabled ?? true,
            image: data.home_banner?.image || null,
            link: data.home_banner?.link || '',
            width: data.home_banner?.width || '100%',
            height: data.home_banner?.height || '',
            fit: data.home_banner?.fit || 'cover',
            position: data.home_banner?.position || 'center',
        }
        paymentMethods.value = {
            strip_heading: data.payment_strip_heading ?? 'আমরা গ্রহণ করি',
            show_bkash: Boolean(data.payment_show_bkash ?? true),
            show_nagad: Boolean(data.payment_show_nagad ?? true),
            show_rocket: Boolean(data.payment_show_rocket ?? true),
            show_visa: Boolean(data.payment_show_visa ?? true),
            show_mastercard: Boolean(data.payment_show_mastercard ?? true),
            show_cod: Boolean(data.payment_show_cod ?? true),
            show_emi: Boolean(data.payment_show_emi ?? true),
        }
        footer.value = {
            copyright: data.footer_copyright ?? `© ${new Date().getFullYear()} ${_site} — সর্বস্বত্ব সংরক্ষিত`,
            tagline: data.footer_tagline ?? '',
            address: data.footer_address ?? '',
            phone: data.footer_phone ?? '',
            email: data.footer_email ?? '',
            facebook: data.social_facebook ?? '',
            instagram: data.social_instagram ?? '',
            youtube: data.social_youtube ?? '',
            twitter: data.social_twitter ?? '',
            whatsapp: data.whatsapp_number ?? '',
            office_hours: Array.isArray(data.office_hours) ? data.office_hours : [],
        }
        popup.value = {
            delay: Number(data.popup_delay ?? 1500),
            frequency: data.popup_frequency || 'once_session',
            allow_dismiss_forever: Boolean(data.popup_allow_dismiss_forever ?? true),
        }

        const t = data.tracking ?? {}
        tracking.value = {
            datalayer_enabled: Boolean(t.datalayer_enabled ?? true),
            gtm_id: t.gtm_id ?? '',
            ga4_id: t.ga4_id ?? '',
            fb_pixel_id: t.fb_pixel_id ?? '',
            tiktok_pixel_id: t.tiktok_pixel_id ?? '',
            gads_id: t.gads_id ?? '',
            gads_conversion_label: t.gads_conversion_label ?? '',
            currency: t.currency ?? 'BDT',
            events: {
                page_view: Boolean(t.events?.page_view ?? true),
                view_item: Boolean(t.events?.view_item ?? true),
                view_item_list: Boolean(t.events?.view_item_list ?? true),
                select_item: Boolean(t.events?.select_item ?? true),
                add_to_cart: Boolean(t.events?.add_to_cart ?? true),
                remove_from_cart: Boolean(t.events?.remove_from_cart ?? true),
                view_cart: Boolean(t.events?.view_cart ?? true),
                add_to_wishlist: Boolean(t.events?.add_to_wishlist ?? true),
                begin_checkout: Boolean(t.events?.begin_checkout ?? true),
                add_shipping_info: Boolean(t.events?.add_shipping_info ?? true),
                add_payment_info: Boolean(t.events?.add_payment_info ?? true),
                purchase: Boolean(t.events?.purchase ?? true),
                search: Boolean(t.events?.search ?? true),
                sign_up: Boolean(t.events?.sign_up ?? true),
                login: Boolean(t.events?.login ?? true),
            },
        }

        if (import.meta.client) {
            _applyCSS(colors.value, typography.value, hero.value, data.custom_css ?? '')
            _applyFonts(data.fonts_url ?? null)
            _applyFavicon(branding.value.shop_favicon)
            _applyScripts(data.custom_head_script ?? '', data.custom_body_script ?? '')
            _applyTracking(tracking.value)
        }
    }

    // ── Colour helpers ───────────────────────────────────────────────────
    const _hexToRgb = (hex) => {
        const h = hex.replace('#', '')
        const full = h.length === 3 ? h.split('').map(x => x + x).join('') : h
        const r = parseInt(full.slice(0, 2), 16)
        const g = parseInt(full.slice(2, 4), 16)
        const b = parseInt(full.slice(4, 6), 16)
        return `${r} ${g} ${b}`
    }

    // Darken by amount (0–1) — used for hover states
    const _darken = (hex, amount = 0.18) => {
        const h = hex.replace('#', '')
        const full = h.length === 3 ? h.split('').map(x => x + x).join('') : h
        const r = Math.max(0, Math.round(parseInt(full.slice(0, 2), 16) * (1 - amount)))
        const g = Math.max(0, Math.round(parseInt(full.slice(2, 4), 16) * (1 - amount)))
        const b = Math.max(0, Math.round(parseInt(full.slice(4, 6), 16) * (1 - amount)))
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
    }

    // Lighten toward white by amount (0–1) — used for tint/light backgrounds
    const _lighten = (hex, amount = 0.85) => {
        const h = hex.replace('#', '')
        const full = h.length === 3 ? h.split('').map(x => x + x).join('') : h
        const r = Math.min(255, Math.round(parseInt(full.slice(0, 2), 16) + (255 - parseInt(full.slice(0, 2), 16)) * amount))
        const g = Math.min(255, Math.round(parseInt(full.slice(2, 4), 16) + (255 - parseInt(full.slice(2, 4), 16)) * amount))
        const b = Math.min(255, Math.round(parseInt(full.slice(4, 6), 16) + (255 - parseInt(full.slice(4, 6), 16)) * amount))
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
    }

    // ── Apply all CSS custom properties to <html> ────────────────────────
    const _applyCSS = (c, t, h, customCss) => {
        const root = document.documentElement

        const primaryDark = _darken(c.primary_color)
        const primaryLight = _lighten(c.primary_color, 0.88)
        const accentDark = _darken(c.accent_color)
        const accentLight = _lighten(c.accent_color, 0.88)

        // ── Primary
        root.style.setProperty('--color-primary', c.primary_color)
        root.style.setProperty('--color-primary-rgb', _hexToRgb(c.primary_color))
        root.style.setProperty('--color-primary-dark', primaryDark)
        root.style.setProperty('--color-primary-dark-rgb', _hexToRgb(primaryDark))
        root.style.setProperty('--color-primary-light', primaryLight)
        root.style.setProperty('--color-primary-light-rgb', _hexToRgb(primaryLight))

        // ── Accent
        root.style.setProperty('--color-accent', c.accent_color)
        root.style.setProperty('--color-accent-rgb', _hexToRgb(c.accent_color))
        root.style.setProperty('--color-accent-dark', accentDark)
        root.style.setProperty('--color-accent-dark-rgb', _hexToRgb(accentDark))
        root.style.setProperty('--color-accent-light', accentLight)
        root.style.setProperty('--color-accent-light-rgb', _hexToRgb(accentLight))

        // ── Surface / text
        root.style.setProperty('--color-bg', c.background_color)
        root.style.setProperty('--color-navbar', c.navbar_color)
        root.style.setProperty('--color-footer', c.footer_color)
        root.style.setProperty('--color-text', c.text_color)

        // ── Typography
        root.style.setProperty('--font-body', `'${t.font_body}', sans-serif`)
        root.style.setProperty('--font-display', `'${t.font_display}', '${t.font_body}', sans-serif`)
        root.style.setProperty('--font-size-base', `${t.font_size_base}px`)
        root.style.setProperty('--border-radius', `${t.border_radius}px`)
        root.style.setProperty('--border-radius-sm', `${Math.max(4, t.border_radius - 4)}px`)
        root.style.setProperty('--border-radius-lg', `${t.border_radius + 8}px`)

        // ── Hero height
        root.style.setProperty('--hero-height', `${h.hero_height}px`)

        // ── Body defaults
        document.body.style.backgroundColor = c.background_color
        document.body.style.color = c.text_color

        // ── Navbar / footer data-attributes for layout-aware CSS
        document.documentElement.setAttribute('data-navbar', layout.value.navbar_style)

        // ── Custom CSS injection
        let tag = document.getElementById('ap-custom-css')
        if (!tag) {
            tag = document.createElement('style')
            tag.id = 'ap-custom-css'
            document.head.appendChild(tag)
        }
        tag.textContent = customCss || ''
    }

    // ── Google Fonts ──────────────────────────────────────────────────────
    const _applyFonts = (fontsUrl) => {
        if (!fontsUrl) return
        const old = document.getElementById('ap-dynamic-fonts')
        if (old) old.remove()
        const link = document.createElement('link')
        link.id = 'ap-dynamic-fonts'
        link.rel = 'stylesheet'
        link.href = fontsUrl
        document.head.appendChild(link)
    }

    // ── Favicon ───────────────────────────────────────────────────────────
    const _applyFavicon = (faviconUrl) => {
        if (!faviconUrl) return
        let link = document.querySelector("link[rel~='icon']")
        if (!link) {
            link = document.createElement('link')
            link.rel = 'icon'
            document.head.appendChild(link)
        }
        link.href = faviconUrl
    }

    // ── Custom scripts ────────────────────────────────────────────────────
    const _applyScripts = (headScript, bodyScript) => {
        // The appearance is re-applied on navigation; inject each script set only once per page load.
        const sig = (headScript || '') + '\u0000' + (bodyScript || '')
        if (window.__apScriptsSig === sig) return
        window.__apScriptsSig = sig
        // Head script
        if (headScript) {
            const oldHead = document.getElementById('ap-head-script')
            if (oldHead) oldHead.remove()
            const el = document.createElement('div')
            el.id = 'ap-head-script'
            el.innerHTML = headScript
            // Move actual script tags into <head>
            el.querySelectorAll('script').forEach(s => {
                const clone = document.createElement('script')
                if (s.src) clone.src = s.src
                else clone.textContent = s.textContent
                document.head.appendChild(clone)
            })
        }
        // Body script
        if (bodyScript) {
            const oldBody = document.getElementById('ap-body-script')
            if (oldBody) oldBody.remove()
            const el = document.createElement('div')
            el.id = 'ap-body-script'
            el.innerHTML = bodyScript
            el.querySelectorAll('script').forEach(s => {
                const clone = document.createElement('script')
                if (s.src) clone.src = s.src
                else clone.textContent = s.textContent
                document.body.appendChild(clone)
            })
        }
    }

    // ── Tracking scripts (GTM / GA4 / Meta Pixel / TikTok Pixel / Google Ads) ─
    const _applyTracking = (t) => {
        // Always make sure window.dataLayer exists so push() never errors,
        // even if tracking is disabled (events will just be checked off).
        // NOTE: the actual GTM / GA4 / Meta Pixel / TikTok Pixel snippets are
        // rendered server-side via useHead() in app.vue, using Google's/Meta's
        // exact official snippet format. This keeps Tag Assistant / Meta Events
        // Manager validation happy and ensures GTM is present in the initial
        // HTML <head> (and the noscript iframe right after <body>), as required.
        window.dataLayer = window.dataLayer || []
    }


    // ── Public load function ──────────────────────────────────────────────
    const load = async () => {
        try {
            const data = await api.get('/appearance/public')
            applyData(data)
        } catch (e) {
            console.warn('[useAppearance] fetch failed:', e)
        }
    }

    // ── Grid class helpers ────────────────────────────────────────────────
    // Maps a column count to a responsive Tailwind grid class string.
    // Always 2 cols on mobile, scales up per breakpoint.
    const productGridClass = computed(() => {
        const map = {
            3: 'grid grid-cols-2 md:grid-cols-3 gap-4',
            4: 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4',
            5: 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4',
            6: 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4',
        }
        return map[layout.value.product_columns] ?? map[5]
    })

    const productGridCols = computed(() => {
        const map = {
            3: 'grid-cols-2 sm:grid-cols-3',
            4: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
            5: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
            6: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6',
        }
        return map[layout.value.product_columns] ?? map[5]
    })

    // For pages with a sidebar (category, search) - one fewer column at the top breakpoint
    const productGridColsNarrow = computed(() => {
        const map = {
            3: 'grid-cols-2 sm:grid-cols-3',
            4: 'grid-cols-2 sm:grid-cols-3',
            5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
            6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
        }
        return map[layout.value.product_columns] ?? map[5]
    })

    const categoryGridCols = computed(() => {
        const map = {
            4:  'grid-cols-3 sm:grid-cols-4',
            6:  'grid-cols-4 sm:grid-cols-6',
            8:  'grid-cols-4 sm:grid-cols-6 md:grid-cols-8',
            10: 'grid-cols-5 sm:grid-cols-8 md:grid-cols-10',
        }
        return map[layout.value.category_columns] ?? map[8]
    })

    const flashSaleGridCols = computed(() => {
        const map = {
            2: 'grid-cols-2',
            3: 'grid-cols-2 md:grid-cols-3',
            4: 'grid-cols-2 md:grid-cols-4',
            5: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
        }
        return map[layout.value.flash_sale_columns] ?? map[4]
    })

    const footerGridCols = computed(() => {
        const map = {
            2: 'grid-cols-1 md:grid-cols-2',
            3: 'grid-cols-1 md:grid-cols-3',
            4: 'grid-cols-1 md:grid-cols-4',
        }
        return map[layout.value.footer_columns] ?? map[4]
    })

    return {
        load,
        colors, typography, branding, layout,
        hero, sections, labels,
        announcement, trustBadges,
        localBrands, appDownload, paymentMethods, homeBanner,
        footer, popup,
        tracking,
        // grid helpers
        productGridClass, productGridCols, productGridColsNarrow,
        categoryGridCols, flashSaleGridCols, footerGridCols,
    }
}

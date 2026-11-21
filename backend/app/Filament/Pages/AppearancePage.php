<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\{
    Tabs,
    Section,
    Grid,
    ColorPicker,
    Select,
    Toggle,
    TextInput,
    Textarea,
    ViewField,
    Actions,
    FileUpload,
    Repeater
};
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class AppearancePage extends Page
{
    use \App\Filament\Concerns\ChecksAdminAreaPage;
    protected static string $area = 'settings';
    protected static ?string $navigationIcon  = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Appearance';
    protected static ?string $title           = 'Appearance';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.pages.appearance';

    public array $data = [];

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Appearance')->tabs([

                // ── 1. Branding ──────────────────────────────────────────────
                Tabs\Tab::make('Branding')->icon('heroicon-o-swatch')->schema([

                    Section::make('Brand Colours')
                        ->description('These colours are applied as CSS variables across the entire storefront.')
                        ->icon('heroicon-o-swatch')
                        ->schema([
                            ColorPicker::make('primary_color')
                                ->label('Primary Colour')
                                ->helperText('Navigation, buttons, links, focus rings'),

                            ColorPicker::make('accent_color')
                                ->label('Accent / CTA Colour')
                                ->helperText('Add to cart, sale badges, primary buttons'),

                            ColorPicker::make('background_color')
                                ->label('Page Background')
                                ->helperText('Main body background colour'),

                            ColorPicker::make('text_color')
                                ->label('Body Text Colour')
                                ->helperText('Default text across pages'),

                            ColorPicker::make('navbar_color')
                                ->label('Navbar Background')
                                ->helperText('Top navigation bar background'),

                            ColorPicker::make('footer_color')
                                ->label('Footer Background')
                                ->helperText('Site footer background'),
                        ])->columns(3),

                    Section::make('Logo & Favicon')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('shop_logo')
                                ->label('Store Logo')
                                ->image()
                                ->disk('public')
                                ->directory('settings')
                                ->maxSize(4096)
                                ->imagePreviewHeight('80')
                                ->helperText('Any size works — the logo is scaled to fit the header without cropping. Best: wide PNG/SVG with transparent background (e.g. 400×100px).'),

                            FileUpload::make('shop_favicon')
                                ->label('Favicon')
                                ->image()
                                ->disk('public')
                                ->directory('settings')
                                ->maxSize(1024)
                                ->imagePreviewHeight('64')
                                ->helperText('Recommended: 32×32px or 64×64px .ico / .png'),
                        ])->columns(2),

                    Section::make('Store Identity')
                        ->description('Text displayed in the navbar when no logo image is set, and in the browser tab.')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([
                            TextInput::make('shop_name')
                                ->label('Store Name')
                                ->placeholder('Shop')
                                ->helperText('Short name shown in the navbar logo area'),

                            TextInput::make('shop_tagline')
                                ->label('Tagline / Sub-text')
                                ->placeholder('BAZAR.BD')
                                ->helperText('Small text shown beneath the store name'),

                            TextInput::make('shop_name_bn')
                                ->label('Store Name (Bengali)')
                                ->placeholder('Shop')
                                ->helperText('Bengali version shown in the logo block'),
                        ])->columns(3),
                ]),

                // ── 2. Typography ────────────────────────────────────────────
                Tabs\Tab::make('Typography')->icon('heroicon-o-document-text')->schema([

                    Section::make('Fonts')
                        ->description('Google Fonts are loaded automatically based on these selections.')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Select::make('font_body')
                                ->label('Body / Bengali Font')
                                ->options([
                                    'Hind Siliguri'     => 'Hind Siliguri (default)',
                                    'Noto Sans Bengali' => 'Noto Sans Bengali',
                                    'Baloo Da 2'        => 'Baloo Da 2',
                                    'Tiro Bangla'       => 'Tiro Bangla',
                                    'Kalpurush'         => 'Kalpurush',
                                ])
                                ->native(false)
                                ->helperText('Used for Bengali text and body copy'),

                            Select::make('font_display')
                                ->label('Display / UI Font')
                                ->options([
                                    'Inter'             => 'Inter (default)',
                                    'Poppins'           => 'Poppins',
                                    'DM Sans'           => 'DM Sans',
                                    'Plus Jakarta Sans' => 'Plus Jakarta Sans',
                                    'Nunito'            => 'Nunito',
                                    'Outfit'            => 'Outfit',
                                ])
                                ->native(false)
                                ->helperText('Used for headings, prices, and UI labels'),

                            TextInput::make('font_size_base')
                                ->label('Base Font Size (px)')
                                ->numeric()
                                ->minValue(12)
                                ->maxValue(20)
                                ->step(1)
                                ->suffix('px')
                                ->helperText('Default: 15px'),

                            TextInput::make('border_radius')
                                ->label('Border Radius (px)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(32)
                                ->step(2)
                                ->suffix('px')
                                ->helperText('Cards, buttons, inputs. Default: 12px'),
                        ])->columns(2),
                ]),

                // ── 3. Layout ────────────────────────────────────────────────
                Tabs\Tab::make('Layout')->icon('heroicon-o-squares-2x2')->schema([
                    Section::make('Container Width')
                        ->icon('heroicon-o-arrows-pointing-out')
                        ->schema([
                            Select::make('container_width')
                                ->label('Max Content Width')
                                ->options([
                                    'max-w-5xl'        => 'Narrow  (1024px)',
                                    'max-w-6xl'        => 'Medium  (1152px)',
                                    'max-w-7xl'        => 'Default (1280px)',
                                    'max-w-screen-xl'  => 'Wide (1280px screen)',
                                    'max-w-screen-2xl' => 'Extra Wide (1536px)',
                                    'max-w-full'       => 'Full Width',
                                ])
                                ->native(false)
                                ->helperText('Controls the max-width of the header, footer, and main content areas'),
                        ])->columns(1),
                    Section::make('Navigation')
                        ->icon('heroicon-o-bars-3')
                        ->schema([
                            Select::make('navbar_style')
                                ->label('Navbar Style')
                                ->options([
                                    'default'  => 'Default (white, full-width)',
                                    'centered' => 'Centered logo',
                                    'minimal'  => 'Minimal (no category bar)',
                                    'colored'  => 'Coloured (uses Primary colour)',
                                ])
                                ->native(false),
                        ])->columns(1),

                    Section::make('Product Grid')
                        ->icon('heroicon-o-squares-2x2')
                        ->description('Controls how many product cards appear per row on desktop across all product listing pages (homepage, category, search, seller, wishlist, deals).')
                        ->schema([
                            Select::make('product_columns')
                                ->label('Product Columns (desktop)')
                                ->options([
                                    '3' => '3 columns — large cards',
                                    '4' => '4 columns — balanced',
                                    '5' => '5 columns — compact (default)',
                                    '6' => '6 columns — dense',
                                ])
                                ->native(false)
                                ->helperText('Mobile is always 2 columns'),

                            Select::make('product_card_style')
                                ->label('Product Card Style')
                                ->options([
                                    'default'  => 'Default (image top)',
                                    'compact'  => 'Compact',
                                    'detailed' => 'Detailed (rating + seller visible)',
                                ])
                                ->native(false),

                            Select::make('homepage_layout')
                                ->label('Homepage Layout Mode')
                                ->options([
                                    'grid' => 'Grid',
                                    'list' => 'List',
                                ])
                                ->native(false),
                        ])->columns(3),

                    Section::make('Category & Flash Sale Grid')
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            Select::make('category_columns')
                                ->label('Category Icons per Row (desktop)')
                                ->options([
                                    '4' => '4 icons',
                                    '6' => '6 icons',
                                    '8' => '8 icons (default)',
                                    '10' => '10 icons',
                                ])
                                ->native(false)
                                ->helperText('Category icon strip on homepage & drawer'),

                            Select::make('flash_sale_columns')
                                ->label('Flash Sale Columns (desktop)')
                                ->options([
                                    '2' => '2 columns',
                                    '3' => '3 columns',
                                    '4' => '4 columns (default)',
                                    '5' => '5 columns',
                                ])
                                ->native(false)
                                ->helperText('Products shown in the flash sale strip'),

                            Select::make('footer_columns')
                                ->label('Footer Columns')
                                ->options([
                                    '2' => '2 columns',
                                    '3' => '3 columns',
                                    '4' => '4 columns (default)',
                                ])
                                ->native(false)
                                ->helperText('Footer grid layout on desktop'),
                        ])->columns(3),
                ]),

                // ── 4. Hero Banner ────────────────────────────────────────────
                Tabs\Tab::make('Hero Banner')->icon('heroicon-o-photo')->schema([

                    Section::make('Hero Settings')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Toggle::make('show_hero')
                                ->label('Show Hero Banner')
                                ->inline(false),

                            Select::make('hero_style')
                                ->label('Hero Style')
                                ->options([
                                    'slider'    => 'Auto-sliding carousel',
                                    'static'    => 'Single static image',
                                    'split'     => 'Split: banner + 2 small tiles',
                                    'fullwidth' => 'Full-width single banner',
                                ])
                                ->native(false),

                            TextInput::make('hero_height')
                                ->label('Hero Height (px)')
                                ->numeric()
                                ->minValue(200)
                                ->maxValue(800)
                                ->step(10)
                                ->suffix('px'),
                        ])->columns(3),

                    Section::make('Promo Top Banners')
                        ->description('Two small tiles shown beside the hero slider on desktop (position = promo_top). Toggle visibility here; manage banner content in the Banners module.')
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            Toggle::make('show_promo_top')
                                ->label('Show Promo Top Tiles (beside hero)')
                                ->inline(false),
                        ])->columns(1),

                    Section::make('Promo Bottom Banners')
                        ->description('Full-width horizontal strip beneath the hero (position = promo_bottom). Up to 3 banners shown side-by-side.')
                        ->icon('heroicon-o-bars-2')
                        ->schema([
                            Toggle::make('show_promo_bottom')
                                ->label('Show Promo Bottom Banner Strip')
                                ->inline(false),
                        ])->columns(1),
                ]),

                // ── 5. Homepage Sections ──────────────────────────────────────
                Tabs\Tab::make('Sections')->icon('heroicon-o-rectangle-stack')->schema([

                    Section::make('Section Visibility')
                        ->description('Toggle which sections appear on the homepage and configure their labels.')
                        ->icon('heroicon-o-eye')
                        ->schema([

                            // ── Announcement Bar ──────────────────────────────
                            Toggle::make('show_announcement_bar')
                                ->label('Show Announcement Bar')
                                ->helperText('The scrolling promo strip at the very top of the page')
                                ->inline(false),

                            // ── Trust Badges ──────────────────────────────────
                            Toggle::make('show_trust_badges')
                                ->label('Show Trust Badges')
                                ->helperText('Safe payment, fast delivery, easy return, 100% genuine')
                                ->inline(false),

                            // ── Category Icons Row ────────────────────────────
                            Toggle::make('show_categories')
                                ->label('Show Category Icons Row')
                                ->inline(false),

                            // ── Flash Sale ────────────────────────────────────
                            Toggle::make('show_flash_sale')
                                ->label('Show Flash Sale Countdown')
                                ->inline(false),

                            // ── Featured / Popular Products ───────────────────
                            Toggle::make('show_featured')
                                ->label('Show Featured / Popular Products')
                                ->inline(false),

                            // ── Sidebar / Special Offers banners ──────────────
                            Toggle::make('show_sidebar_banners')
                                ->label('Show Special Offer Banners')
                                ->helperText('The 2-column banner grid between Featured and Local Brands')
                                ->inline(false),

                            // ── Local Brands CTA ──────────────────────────────
                            Toggle::make('show_local_brands_banner')
                                ->label('Show "Buy Local Products" CTA Banner')
                                ->helperText('Green gradient banner promoting local Bangladeshi products')
                                ->inline(false),

                            // ── New Arrivals ──────────────────────────────────
                            Toggle::make('show_new_arrivals')
                                ->label('Show New Arrivals Section')
                                ->inline(false),

                            // ── Trending ──────────────────────────────────────
                            Toggle::make('show_trending')
                                ->label('Show Trending Products Section')
                                ->inline(false),

                            // ── Payment Methods ───────────────────────────────
                            Toggle::make('show_payment_methods')
                                ->label('Show Payment Methods Strip')
                                ->helperText('bKash, Nagad, Rocket, Visa, Mastercard, Cash on Delivery, EMI')
                                ->inline(false),

                            // ── App Download ──────────────────────────────────
                            Toggle::make('show_app_download')
                                ->label('Show App Download Banner')
                                ->helperText('Dark banner with App Store / Google Play links')
                                ->inline(false),

                            // ── Popup Banner ──────────────────────────────────
                            Toggle::make('show_popup_banner')
                                ->label('Show Popup Banner on Landing')
                                ->inline(false),

                        ])->columns(3),

                    Section::make('Section Labels')
                        ->description('Customize the Bengali / English headings shown above each product section.')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            TextInput::make('label_featured')
                                ->label('Featured Products Heading')
                                ->placeholder('Popular Products')
                                ->helperText('Default: Popular Products'),

                            TextInput::make('label_new_arrivals')
                                ->label('New Arrivals Heading')
                                ->placeholder('New Products')
                                ->helperText('Default:New Product'),

                            TextInput::make('label_trending')
                                ->label('Trending Section Heading')
                                ->placeholder('Trending')
                                ->helperText('Default: Trending'),

                            TextInput::make('label_flash_sale')
                                ->label('Flash Sale Heading')
                                ->placeholder('Flash Sale')
                                ->helperText('Default: Flash Sale'),

                            TextInput::make('label_special_offers')
                                ->label('Special Offers Heading')
                                ->placeholder('Special Offers')
                                ->helperText('Default: Special Offers'),
                        ])->columns(2),

                    Section::make('Featured Products Tabs')
                        ->description('Pick which category each tab pill (above the Featured Products grid) shows. The tab name comes from the category automatically and clicking it loads that category\'s products. Leave a tab blank to hide it. If fewer than two tabs are set, the storefront shows "All" + your categories automatically.')
                        ->icon('heroicon-o-tag')
                        ->schema(collect([1, 2, 3, 4])->map(fn ($i) => Select::make("featured_tab_$i")
                            ->label("Tab $i")
                            ->options(fn () => self::featuredTabOptions())
                            ->searchable()
                            ->native(false)
                            ->placeholder('— hidden —')
                        )->all())->columns(4),
                ]),

                // ── 6. Announcement Bar ───────────────────────────────────────
                Tabs\Tab::make('Announcement Bar')->icon('heroicon-o-megaphone')->schema([

                    Section::make('Announcement Bar Messages')
                        ->description('Rotating messages shown in the accent-coloured strip at the top of every page. Each message auto-rotates every 4 seconds.')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            TextInput::make('announcement_1')
                                ->label('Message 1')
                                ->placeholder('Eid Special Offer! 40% off all clothing')
                                ->columnSpanFull(),

                            TextInput::make('announcement_2')
                                ->label('Message 2')
                                ->placeholder('Order today — Free delivery in Dhaka!')
                                ->columnSpanFull(),

                            TextInput::make('announcement_3')
                                ->label('Message 3')
                                ->placeholder('Get 5% extra discount on bKash payment')
                                ->columnSpanFull(),

                            TextInput::make('announcement_4')
                                ->label('Message 4')
                                ->placeholder('New users get BDT 150 off with code NEWUSER!')
                                ->columnSpanFull(),
                        ])->columns(1),
                ]),

                // ── 7. Trust Badges ───────────────────────────────────────────
                Tabs\Tab::make('Trust Badges')->icon('heroicon-o-shield-check')->schema([

                    Section::make('Trust Badge Labels')
                        ->description('The 4 trust badges shown on the homepage. Each badge has a title and a short description.')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            TextInput::make('trust_badge_1_title')->label('Badge 1 — Title')->placeholder('Secure Payment'),
                            TextInput::make('trust_badge_1_desc')->label('Badge 1 — Description')->placeholder('SSL Encrypted'),

                            TextInput::make('trust_badge_2_title')->label('Badge 2 — Title')->placeholder('Fast Delivery'),
                            TextInput::make('trust_badge_2_desc')->label('Badge 2 — Description')->placeholder('Delivery to 64 districts'),

                            TextInput::make('trust_badge_3_title')->label('Badge 3 — Title')->placeholder('Easy Returns'),
                            TextInput::make('trust_badge_3_desc')->label('Badge 3 — Description')->placeholder('Return within 7 days'),

                            TextInput::make('trust_badge_4_title')->label('Badge 4 — Title')->placeholder('100% Authentic Products'),
                            TextInput::make('trust_badge_4_desc')->label('Badge 4 — Description')->placeholder('Guaranteed'),
                        ])->columns(2),
                ]),

                // ── 8. Local Brands Banner ────────────────────────────────────
                Tabs\Tab::make('Local Brands')->icon('heroicon-o-building-storefront')->schema([

                    Section::make('"Buy Local Products" Banner')
                        ->description('The green gradient CTA strip that promotes local Bangladeshi products.')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            TextInput::make('local_brands_heading')
                                ->label('Heading')
                                ->placeholder('Buy Local Products')
                                ->helperText('Bold headline on the banner'),

                            TextInput::make('local_brands_subtext')
                                ->label('Sub-text')
                                ->placeholder('Buy products directly from Bangladeshi entrepreneurs.')
                                ->helperText('Shorter description line below the heading'),

                            TextInput::make('local_brands_button_label')
                                ->label('Button Label')
                                ->placeholder('View Local Products'),

                            TextInput::make('local_brands_button_link')
                                ->label('Button Link / URL')
                                ->placeholder('/category/fashion')
                                ->helperText('Relative or absolute URL the CTA button points to'),
                        ])->columns(2),
                ]),

                // ── 9. App Download Banner ────────────────────────────────────
                Tabs\Tab::make('App Download')->icon('heroicon-o-device-phone-mobile')->schema([

                    Section::make('App Download Banner')
                        ->description('The dark banner at the bottom of the homepage with App Store and Google Play download links.')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([
                            TextInput::make('app_banner_heading')
                                ->label('Heading')
                                ->placeholder('Download the Shop App'),

                            TextInput::make('app_banner_subtext')
                                ->label('Sub-text')
                                ->placeholder('Get an extra 5% discount when buying from the app.'),

                            TextInput::make('app_store_url')
                                ->label('App Store URL')
                                ->placeholder('https://apps.apple.com/...')
                                ->helperText('Leave blank to hide the App Store button'),

                            TextInput::make('play_store_url')
                                ->label('Google Play URL')
                                ->placeholder('https://play.google.com/...')
                                ->helperText('Leave blank to hide the Google Play button'),

                            FileUpload::make('app_banner_image')->disk('public')
                                ->label('Banner Image')
                                ->image()
                                ->directory('settings')
                                ->helperText('Recommended: 200×200px square. Shown on the left side of the banner.'),
                        ])->columns(2),
                ]),

                // ── 10. Payment Methods ────────────────────────────────────────
                Tabs\Tab::make('Payment Methods')->icon('heroicon-o-credit-card')->schema([

                    Section::make('Payment Methods Strip')
                        ->description('Toggle which payment methods are displayed in the "We Accept" section on the homepage.')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            Toggle::make('payment_show_bkash')
                                ->label('bKash')
                                ->inline(false),

                            Toggle::make('payment_show_nagad')
                                ->label('Nagad')
                                ->inline(false),

                            Toggle::make('payment_show_rocket')
                                ->label('Rocket')
                                ->inline(false),

                            Toggle::make('payment_show_visa')
                                ->label('Visa')
                                ->inline(false),

                            Toggle::make('payment_show_mastercard')
                                ->label('Mastercard')
                                ->inline(false),

                            Toggle::make('payment_show_cod')
                                ->label('Cash on Delivery')
                                ->inline(false),

                            Toggle::make('payment_show_emi')
                                ->label('EMI / Instalment')
                                ->inline(false),
                        ])->columns(4),

                    Section::make('Strip Heading')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            TextInput::make('payment_strip_heading')
                                ->label('Strip Heading')
                                ->placeholder('We Accept')
                                ->helperText('Default: We Accept'),
                        ])->columns(1),
                ]),

                // ── 11. Footer ─────────────────────────────────────────────────
                // ── Home Image Banner (above footer) ─────────────────────────
                Tabs\Tab::make('Home Image Banner')->icon('heroicon-o-photo')->schema([
                    Section::make('Image section above the footer (Homepage)')
                        ->description('A single full-width image shown on the homepage just above the footer. Leave image empty to show the placeholder.')
                        ->schema([
                            Toggle::make('home_banner_enabled')->label('Show this section')->default(true),

                            FileUpload::make('home_banner_image')->disk('public')
                                ->label('Image')->image()->directory('settings')
                                ->helperText('Recommended: 1920 × 600 px (JPG/WebP, under 300 KB). Mobile will scale it down automatically.'),

                            TextInput::make('home_banner_link')->label('Link (optional)')
                                ->placeholder('https://… or /category/shoes')
                                ->helperText('Clicking the image opens this link. Leave blank for no link.'),

                            TextInput::make('home_banner_width')->label('Width')
                                ->placeholder('100%')->default('100%')
                                ->helperText('Default 100% (full width). Examples: 100%, 1200px, 80%. Recommended image width: 1920 px.'),

                            TextInput::make('home_banner_height')->label('Height')
                                ->placeholder('auto')
                                ->live(onBlur: true)
                                ->helperText('Leave blank = auto (image keeps its own ratio). Examples: 400px, 50vh. Recommended: 400–600 px for a 1920 px wide image.'),

                            Select::make('home_banner_fit')->label('Object fit')
                                ->options(['cover' => 'Cover (fill, crop extra)', 'contain' => 'Contain (show whole image)', 'fill' => 'Fill (stretch)', 'none' => 'None', 'scale-down' => 'Scale down'])
                                ->default('cover')
                                ->visible(fn (Get $get) => filled($get('home_banner_height')))
                                ->helperText('How the image fits inside the set height.'),

                            Select::make('home_banner_position')->label('Object / background position')
                                ->options(['center' => 'Center', 'top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right',
                                    'left top' => 'Left top', 'right top' => 'Right top', 'left bottom' => 'Left bottom', 'right bottom' => 'Right bottom'])
                                ->default('center')
                                ->visible(fn (Get $get) => filled($get('home_banner_height')))
                                ->helperText('Which part of the image stays visible when it is cropped.'),
                        ])->columns(2),
                ]),

                Tabs\Tab::make('Footer')->icon('heroicon-o-document-minus')->schema([

                    Section::make('Footer Content')
                        ->description('Text and links displayed in the site footer.')
                        ->icon('heroicon-o-document-minus')
                        ->schema([
                            TextInput::make('footer_copyright')
                                ->label('Copyright Text')
                                ->placeholder('© 2025 Shop.BD — All rights reserved')
                                ->columnSpanFull(),

                            TextInput::make('footer_tagline')
                                ->label('Footer Tagline')
                                ->placeholder('The best online marketplace in Bangladesh')
                                ->columnSpanFull(),

                            TextInput::make('footer_address')
                                ->label('Business Address')
                                ->placeholder('Dhaka, Bangladesh')
                                ->columnSpanFull(),

                            TextInput::make('footer_phone')
                                ->label('Phone / Hotline')
                                ->placeholder('+880 1XXX-XXXXXX'),

                            TextInput::make('footer_email')
                                ->label('Support Email')
                                ->placeholder('support@yourshop.com'),
                        ])->columns(2),

                    Section::make('Social Links')
                        ->description('Links shown as social icons in the footer. Leave blank to hide.')
                        ->icon('heroicon-o-share')
                        ->schema([
                            TextInput::make('social_facebook')
                                ->label('Facebook URL')
                                ->placeholder('https://facebook.com/...'),

                            TextInput::make('social_instagram')
                                ->label('Instagram URL')
                                ->placeholder('https://instagram.com/...'),

                            TextInput::make('social_youtube')
                                ->label('YouTube URL')
                                ->placeholder('https://youtube.com/...'),

                            TextInput::make('social_twitter')
                                ->label('X / Twitter URL')
                                ->placeholder('https://x.com/...'),
                        ])->columns(2),
                ]),

                // ── 12. Popup Banner ───────────────────────────────────────────
                Tabs\Tab::make('Popup Banner')->icon('heroicon-o-megaphone')->schema([

                    Section::make('Popup Banner Settings')
                        ->description('Controls the modal popup shown when a visitor first lands on the homepage. The popup banner image and link are managed in the Banners module (position = popup).')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Toggle::make('show_popup_banner')
                                ->label('Enable Popup Banner on Homepage')
                                ->inline(false),

                            TextInput::make('popup_delay')
                                ->label('Delay before showing (ms)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10000)
                                ->step(500)
                                ->suffix('ms')
                                ->helperText('Default: 1500ms (1.5 seconds)'),

                            Select::make('popup_frequency')
                                ->label('How often to show')
                                ->options([
                                    'once_ever'    => 'Only the first time a visitor ever comes',
                                    'once_session' => 'Once per visit (browser session)',
                                    'every_24h'    => 'Once every 24 hours',
                                    'every_visit'  => 'On every homepage load',
                                ])
                                ->default('once_session')
                                ->native(false),

                            Toggle::make('popup_allow_dismiss_forever')
                                ->label('Allow "Don\'t show again" button')
                                ->inline(false),
                        ])->columns(2),
                ]),

                // ── 13. Custom Code ────────────────────────────────────────────
                Tabs\Tab::make('Custom Code')->icon('heroicon-o-code-bracket')->schema([

                    Section::make('Custom CSS')
                        ->description('Injected inside a <style> tag in the frontend <head>. Scoped to the storefront only.')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Textarea::make('custom_css')
                                ->label('')
                                ->rows(10)
                                ->placeholder("/* Example: hide the search bar on mobile */\n@media (max-width: 640px) {\n  .search-bar { display: none; }\n}")
                                ->columnSpanFull(),
                        ]),

                    Section::make('Custom Scripts')
                        ->description('Injected verbatim — use with caution. Head script loads before </head>, body script before </body>.')
                        ->icon('heroicon-o-code-bracket-square')
                        ->schema([
                            Textarea::make('custom_head_script')
                                ->label('Head Script (</head>)')
                                ->rows(5)
                                ->placeholder('<!-- e.g. Google Tag Manager, Meta Pixel -->')
                                ->columnSpanFull(),

                            Textarea::make('custom_body_script')
                                ->label('Body Script (</body>)')
                                ->rows(5)
                                ->placeholder('<!-- e.g. Intercom, Crisp chat -->')
                                ->columnSpanFull(),
                        ]),
                ]),

                // ── 14. Tracking & Analytics ──────────────────────────────────
                Tabs\Tab::make('Tracking & Analytics')->icon('heroicon-o-chart-bar')->schema([

                    Section::make('Master Switch')
                        ->description('Turn the entire dataLayer / analytics pipeline on or off for the storefront.')
                        ->icon('heroicon-o-power')
                        ->schema([
                            Toggle::make('datalayer_enabled')
                                ->label('Enable dataLayer & Tracking')
                                ->helperText('When off, no tracking scripts or dataLayer events fire on the storefront — regardless of the settings below.')
                                ->default(true)
                                ->live()
                                ->inline(false),
                        ]),

                    Section::make('Tracking IDs')
                        ->description('Paste your container / pixel IDs. Leave blank to disable that platform. Scripts are injected automatically — no need to add them in Custom Code.')
                        ->icon('heroicon-o-key')
                        ->schema([
                            TextInput::make('gtm_id')
                                ->label('Google Tag Manager Container ID')
                                ->placeholder('GTM-XXXXXXX')
                                ->helperText('Recommended — manage all other pixels from inside GTM using this dataLayer.'),

                            TextInput::make('ga4_id')
                                ->label('Google Analytics 4 Measurement ID')
                                ->placeholder('G-XXXXXXXXXX')
                                ->helperText('Only needed if NOT using GTM for GA4.'),

                            TextInput::make('fb_pixel_id')
                                ->label('Meta (Facebook) Pixel ID')
                                ->placeholder('123456789012345'),

                            TextInput::make('tiktok_pixel_id')
                                ->label('TikTok Pixel ID')
                                ->placeholder('CXXXXXXXXXXXXXXXXXXX'),

                            TextInput::make('gads_id')
                                ->label('Google Ads Conversion ID')
                                ->placeholder('AW-XXXXXXXXX'),

                            TextInput::make('gads_conversion_label')
                                ->label('Google Ads Purchase Conversion Label')
                                ->placeholder('AbCdEfGhIjKlMnOp'),
                        ])->columns(2)
                        ->visible(fn (Get $get) => (bool) $get('datalayer_enabled')),

                    Section::make('E-commerce Events')
                        ->description('Choose which standard GA4 / GTM e-commerce events are pushed to window.dataLayer from the storefront.')
                        ->icon('heroicon-o-bolt')
                        ->schema([
                            Toggle::make('track_page_view')->label('page_view')->default(true)->inline(false),
                            Toggle::make('track_view_item')->label('view_item')->default(true)->inline(false),
                            Toggle::make('track_view_item_list')->label('view_item_list')->default(true)->inline(false),
                            Toggle::make('track_select_item')->label('select_item')->default(true)->inline(false),
                            Toggle::make('track_add_to_cart')->label('add_to_cart')->default(true)->inline(false),
                            Toggle::make('track_remove_from_cart')->label('remove_from_cart')->default(true)->inline(false),
                            Toggle::make('track_view_cart')->label('view_cart')->default(true)->inline(false),
                            Toggle::make('track_add_to_wishlist')->label('add_to_wishlist')->default(true)->inline(false),
                            Toggle::make('track_begin_checkout')->label('begin_checkout')->default(true)->inline(false),
                            Toggle::make('track_add_shipping_info')->label('add_shipping_info')->default(true)->inline(false),
                            Toggle::make('track_add_payment_info')->label('add_payment_info')->default(true)->inline(false),
                            Toggle::make('track_purchase')->label('purchase')->default(true)->inline(false),
                            Toggle::make('track_search')->label('search')->default(true)->inline(false),
                            Toggle::make('track_sign_up')->label('sign_up')->default(true)->inline(false),
                            Toggle::make('track_login')->label('login')->default(true)->inline(false),
                        ])->columns(4)
                        ->visible(fn (Get $get) => (bool) $get('datalayer_enabled')),

                    Section::make('E-commerce Settings')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            TextInput::make('tracking_currency')
                                ->label('Currency Code (ISO 4217)')
                                ->placeholder('BDT')
                                ->default('BDT')
                                ->maxLength(3),
                        ])->columns(2)
                        ->visible(fn (Get $get) => (bool) $get('datalayer_enabled')),
                ]),

            ])->columnSpanFull()->persistTabInQueryString(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            // Filament Toggle returns true/false — store as 1/0 so the
            // API controller can safely cast with (bool)(int)
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_string($value)) {
                // Trim stray whitespace (e.g. accidentally pasted leading/
                // trailing spaces in tracking IDs, URLs, etc.)
                $value = trim($value);
            }
            Setting::set($key, $value, 'appearance');
        }
        cache()->flush();
        Notification::make()->success()->title('Appearance saved!')->body('Changes will appear on the storefront immediately.')->send();
    }

    public function resetToDefaults(): void
    {
        $defaults = [
            // Branding
            'primary_color'               => '#006A4E',
            'accent_color'                => '#E63946',
            'background_color'            => '#F8FAF9',
            'navbar_color'                => '#ffffff',
            'footer_color'                => '#1a1a2e',
            'text_color'                  => '#1a1a1a',
            'shop_name'                   => 'Shop',
            'shop_tagline'                => 'BAZAR.BD',
            'shop_name_bn'                => 'Shop',

            // Typography
            'font_body'                   => 'Hind Siliguri',
            'font_display'                => 'Inter',
            'font_size_base'              => '15',
            'border_radius'               => '12',

            // Layout
            'navbar_style'                => 'default',
            'homepage_layout'             => 'grid',
            'product_card_style'          => 'default',
            'container_width'             => 'max-w-7xl',
            'product_columns'             => '5',
            'category_columns'            => '8',
            'flash_sale_columns'          => '4',
            'footer_columns'              => '4',

            // Hero
            'show_hero'                   => '1',
            'hero_style'                  => 'slider',
            'hero_height'                 => '420',
            'show_promo_top'              => '1',
            'show_promo_bottom'           => '1',

            // Sections visibility
            'show_announcement_bar'       => '1',
            'show_trust_badges'           => '1',
            'show_categories'             => '1',
            'show_flash_sale'             => '1',
            'show_featured'               => '1',
            'show_sidebar_banners'        => '1',
            'show_local_brands_banner'    => '1',
            'show_new_arrivals'           => '1',
            'show_trending'               => '1',
            'show_payment_methods'        => '1',
            'show_app_download'           => '1',
            'show_popup_banner'           => '1',

            // Section labels
            'label_featured'              => 'Popular Products',
            'label_new_arrivals'          => 'New Arrivals',
            'label_trending'              => 'Trending',
            'label_flash_sale'            => 'Flash Sale',
            'label_special_offers'        => 'Special Offers',

            // Featured tabs
            'featured_tab_1'              => 'all',
            'featured_tab_2'              => '',
            'featured_tab_3'              => '',
            'featured_tab_4'              => '',

            // Announcement bar
            'announcement_1'              => 'Eid Special Offer! 40% off all clothing',
            'announcement_2'              => 'Order today — Free delivery in Dhaka!',
            'announcement_3'              => 'Get 5% extra discount on bKash payment',
            'announcement_4'              => 'New users get BDT 150 off with code NEWUSER!',

            // Trust badges
            'trust_badge_1_title'         => 'Secure Payment',
            'trust_badge_1_desc'          => 'SSL Encrypted',
            'trust_badge_2_title'         => 'Fast Delivery',
            'trust_badge_2_desc'          => 'Delivery to 64 districts',
            'trust_badge_3_title'         => 'Easy Returns',
            'trust_badge_3_desc'          => 'Return within 7 days',
            'trust_badge_4_title'         => '100% Authentic Products',
            'trust_badge_4_desc'          => 'Guaranteed',

            // Local brands banner
            'local_brands_heading'        => 'Buy Local Products',
            'local_brands_subtext'        => 'Buy products directly from Bangladeshi entrepreneurs.',
            'local_brands_button_label'   => 'View Local Products',
            'local_brands_button_link'    => '/category/fashion',

            // App download banner
            'app_banner_heading'          => 'Download the Shop App',
            'app_banner_subtext'          => 'Get an extra 5% discount when buying from the app.',
            'app_store_url'               => '',
            'play_store_url'              => '',

            // Payment methods
            'payment_strip_heading'       => 'We Accept',
            'payment_show_bkash'          => '1',
            'payment_show_nagad'          => '1',
            'payment_show_rocket'         => '1',
            'payment_show_visa'           => '1',
            'payment_show_mastercard'     => '1',
            'payment_show_cod'            => '1',
            'payment_show_emi'            => '1',

            // Footer
            'footer_copyright'            => '© 2025 Shop.BD — All rights reserved',
            'footer_tagline'              => 'The best online marketplace in Bangladesh',
            'footer_address'              => 'Dhaka, Bangladesh',
            'footer_phone'                => '',
            'footer_email'                => '',
            'social_facebook'             => '',
            'social_instagram'            => '',
            'social_youtube'              => '',
            'social_twitter'              => '',

            // Popup
            'popup_delay'                 => '1500',
            'popup_frequency'             => 'once_session',
            'popup_allow_dismiss_forever' => '1',

            // Custom code
            'custom_css'                  => '',
            'custom_head_script'          => '',
            'custom_body_script'          => '',

            // Tracking & Analytics
            'datalayer_enabled'           => '1',
            'gtm_id'                      => '',
            'ga4_id'                      => '',
            'fb_pixel_id'                 => '',
            'tiktok_pixel_id'             => '',
            'gads_id'                     => '',
            'gads_conversion_label'       => '',
            'tracking_currency'           => 'BDT',
            'track_page_view'             => '1',
            'track_view_item'             => '1',
            'track_view_item_list'        => '1',
            'track_select_item'           => '1',
            'track_add_to_cart'           => '1',
            'track_remove_from_cart'      => '1',
            'track_view_cart'             => '1',
            'track_add_to_wishlist'       => '1',
            'track_begin_checkout'        => '1',
            'track_add_shipping_info'     => '1',
            'track_add_payment_info'      => '1',
            'track_purchase'              => '1',
            'track_search'                => '1',
            'track_sign_up'               => '1',
            'track_login'                 => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value, 'appearance');
        }
        cache()->flush();
        $this->mount();
        Notification::make()->success()->title('Reset to defaults!')->send();
    }

    /** Options for the Featured Products tab selects: "all" + every active category (slug => name). */
    public static function featuredTabOptions(): array
    {
        $opts = ['all' => 'All Products'];
        \App\Models\Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ->each(function ($c) use (&$opts) {
                $opts[$c->slug] = $c->name_bn ? "{$c->name} ({$c->name_bn})" : $c->name;
            });
        return $opts;
    }

    public function mount(): void
    {
        $data = Setting::all()->pluck('value', 'key')->toArray();
        // Older installs stored free-text tab names — map them to category slugs so the selects show them
        $opts = self::featuredTabOptions();
        $cats = \App\Models\Category::query()->get();
        foreach ([1, 2, 3, 4] as $i) {
            $v = trim((string) ($data["featured_tab_$i"] ?? ''));
            if ($v === '' || isset($opts[$v])) continue;
            $m = $cats->first(fn ($c) => mb_strtolower($c->name) === mb_strtolower($v) || mb_strtolower((string) $c->name_bn) === mb_strtolower($v));
            $data["featured_tab_$i"] = $m ? $m->slug : (($i === 1) ? 'all' : null);
        }
        $this->form->fill($data);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

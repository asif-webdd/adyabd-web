<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Exception;
use Filament\Forms\{Components\Actions,
    Form,
    Components\Tabs,
    Components\Section,
    Components\TextInput,
    Components\Textarea,
    Components\Toggle,
    Components\Select,
    Components\Repeater,
    Components\Grid,
    Components\Placeholder,
    Get
};
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class SettingsPage extends Page
{
    use \App\Filament\Concerns\ChecksAdminAreaPage;
    protected static string $area = 'settings';
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'System Settings';
    protected static ?string $title           = 'System Settings';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.settings';

    /** Settings keys owned by each tab – each tab is saved on its own. */
    protected const TAB_FIELDS = [
        'general' => ['shop_email', 'shop_phone', 'shop_address', 'office_hours', 'shop_url', 'invoice_prefix', 'invoice_pattern', 'invoice_sequence_length'],
        'delivery' => ['enable_standard_shipping', 'standard_shipping_charge', 'enable_express_shipping', 'express_shipping_charge', 'enable_same_day_shipping', 'same_day_shipping_charge', 'free_shipping_min_order', 'city_delivery_charges', 'auto_book_courier', 'auto_confirm_order'],
        'payment' => ['enable_cod', 'enable_zinipay', 'enable_wallet', 'zinipay_api_key', 'zinipay_base_url', 'zinipay_frontend_url', 'zinipay_webhook_url'],
        'sms' => ['sms_gateway', 'sms_sender_id', 'ssl_wireless_token', 'ssl_wireless_sid', 'bulk_sms_api_key', 'sms_on_order_placed', 'sms_on_order_confirmed', 'sms_on_order_shipped', 'sms_on_order_delivered', 'sms_on_order_cancelled', 'sms_on_user_blocked', 'sms_on_seller_new_order', 'sms_on_support_reply', 'sms_on_contact_reply'],
        'blocking' => ['auto_block_enabled', 'auto_block_ip_enabled'],
        'ecommerce' => ['seller_min_withdrawal', 'seller_products_auto_approve', 'products_per_page', 'platform_commission', 'enable_reviews', 'auto_approve_reviews', 'enable_referral', 'referral_bonus_amount'],
        'smtp' => ['mail_mailer', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name', 'test_email_address', 'email_on_order_placed', 'email_on_order_status', 'email_on_support_reply'],
        'social' => ['facebook_url', 'instagram_url', 'youtube_url', 'whatsapp_number'],
    ];

    public array $data = [];

    public function mount(): void
    {
        $state = Setting::all()->pluck('value', 'key')->toArray();

        // The repeater is stored as a JSON string; the form needs an array
        // (without this the page crashed with a 500 right after the first delivery-charge save).
        $rows = $state['city_delivery_charges'] ?? [];
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        $state['city_delivery_charges'] = is_array($rows) ? array_values($rows) : [];

        $this->form->fill($state);

        // Fields that were never saved yet show their default instead of staying empty.
        foreach ($this->form->getFlatFields(true) as $name => $field) {
            if (blank(data_get($this->data, $name)) && ($default = $field->getDefaultState()) !== null) {
                data_set($this->data, $name, $default);
            }
        }
    }

    protected function saveButton(string $tab, string $label): Actions
    {
        return Actions::make([
            Actions\Action::make("save_{$tab}")
                ->label("Save {$label} Settings")
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(fn () => $this->saveTab($tab)),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Settings')->tabs([
                Tabs\Tab::make('General')->icon('heroicon-o-building-storefront')->schema([
                    Section::make('Shop Information')
                        ->description('Logo, favicon, and store name are managed under Appearance → Branding.')
                        ->schema([
                        TextInput::make('shop_email')->label('Email')->email(),
                        TextInput::make('shop_phone')->label('Helpline')->default('16488'),
                        TextInput::make('shop_url')->label('Website URL')->url(),
                        Textarea::make('shop_address')->label('Address')->rows(2)->columnSpanFull(),
                        Textarea::make('office_hours')->label('Office hours')->rows(3)->columnSpanFull()
                            ->helperText('One per line: label | time   e.g.  Saturday – Thursday | 9 AM – 9 PM'),
                    ])->columns(2),

                    Section::make('Invoice Numbering')
                        ->description('Controls the order/invoice number generated for every new order (e.g. used in admin invoices, SMS, and order tracking).')
                        ->schema([
                        TextInput::make('invoice_prefix')
                            ->label('Prefix')
                            ->default('BD')
                            ->maxLength(10)
                            ->helperText('Letters shown at the start of every invoice number.'),
                        Select::make('invoice_pattern')
                            ->label('Pattern')
                            ->live()
                            ->options([
                                'PREFIX-YEAR-SEQ'        => 'PREFIX-YEAR-SEQ  (e.g. BD-2026-00001)',
                                'PREFIX-YEARMONTH-SEQ'   => 'PREFIX-YEARMONTH-SEQ  (e.g. BD-202606-00001)',
                                'PREFIX-SEQ'             => 'PREFIX-SEQ  (e.g. BD-00001, never resets)',
                                'PREFIX-YEAR-MONTH-SEQ'  => 'PREFIX-YEAR-MONTH-SEQ  (e.g. BD-2026-06-00001)',
                            ])
                            ->default('PREFIX-YEAR-SEQ')
                            ->required(),
                        TextInput::make('invoice_sequence_length')
                            ->label('Sequence Digits')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(10)
                            ->default(5)
                            ->helperText('Number of zero-padded digits, e.g. 5 → 00001.'),
                        Placeholder::make('invoice_pattern_preview')
                            ->label('Preview')
                            ->content(fn (Get $get) => \App\Models\Order::previewOrderNumber(
                                $get('invoice_prefix') ?: 'BD',
                                $get('invoice_pattern') ?: 'PREFIX-YEAR-SEQ',
                                (int) ($get('invoice_sequence_length') ?: 5)
                            ))
                            ->columnSpanFull(),
                    ])->columns(3),
                    $this->saveButton('general', 'General'),
                ]),
                Tabs\Tab::make('Delivery')->icon('heroicon-o-truck')->schema([
                    Section::make('Delivery Charges')->schema([
                        Toggle::make('enable_standard_shipping')->label('Enable Standard Delivery')->default(true)->live(),
                        TextInput::make('standard_shipping_charge')->label('Standard Default (BDT )')->numeric()->default(60)
                            ->visible(fn (Get $get) => (bool) $get('enable_standard_shipping'))
                            ->helperText('Used when the customer\'s city is not listed below.'),

                        Toggle::make('enable_express_shipping')->label('Enable Express Delivery')->default(true)->live(),
                        TextInput::make('express_shipping_charge')->label('Express (BDT )')->numeric()->default(120)
                            ->visible(fn (Get $get) => (bool) $get('enable_express_shipping')),

                        Toggle::make('enable_same_day_shipping')->label('Enable Same Day Delivery')->default(true)->live(),
                        TextInput::make('same_day_shipping_charge')->label('Same Day (BDT )')->numeric()->default(150)
                            ->visible(fn (Get $get) => (bool) $get('enable_same_day_shipping')),

                        TextInput::make('free_shipping_min_order')->label('Free Shipping Minimum (BDT )')->numeric()->default(500),
                    ])->columns(2),

                    Section::make('City / District Delivery Charges')
                        ->description('Set different standard delivery charges per area. Select the type first, then pick from the dropdown. Unmatched addresses fall back to the default charge above.')
                        ->schema([
                            Repeater::make('city_delivery_charges')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Select::make('area_type')
                                            ->label('Type')
                                            ->options([
                                                'division' => 'Division',
                                                'district' => 'District',
                                                'upazila'  => 'Upazila',
                                            ])
                                            ->default('district')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('area_name', null);
                                            }),

                                        Select::make('area_name')
                                            ->label('Area')
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->options(function (Get $get) {
                                                $type = $get('area_type') ?? 'district';
                                                return match ($type) {
                                                    'division' => \App\Models\Division::orderBy('name')
                                                                    ->pluck('name', 'name')
                                                                    ->toArray(),
                                                    'upazila'  => \App\Models\Upazila::orderBy('name')
                                                                    ->pluck('name', 'name')
                                                                    ->toArray(),
                                                    default    => \App\Models\District::orderBy('name')
                                                                    ->pluck('name', 'name')
                                                                    ->toArray(),
                                                };
                                            })
                                            ->placeholder(fn (Get $get) => match($get('area_type') ?? 'district') {
                                                'division' => 'Select division...',
                                                'upazila'  => 'Search upazila...',
                                                default    => 'Select district...',
                                            }),

                                        TextInput::make('charge')
                                            ->label('Charge (BDT )')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required()
                                            ->suffix('BDT '),
                                    ]),
                                ])
                                ->addActionLabel('+ Add Area')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    ($state['area_name'] ?? '') !== ''
                                        ? ($state['area_name'] . ' (' . ($state['area_type'] ?? 'district') . ') — BDT ' . ($state['charge'] ?? '0'))
                                        : null
                                )
                                ->defaultItems(0)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Auto Booking')->schema([
                        Toggle::make('auto_book_courier')->label('Automatic Courier Booking'),
                        Toggle::make('auto_confirm_order')->label('Automatic Order Confirmation'),
                    ])->columns(2),
                    $this->saveButton('delivery', 'Delivery'),
                ]),
                Tabs\Tab::make('Payment')->icon('heroicon-o-credit-card')->schema([
                    Section::make('Payment Gateways')->schema([
                        Toggle::make('enable_cod')->label('Cash on Delivery')->default(true),
                        Toggle::make('enable_zinipay')->label('ZiniPay')->default(true)->live(),
                        Toggle::make('enable_wallet')->label('Wallet')->default(true),
                    ])->columns(3),

                    Section::make('ZiniPay Configuration')
                        ->visible(fn (Get $get) => (bool) $get('enable_zinipay'))
                        ->schema([
                        TextInput::make('zinipay_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('From your ZiniPay merchant dashboard. Saving this updates payments immediately — no server restart needed.'),
                        TextInput::make('zinipay_base_url')
                            ->label('API Base URL')
                            ->default('https://api.zinipay.com')
                            ->helperText('Only change this if ZiniPay gives you a different API host.'),
                        TextInput::make('zinipay_frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->default(rtrim(config('app.frontend_url'), '/'))
                            ->helperText('Customers are sent back here after paying, to /order/payment-callback (e.g. https://yourshop.com).'),
                        TextInput::make('zinipay_webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->default(rtrim(config('app.url'), '/') . '/api/v1/payment/zinipay/webhook')
                            ->helperText('ZiniPay calls this URL after payment. Only change if this server is reached via a different public domain.'),
                    ])->columns(2),
                    $this->saveButton('payment', 'Payment'),
                ]),
                Tabs\Tab::make('SMS')->icon('heroicon-o-chat-bubble-left-ellipsis')->schema([
                    Section::make('SMS Gateway')->schema([
                        Select::make('sms_gateway')->label('Gateway')->options([
                            'ssl_wireless' => 'SSL Wireless',
                            'bulk_sms_bd'  => 'BulkSMSBD',
                        ])->default('ssl_wireless'),
                        TextInput::make('sms_sender_id')->label('Sender ID')->default('BazarBD'),
                        TextInput::make('ssl_wireless_token')->label('SSL Wireless Token')->password()->revealable(),
                        TextInput::make('ssl_wireless_sid')->label('SSL Wireless SID'),
                        TextInput::make('bulk_sms_api_key')->label('BulkSMSBD API Key')->password()->revealable(),
                    ])->columns(2),

                    Section::make('SMS Notifications')->schema([
                        Toggle::make('sms_on_order_placed')->label('On Order Placed')->default(true),
                        Toggle::make('sms_on_order_confirmed')->label('On Order Confirmed')->default(true),
                        Toggle::make('sms_on_order_shipped')->label('On Order Shipped')->default(true),
                        Toggle::make('sms_on_order_delivered')->label('On Order Delivered')->default(true),
                        Toggle::make('sms_on_order_cancelled')->label('On Order Cancelled')->default(true),
                        Toggle::make('sms_on_user_blocked')->label('On User Blocked')->default(true),
                        Toggle::make('sms_on_seller_new_order')->label('Seller: new order / cancellation / payout')->default(true),
                        Toggle::make('sms_on_support_reply')->label('Support ticket reply')->default(true),
                        Toggle::make('sms_on_contact_reply')->label('Contact message reply')->default(true),
                    ])->columns(3),
                    $this->saveButton('sms', 'SMS'),
                ]),

                Tabs\Tab::make('Blocking')->icon('heroicon-o-no-symbol')->schema([
                    Section::make('Auto Blocking')->schema([
                        Toggle::make('auto_block_enabled')->label('Enable Auto Blocking')->default(true),
                        Toggle::make('auto_block_ip_enabled')->label('Enable Auto IP Blocking')->default(true),
                    ])->columns(2),
                    $this->saveButton('blocking', 'Blocking'),
                ]),

                Tabs\Tab::make('E-Commerce')->icon('heroicon-o-shopping-bag')->schema([
                    Section::make('Products & Orders')->schema([
                        TextInput::make('products_per_page')->numeric()->default(20),
                        TextInput::make('platform_commission')->label('Commission (%)')->numeric()->default(10),
                        Toggle::make('enable_reviews')->label('Enable Reviews')->default(true),
                        Toggle::make('auto_approve_reviews')->label('Auto Approve Reviews'),
                        Toggle::make('enable_referral')->label('Enable Referral')->default(true),
                        TextInput::make('referral_bonus_amount')->label('Referral Bonus (BDT )')->numeric()->default(50),
                    ])->columns(3),

                    Section::make('Sellers')->description('Rules for the multi-vendor marketplace.')->schema([
                        TextInput::make('seller_min_withdrawal')->label('Minimum withdrawal (BDT)')->numeric()->minValue(1)->default(500),
                        Toggle::make('seller_products_auto_approve')->label('Publish seller products without admin approval'),
                    ])->columns(2),
                    $this->saveButton('ecommerce', 'E-Commerce'),
                ]),

                Tabs\Tab::make('SMTP / Email')->icon('heroicon-o-envelope')->schema([
                    Section::make('SMTP Configuration')->schema([
                        Select::make('mail_mailer')->label('Mail Driver')->options([
                            'smtp'    => 'SMTP',
                            'mailgun' => 'Mailgun',
                            'ses'     => 'Amazon SES',
                            'log'     => 'Log (Testing)',
                        ])->default('smtp')->required(),
                        TextInput::make('mail_host')->label('SMTP Host')->placeholder('smtp.gmail.com')->default('smtp.gmail.com'),
                        TextInput::make('mail_port')->label('SMTP Port')->numeric()->placeholder('587')->default('587'),
                        Select::make('mail_encryption')->label('Encryption')->options([
                            'tls' => 'TLS (Port 587)',
                            'ssl' => 'SSL (Port 465)',
                            ''    => 'None',
                        ])->default('tls'),
                        TextInput::make('mail_username')->label('SMTP Username / Email')->placeholder('you@gmail.com'),
                        TextInput::make('mail_password')->label('SMTP Password / App Password')->password()->revealable()->helperText('For Gmail: use an App Password, not your account password. Generate at myaccount.google.com → Security → App Passwords.'),
                        TextInput::make('mail_from_address')->label('From Address')->email()->placeholder('noreply@yourdomain.com'),
                        TextInput::make('mail_from_name')->label('From Name')->placeholder('Your Shop Name'),
                    ])->columns(2),

                    Section::make('Customer emails')->description('Sent only when the customer has an email address on their account.')->schema([
                        Toggle::make('email_on_order_placed')->label('Order placed')->default(true),
                        Toggle::make('email_on_order_status')->label('Order confirmed / shipped / delivered / cancelled')->default(true),
                        Toggle::make('email_on_support_reply')->label('Support ticket reply')->default(true),
                    ])->columns(3),

                    Section::make('Send Test Email')->schema([
                        TextInput::make('test_email_address')->label('Recipient Email')->email()->placeholder('test@example.com')->helperText('Enter an address then click Send Test Email to verify your SMTP settings.'),
                        Actions::make([
                            Actions\Action::make('send_test_email')->label('Send Test Email')->color('info')->icon('heroicon-o-paper-airplane')->action(function (Get $get) {
                                $to = $get('test_email_address');
                                if (!$to) {
                                    Notification::make()->danger()->title('No test email address')->body('Please enter a test email address.')->send();
                                    return;
                                }

                                $this->applySMTPConfigDynamicallyFromFormData([
                                    'mail_host'         => $get('mail_host'),
                                    'mail_port'         => $get('mail_port'),
                                    'mail_encryption'   => $get('mail_encryption'),
                                    'mail_username'     => $get('mail_username'),
                                    'mail_password'     => $get('mail_password'),
                                    'mail_from_address' => $get('mail_from_address'),
                                    'mail_from_name'    => $get('mail_from_name'),
                                ]);

                                try {
                                    Mail::html(view('emails.admin.smtp_test', [
                                        'user'    => auth()->user(),
                                        'subject' => ' SMTP Test Email',
                                    ])->render(), fn($m) => $m->to($to)->subject(' SMTP Test — ' . config('app.name')));
                                    Notification::make()->success()->title('Test email sent!')->body("Check your inbox at {$to}")->send();
                                } catch (Exception $e) {
                                    Notification::make()->danger()->title('SMTP Error')->body($e->getMessage())->send();
                                }
                            }),
                        ])->verticallyAlignCenter(),
                    ])->columns(2),
                    $this->saveButton('smtp', 'SMTP / Email'),
                ]),

                Tabs\Tab::make('Social Media')->icon('heroicon-o-share')->schema([
                    Section::make()->schema([
                        TextInput::make('facebook_url')->url(),
                        TextInput::make('instagram_url')->url(),
                        TextInput::make('youtube_url')->url(),
                        TextInput::make('whatsapp_number'),
                    ])->columns(2),
                    $this->saveButton('social', 'Social Media'),
                ]),

            ])->columnSpanFull()->persistTabInQueryString(),
        ])->statePath('data');
    }

    public function applySMTPConfigDynamicallyFromFormData(array $data): void
    {
        config([
            'mail.mailers.smtp.host'       => $data['mail_host'] ?? config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port'       => $data['mail_port'] ?? config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
            'mail.mailers.smtp.username'   => $data['mail_username'] ?? config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password'   => $data['mail_password'] ?? config('mail.mailers.smtp.password'),
            'mail.from.address'            => $data['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name'               => $data['mail_from_name'] ?? config('mail.from.name'),
        ]);
    }

    public function sendTestEmail(): void
    {
        // Read the raw values: the test must work no matter what is filled in on other tabs.
        $data = $this->data;
        $to = $data['test_email_address'] ?? null;

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Notification::make()->danger()->title('No valid test email address')->body('Please enter a valid test email address in the SMTP tab.')->send();
            return;
        }

        $this->applySMTPConfigDynamicallyFromFormData($data);

        try {
            Mail::html(
                view('emails.admin.smtp_test', [
                    'user'    => auth()->user(),
                    'subject' => ' SMTP Test Email',
                ])->render(),
                fn($m) => $m->to($to)->subject(' SMTP Test — ' . config('app.name'))
            );

            Notification::make()->success()->title('Test email sent!')->body("Check your inbox at {$to}")->send();
        } catch (Exception $e) {
            Notification::make()->danger()->title('SMTP Error')->body($e->getMessage())->send();
        }
    }

    /** Validate ONLY the fields that belong to $tab (other tabs can be empty/incomplete). */
    protected function validateTab(string $tab): void
    {
        $keys  = self::TAB_FIELDS[$tab] ?? [];
        $rules = [];
        $names = [];

        foreach ($this->form->getFlatFields(true) as $name => $field) {
            if (!in_array($name, $keys, true)) {
                continue;
            }
            if ($fieldRules = $field->getValidationRules()) {
                $rules[$field->getStatePath()] = $fieldRules;
                $names[$field->getStatePath()] = $field->getValidationAttribute();
            }
        }

        if ($tab === 'delivery') {
            $rules['data.city_delivery_charges.*.area_name'] = ['required'];
            $rules['data.city_delivery_charges.*.charge']    = ['required', 'numeric', 'min:0'];
            $names['data.city_delivery_charges.*.area_name'] = 'area';
            $names['data.city_delivery_charges.*.charge']    = 'charge';
        }

        $this->validate($rules, [], $names);
    }

    /** Save a single tab; validation and writes never touch other tabs' settings. */
    public function saveTab(string $tab): void
    {
        $keys = self::TAB_FIELDS[$tab] ?? [];
        if (!$keys) {
            return;
        }

        $this->validateTab($tab);

        foreach ($keys as $key) {
            $value = data_get($this->data, $key);

            if ($key === 'city_delivery_charges') {
                // Store as JSON type so Setting::get() decodes it automatically
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => json_encode(array_values($value ?? [])),
                        'group' => 'delivery',
                        'type'  => 'json',
                    ]
                );
            } else {
                Setting::set($key, $value);
            }
            cache()->forget("setting_{$key}");
        }

        cache()->flush();
        $this->applySMTPConfigDynamicallyFromFormData($this->data);
        Notification::make()->success()->title(ucfirst($tab) . ' settings saved!')->send();
    }

    /** Kept for any old caller: saves every tab, each one validated on its own. */
    public function save(): void
    {
        foreach (array_keys(self::TAB_FIELDS) as $tab) {
            $this->saveTab($tab);
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

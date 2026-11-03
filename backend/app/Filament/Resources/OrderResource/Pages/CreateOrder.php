<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\{Address, Coupon, District, Division, Order, Product, ProductVariant, Upazila, User};
use Filament\Forms\Form;
use Filament\Forms\Components\{Section, Grid, Group, Radio, Select, TextInput, Textarea, Toggle, Repeater, Placeholder};
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\{HtmlString, Str};

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;


    public function getTitle(): string
    {
        return 'Create Order';
    }

    // ── Form ──────────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make()->columnSpan(['lg' => 2])->schema([
                    $this->customerSection(),
                    $this->addressSection(),
                    $this->itemsSection(),
                    $this->settingsSection(),
                ]),

                Group::make()->columnSpan(['lg' => 1])->schema([
                    $this->summarySection(),
                ]),
            ]),
        ]);
    }

    // ── Customer ─────────────────────────────────────────────────────────

    protected function customerSection(): Section
    {
        return Section::make('Customer')->icon('heroicon-o-user')->description('Select an existing customer or create a new one for this order.')->schema([
            Radio::make('customer_type')->label('Customer')->options([
                'existing' => 'Existing Customer',
                'new'      => 'New Customer',
            ])->default('existing')->inline()->inlineLabel(false)->live()->afterStateUpdated(function (callable $set) {
                $set('user_id', null);
                $set('address_id', null);
            }),
            Select::make('user_id')->label('Select customer')->placeholder('Search by name or phone number...')->searchable()->getSearchResultsUsing(fn(string $search): array => User::query()->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))->limit(50)->get()->mapWithKeys(fn(User $u) => [$u->id => "{$u->name} — {$u->phone}"])->all())
                ->getOptionLabelUsing(fn($value) => ($u = User::find($value)) ? "{$u->name} — {$u->phone}" : null)->live()->required(fn(callable $get) => $get('customer_type') === 'existing')->visible(fn(callable $get) => $get('customer_type') === 'existing')->afterStateUpdated(function (callable $set, callable $get) {
                    $userId = $get('user_id');

                    $address = $userId ? (Address::where('user_id', $userId)->where('is_default', true)->first() ?? Address::where('user_id', $userId)->first()) : null;
                    $set('address_id', $address?->id ?? 'new');
                })->columnSpanFull(),

            Placeholder::make('customer_preview')->label('')->content(function (callable $get) {
                $user = $get('user_id') ? User::find($get('user_id')) : null;

                if (!$user) {
                    return null;
                }

                return new HtmlString(
                    '<div style="font-size:13px;display:flex;flex-wrap:wrap;gap:16px;padding:8px 12px;background:#f9fafb;border-radius:8px">'
                    . '<span><span style="color:#6b7280">Phone:</span> ' . e($user->phone) . '</span>'
                    . '<span><span style="color:#6b7280">Email:</span> ' . e($user->email ?? '—') . '</span>'
                    . '<span><span style="color:#6b7280">Orders:</span> ' . (int)$user->total_orders . '</span>'
                    . '<span><span style="color:#6b7280">Wallet:</span> BDT  ' . number_format((float)$user->wallet_balance, 2) . '</span>'
                    . ($user->is_blocked ? '<span style="color:#dc2626;font-weight:600">Blocked</span>' : '')
                    . '</div>'
                );
            })->visible(fn(callable $get) => $get('customer_type') === 'existing' && $get('user_id'))->columnSpanFull(),

            Grid::make(3)->schema([
                TextInput::make('customer_name')->label('Name')->maxLength(100)->required(fn(callable $get) => $get('customer_type') === 'new'),
                TextInput::make('customer_phone')->label('Phone')->tel()->unique(table: 'users', column: 'phone')->validationMessages(['unique' => 'This phone number is already registered.'])->required(fn(callable $get) => $get('customer_type') === 'new'),
                TextInput::make('customer_email')->label('Email (optional)')->email(),
            ])->visible(fn(callable $get) => $get('customer_type') === 'new'),
        ]);
    }

    // ── Shipping Address ─────────────────────────────────────────────────

    protected function addressSection(): Section
    {
        return Section::make('Shipping Address')->icon('heroicon-o-map-pin')->description('Choose one of the customer\'s saved addresses, or add a new delivery address.')->schema([
            Select::make('address_id')->label('Address')->options(function (callable $get) {
                $userId = $get('user_id');
                $options = [];

                if ($userId) {
                    foreach (Address::with(['division', 'district', 'upazila'])->where('user_id', $userId)->orderByDesc('is_default')->get() as $address) {
                        $label = "{$address->name} ({$address->phone}) — {$address->address_line}";

                        if ($address->district) {
                            $label .= ", {$address->district->name}";
                        }

                        if ($address->is_default) {
                            $label .= ' • Default';
                        }

                        $options[$address->id] = $label;
                    }
                }

                $options['new'] = '+ Add a new address';

                return $options;
            })->default('new')->live()->required()->native(false)->visible(fn(callable $get) => $get('customer_type') === 'existing' && $get('user_id'))->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('address_name')->label('Recipient name')->maxLength(100)->required(fn(callable $get) => $this->isNewAddress($get)),
                TextInput::make('address_phone')->label('Phone')->tel()->required(fn(callable $get) => $this->isNewAddress($get)),
                Select::make('address_division_id')->label('Division')->options(Division::pluck('name', 'id'))->searchable()->native(false)->live()->afterStateUpdated(function (callable $set) {
                    $set('address_district_id', null);
                    $set('address_upazila_id', null);
                }),

                Select::make('address_district_id')->label('District')->options(fn(callable $get) => $get('address_division_id') ? District::where('division_id', $get('address_division_id'))->pluck('name', 'id') : [])
                    ->searchable()->native(false)->live()->disabled(fn(callable $get) => !$get('address_division_id'))->afterStateUpdated(fn(callable $set) => $set('address_upazila_id', null)),
                Select::make('address_upazila_id')->label('Upazila')->options(fn(callable $get) => $get('address_district_id') ? Upazila::where('district_id', $get('address_district_id'))->pluck('name', 'id') : [])
                    ->searchable()->native(false)->disabled(fn(callable $get) => !$get('address_district_id')),
                TextInput::make('address_postal_code')->label('Postal code'),
                Select::make('address_type')->label('Address type')->options(['home' => 'Home', 'office' => 'Office', 'other' => 'Other'])->default('home')->native(false),
                Toggle::make('address_is_default')->label('Set as default address')->inline(false),
                Textarea::make('address_line')->label('Full address')->rows(2)->columnSpanFull()->required(fn(callable $get) => $this->isNewAddress($get)),
            ])->visible(fn(callable $get) => $this->isNewAddress($get)),
        ]);
    }

    protected function isNewAddress(callable $get): bool
    {
        return $get('customer_type') === 'new' || $get('address_id') === 'new';
    }

    // ── Order Items ──────────────────────────────────────────────────────

    protected function itemsSection(): Section
    {
        return Section::make('Order Items')->icon('heroicon-o-shopping-cart')->description('Add the products included in this order.')->schema([
            Repeater::make('items')->label('')->schema([
                Grid::make(12)->schema([
                    Select::make('product_id')->label('Product')->columnSpan(['default' => 12, 'md' => 5])->searchable()->getSearchResultsUsing(fn(string $search): array => Product::query()
                        ->where('is_active', true)->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))->limit(50)->get()->mapWithKeys(fn(Product $p) => [$p->id => "{$p->name} (SKU: {$p->sku}) — BDT " . number_format($p->price, 2)])->all())
                        ->getOptionLabelUsing(fn($value) => Product::find($value)?->name)->live()->required()->afterStateUpdated(function (callable $set, $state) {
                            $set('variant_id', null);

                            if ($product = Product::find($state)) {
                                $set('unit_price', (float)$product->price);
                            }
                        }),

                    Select::make('variant_id')
                        ->label('Variant')
                        ->columnSpan(['default' => 12, 'md' => 3])
                        ->options(function (callable $get) {
                            $product = Product::find($get('product_id'));

                            if (!$product || !$product->has_variants) {
                                return [];
                            }

                            return ProductVariant::where('product_id', $product->id)->where('is_active', true)->get()
                                ->mapWithKeys(function (ProductVariant $variant) {
                                    $attributes = collect($variant->attribute_values ?? [])
                                        ->map(fn($attr) => ($attr['attribute'] ?? '') . ': ' . ($attr['value'] ?? ''))
                                        ->filter()
                                        ->join(', ');

                                    return [$variant->id => ($attributes ?: $variant->sku) . ' — BDT ' . number_format($variant->price, 2)];
                                });
                        })
                        ->live()
                        ->visible(fn(callable $get) => Product::find($get('product_id'))?->has_variants ?? false)
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state && ($variant = ProductVariant::find($state))) {
                                $set('unit_price', (float)$variant->price);
                            }
                        }),

                    TextInput::make('quantity')
                        ->label('Qty')
                        ->columnSpan(['default' => 6, 'md' => 1])
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true)
                        ->helperText(function (callable $get) {
                            if ($get('variant_id')) {
                                return 'Stock: ' . (ProductVariant::find($get('variant_id'))?->quantity ?? 0);
                            }

                            if ($get('product_id')) {
                                return 'Stock: ' . (Product::find($get('product_id'))?->quantity ?? 0);
                            }

                            return null;
                        }),

                    TextInput::make('unit_price')
                        ->label('Unit price')
                        ->columnSpan(['default' => 6, 'md' => 2])
                        ->numeric()
                        ->prefix('BDT ')
                        ->default(0)
                        ->required()
                        ->live(onBlur: true),

                    Placeholder::make('line_total')
                        ->label('Total')
                        ->columnSpan(['default' => 12, 'md' => 1])
                        ->content(fn(callable $get) => 'BDT  ' . number_format(
                                (float)($get('quantity') ?? 0) * (float)($get('unit_price') ?? 0),
                                2
                            )),
                ]),
            ])
                ->itemLabel(fn(array $state): ?string => Product::find($state['product_id'] ?? null)?->name ?? 'New item')
                ->addActionLabel('Add product')
                ->reorderable(false)
                ->defaultItems(1)
                ->minItems(1)
                ->live()
                ->columnSpanFull(),
        ]);
    }

    // ── Order Settings ───────────────────────────────────────────────────

    protected function settingsSection(): Section
    {
        return Section::make('Order Settings')
            ->icon('heroicon-o-cog-6-tooth')
            ->columns(2)
            ->schema([
                Select::make('status')
                    ->label('Order status')
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'processing' => 'Processing',
                    ])
                    ->default('pending')
                    ->required()
                    ->native(false)
                    ->helperText('New admin orders are usually created as Pending or Confirmed.'),

                Select::make('payment_method')
                    ->label('Payment method')
                    ->options([
                        'cod'     => 'Cash on Delivery',
                        'zinipay' => 'ZiniPay',
                        'wallet'  => 'Wallet',
                    ])
                    ->default('cod')
                    ->required()
                    ->native(false),

                Select::make('payment_status')
                    ->label('Payment status')
                    ->options([
                        'pending'  => 'Pending',
                        'paid'     => 'Paid',
                        'failed'   => 'Failed',
                        'partial'  => 'Partial',
                        'refunded' => 'Refunded',
                    ])
                    ->default('pending')
                    ->required()
                    ->native(false),

                Select::make('delivery_type')
                    ->label('Delivery type')
                    ->options([
                        'standard' => 'Standard',
                        'express'  => 'Express',
                        'same_day' => 'Same Day',
                    ])
                    ->default('standard')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('shipping_charge', match ($state) {
                            'express'  => (float)setting('express_shipping_charge', 120),
                            'same_day' => (float)setting('same_day_shipping_charge', 150),
                            default    => (float)setting('standard_shipping_charge', 60),
                        });
                    }),

                TextInput::make('shipping_charge')
                    ->label('Shipping charge')
                    ->numeric()
                    ->prefix('BDT ')
                    ->default(fn() => (float)setting('standard_shipping_charge', 60))
                    ->required()
                    ->live(onBlur: true),

                TextInput::make('discount_amount')
                    ->label('Manual discount')
                    ->numeric()
                    ->prefix('BDT ')
                    ->default(0)
                    ->live(onBlur: true),

                TextInput::make('coupon_code')
                    ->label('Coupon code')
                    ->helperText('Optional — must be a valid, active coupon.')
                    ->live(onBlur: true),

                Textarea::make('customer_note')
                    ->label('Customer note')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('admin_note')
                    ->label('Admin note (internal)')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    // ── Order Summary ────────────────────────────────────────────────────

    protected function summarySection(): Section
    {
        return Section::make('Order Summary')
            ->icon('heroicon-o-receipt-percent')
            ->schema([
                Placeholder::make('order_summary')
                    ->label('')
                    ->content(function (callable $get) {
                        $items = $get('items') ?? [];
                        $subtotal = 0;
                        $itemCount = 0;

                        foreach ($items as $item) {
                            $qty = (float)($item['quantity'] ?? 0);
                            $price = (float)($item['unit_price'] ?? 0);
                            $subtotal += $qty * $price;
                            $itemCount += $qty;
                        }

                        $discount = (float)($get('discount_amount') ?? 0);
                        $shipping = (float)($get('shipping_charge') ?? 0);

                        $couponCode = $get('coupon_code');
                        $couponDiscount = 0;

                        if ($couponCode) {
                            $coupon = Coupon::where('code', $couponCode)->first();

                            if ($coupon && $coupon->isValid() && $subtotal >= $coupon->min_order_amount) {
                                $couponDiscount = $coupon->calculateDiscount($subtotal);
                            }
                        }

                        $total = max(0, $subtotal - $discount - $couponDiscount + $shipping);

                        return view('filament.resources.order.partials.create-order-summary', [
                            'itemCount'      => $itemCount,
                            'subtotal'       => $subtotal,
                            'discount'       => $discount,
                            'couponCode'     => $couponCode,
                            'couponDiscount' => $couponDiscount,
                            'shipping'       => $shipping,
                            'total'          => $total,
                        ]);
                    }),
            ]);
    }

    // ── Record Creation ──────────────────────────────────────────────────

    protected function handleRecordCreation(array $data): Model
    {
        // ── 1. Validate items & stock ────────────────────────────────────
        $itemsInput = $data['items'] ?? [];
        $itemsData = [];
        $subtotal = 0;
        $sellerId = null;

        foreach ($itemsInput as $item) {
            $product = Product::find($item['product_id'] ?? null);

            if (!$product) {
                Notification::make()->danger()->title('One of the selected products no longer exists.')->send();
                throw new Halt();
            }

            $variant = !empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $unitPrice = (float)($item['unit_price'] ?? ($variant?->price ?? $product->price));
            $stock = $variant ? $variant->quantity : $product->quantity;

            if ($stock < $quantity) {
                Notification::make()
                    ->danger()
                    ->title("Insufficient stock for \"{$product->name}\"")
                    ->body("Available: {$stock}, requested: {$quantity}")
                    ->send();
                throw new Halt();
            }

            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;
            $sellerId ??= $product->seller_id;

            $itemsData[] = [
                'product'       => $product,
                'variant'       => $variant,
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
                'compare_price' => $variant?->compare_price ?? $product->compare_price,
                'total_price'   => $lineTotal,
            ];
        }

        if (empty($itemsData)) {
            Notification::make()->danger()->title('Please add at least one product to the order.')->send();
            throw new Halt();
        }

        // ── 2. Coupon ─────────────────────────────────────────────────────
        $coupon = null;
        $couponDiscount = 0;

        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])->first();

            if (!$coupon || !$coupon->isValid()) {
                Notification::make()->danger()->title('Invalid or expired coupon code.')->send();
                throw new Halt();
            }

            if ($subtotal < $coupon->min_order_amount) {
                Notification::make()
                    ->danger()
                    ->title('Coupon requires a minimum order of BDT ' . number_format($coupon->min_order_amount, 0))
                    ->send();
                throw new Halt();
            }

            $couponDiscount = $coupon->calculateDiscount($subtotal);
        }

        $discountAmount = (float)($data['discount_amount'] ?? 0);
        $shippingCharge = (float)($data['shipping_charge'] ?? 0);
        $totalAmount = max(0, $subtotal - $discountAmount - $couponDiscount + $shippingCharge);

        $commissionRate = (float)setting('platform_commission', 10);
        $commissionBase = max(0, $subtotal - $discountAmount - $couponDiscount);
        $commissionAmount = $commissionBase * $commissionRate / 100;

        return DB::transaction(function () use ($data, $itemsData, $subtotal, $coupon, $couponDiscount, $discountAmount, $shippingCharge, $totalAmount, $commissionAmount, $sellerId) {
            // ── 3. Resolve / create customer ───────────────────────────────
            if (($data['customer_type'] ?? 'existing') === 'new') {
                $user = User::create([
                    'name'          => $data['customer_name'],
                    'phone'         => $data['customer_phone'],
                    'email'         => !empty($data['customer_email']) ? $data['customer_email'] : null,
                    'password'      => Hash::make(Str::random(12)),
                    'referral_code' => strtoupper(substr(md5($data['customer_phone'] . time()), 0, 8)),
                    'status'        => 'active',
                ]);
            } else {
                $user = User::findOrFail($data['user_id']);
            }

            // ── 4. Resolve / create shipping address ────────────────────────
            $isNewAddress = ($data['customer_type'] ?? 'existing') === 'new' || ($data['address_id'] ?? 'new') === 'new';

            if ($isNewAddress) {
                $makeDefault = ($data['address_is_default'] ?? false) || Address::where('user_id', $user->id)->doesntExist();

                if ($makeDefault) {
                    Address::where('user_id', $user->id)->update(['is_default' => false]);
                }

                $address = Address::create([
                    'user_id'      => $user->id,
                    'name'         => $data['address_name'] ?? $user->name,
                    'phone'        => $data['address_phone'] ?? $user->phone,
                    'type'         => $data['address_type'] ?? 'home',
                    'address_line' => $data['address_line'] ?? '',
                    'division_id'  => $data['address_division_id'] ?? null,
                    'district_id'  => $data['address_district_id'] ?? null,
                    'upazila_id'   => $data['address_upazila_id'] ?? null,
                    'postal_code'  => $data['address_postal_code'] ?? null,
                    'is_default'   => $makeDefault,
                ]);
            } else {
                $address = Address::where('id', $data['address_id'])->where('user_id', $user->id)->firstOrFail();
            }

            $address->loadMissing(['division', 'district', 'upazila']);

            // ── 5. Create the order ───────────────────────────────────────
            $order = Order::create([
                'user_id'              => $user->id,
                'seller_id'            => $sellerId,
                'coupon_id'            => $coupon?->id,
                'coupon_code'          => $coupon?->code,
                'status'               => 'pending',
                'payment_method'       => $data['payment_method'],
                'payment_status'       => $data['payment_status'],
                'subtotal'             => $subtotal,
                'discount_amount'      => $discountAmount,
                'coupon_discount'      => $couponDiscount,
                'shipping_charge'      => $shippingCharge,
                'total_amount'         => $totalAmount,
                'commission_amount'    => $commissionAmount,
                'seller_payout'        => max(0, ($subtotal - $discountAmount - $couponDiscount) - $commissionAmount),
                'shipping_name'        => $address->name,
                'shipping_phone'       => $address->phone,
                'shipping_address'     => $address->address_line,
                'shipping_division'    => $address->division?->name,
                'shipping_district'    => $address->district?->name,
                'shipping_upazila'     => $address->upazila?->name,
                'shipping_postal_code' => $address->postal_code,
                'delivery_type'        => $data['delivery_type'] ?? 'standard',
                'customer_note'        => $data['customer_note'] ?? null,
                'admin_note'           => $data['admin_note'] ?? null,
                'user_ip'              => request()->ip(),
                'user_device'          => 'admin_panel',
            ]);

            // ── 6. Create order items & decrement stock ─────────────────────
            foreach ($itemsData as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $variant = $item['variant'];

                $order->items()->create([
                    'product_id'      => $product->id,
                    'user_id'         => $user->id,
                    'variant_id'      => $variant?->id,
                    'product_name'    => $product->name,
                    'product_image'   => $product->primaryImage?->image_path ?? $product->images()->first()?->image_path,
                    'variant_options' => $variant?->attribute_values,
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'compare_price'   => $item['compare_price'],
                    'total_price'     => $item['total_price'],
                ]);

                $variant ? $variant->decrement('quantity', $item['quantity']) : $product->decrement('quantity', $item['quantity']);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $user->increment('total_orders');

            // ── 7. Wallet payment ───────────────────────────────────────────
            if (($data['payment_method'] ?? null) === 'wallet' && ($data['payment_status'] ?? null) === 'paid') {
                $user->decrement('wallet_balance', $totalAmount);
            }

            // ── 8. Status history ────────────────────────────────────────────
            $order->updateStatus('pending', 'Order created by admin', 'admin', auth()->id());

            $finalStatus = $data['status'] ?? 'pending';

            if ($finalStatus !== 'pending') {
                $order->updateStatus($finalStatus, 'Status set during order creation', 'admin', auth()->id());
            }

            return $order;
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order created successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\IpBlocksRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WishlistsRelationManager;
use Illuminate\Support\Facades\Password;
use App\Models\{Transaction, User};
use App\Services\{BlockingService, SmsService};
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\{RichEditor, Select, Textarea, TextInput, Toggle};
use Filament\Infolists\Components\{
    Grid,
    IconEntry,
    ImageEntry,
    Section,
    TextEntry,
    TextEntry\TextEntrySize,
};
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Mail;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getContentTabLabel(): ?string
    {
        return 'Profile';
    }

    public function getRelationManagers(): array
    {
        return [
            OrdersRelationManager::class,
            TransactionsRelationManager::class,
            AddressesRelationManager::class,
            ReviewsRelationManager::class,
            WishlistsRelationManager::class,
            IpBlocksRelationManager::class,
            UserResource\RelationManagers\ActivityLogsRelationManager::class
        ];
    }

    // ── Dynamic page title & subtitle ──────────────────────────────────

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        $status  = ucfirst($this->record->status);
        $joined  = $this->record->created_at->format('d M Y');
        $blocked = $this->record->is_blocked ? ' · BLOCKED' : '';
        $seller  = $this->record->seller ? ' · Seller' : '';
        return "{$this->record->phone} · {$status} · Joined {$joined}{$seller}{$blocked}";
    }

    // ── Infolist ──────────────────────────────────────────────────────

    public function infolist(Infolist $infolist): Infolist
    {
        $user = $this->record;

        return $infolist->schema([

            // ── Profile Hero ──────────────────────────────────────
            Section::make()->schema([
                Grid::make(5)->schema([

                    ImageEntry::make('avatar_url')
                        ->label('')
                        ->circular()
                        ->extraImgAttributes(['style' => 'width:80px;height:80px;object-fit:cover'])
                        ->columnSpan(1),

                    Grid::make(4)->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-m-user'),

                        TextEntry::make('phone')
                            ->label('Phone')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->copyMessage('Phone copied!'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'active'   => 'success',
                                'inactive' => 'warning',
                                'banned'   => 'danger',
                                default    => 'gray',
                            }),

                        TextEntry::make('gender')
                            ->label('Gender')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '—'),

                        TextEntry::make('date_of_birth')
                            ->label('Date of Birth')
                            ->date('d M Y')
                            ->icon('heroicon-m-cake')
                            ->placeholder('—'),

                        TextEntry::make('referral_code')
                            ->label('Referral Code')
                            ->copyable()
                            ->icon('heroicon-m-gift')
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime('d M Y')
                            ->icon('heroicon-m-calendar'),
                    ])->columnSpan(4),

                ]),
            ])->columnSpanFull(),

            // ── Stats Row ─────────────────────────────────────────
            Section::make('Activity Statistics')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Grid::make(6)->schema([
                        TextEntry::make('total_orders')
                            ->label('Total Orders')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->icon('heroicon-m-shopping-bag'),

                        TextEntry::make('completed_orders')
                            ->label('Completed')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->icon('heroicon-m-check-badge'),

                        TextEntry::make('cancel_order_count')
                            ->label('Cancelled')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn($state) => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'success'))
                            ->icon('heroicon-m-x-circle'),

                        TextEntry::make('failed_delivery_count')
                            ->label('Failed Deliveries')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn($state) => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'success'))
                            ->icon('heroicon-m-truck'),

                        TextEntry::make('no_answer_count')
                            ->label('No Answer')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn($state) => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'success'))
                            ->icon('heroicon-m-phone-x-mark'),

                        TextEntry::make('wallet_balance')
                            ->label('Wallet Balance')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('warning')
                            ->icon('heroicon-m-banknotes')
                            ->money('BDT'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('total_spent')
                            ->label('Total Spent')
                            ->money('BDT')
                            ->color('info')
                            ->placeholder('BDT 0.00'),

                        TextEntry::make('referred_by')
                            ->label('Referred By (User ID)')
                            ->placeholder('Direct signup'),

                        TextEntry::make('preferred_language')
                            ->label('Language')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                    ]),
                ])->columnSpanFull(),

            // ── Block Info (shown only when blocked) ──────────────
            Section::make('Block Information')
                ->icon('heroicon-o-no-symbol')
                ->schema([
                    Grid::make(4)->schema([
                        IconEntry::make('is_blocked')
                            ->label('Blocked')
                            ->boolean()
                            ->trueColor('danger')
                            ->falseColor('success'),

                        TextEntry::make('block_type')
                            ->label('Block Type')
                            ->badge()
                            ->color('danger')
                            ->placeholder('—'),

                        TextEntry::make('blocked_at')
                            ->label('Blocked At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('—'),

                        TextEntry::make('blockedBy.name')
                            ->label('Blocked By')
                            ->placeholder('Auto-system'),
                    ]),

                    TextEntry::make('blocked_reason')
                        ->label('Reason')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ])
                ->visible(fn() => $this->record->is_blocked)
                ->collapsible()
                ->columnSpanFull(),

            // ── Seller Info (shown only when user is a seller) ────
            Section::make('Seller Profile')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('seller.shop_name')
                            ->label('Shop Name')
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-m-building-storefront'),

                        TextEntry::make('seller.status')
                            ->label('Shop Status')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'active'   => 'success',
                                'inactive' => 'warning',
                                'banned'   => 'danger',
                                default    => 'gray',
                            }),

                        IconEntry::make('seller.is_verified')
                            ->label('Verified')
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger'),

                        IconEntry::make('seller.is_featured')
                            ->label('Featured')
                            ->boolean(),

                        TextEntry::make('seller.phone')
                            ->label('Shop Phone')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('seller.email')
                            ->label('Shop Email')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('seller.commission_rate')
                            ->label('Commission Rate')
                            ->suffix('%')
                            ->placeholder('—'),

                        TextEntry::make('seller.balance')
                            ->label('Seller Balance')
                            ->money('BDT')
                            ->color('success'),

                        TextEntry::make('seller.bkash_number')
                            ->label('bKash Number')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('seller.nid_number')
                            ->label('NID Number')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('seller.bank_name')
                            ->label('Bank')
                            ->placeholder('—'),

                        TextEntry::make('seller.bank_account')
                            ->label('Bank Account')
                            ->copyable()
                            ->placeholder('—'),
                    ]),

                    TextEntry::make('seller.address')
                        ->label('Shop Address')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->visible(fn() => (bool) $this->record->seller)
                ->collapsible()
                ->columnSpanFull(),

            // ── Device & Security ─────────────────────────────────
            Section::make('Device & Security')
                ->icon('heroicon-o-lock-closed')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('last_login_ip')
                            ->label('Last Login IP')
                            ->copyable()
                            ->icon('heroicon-m-globe-alt')
                            ->placeholder('—'),

                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime('d M Y, h:i A')
                            ->icon('heroicon-m-clock')
                            ->placeholder('—'),

                        TextEntry::make('device_type')
                            ->label('Device Type')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('d M Y')
                            ->placeholder('Not verified'),
                    ]),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpanFull(),
        ]);
    }

    // ── Header actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [

            // ── Send Email ──────────────────────────────────────────
            Action::make('send_email')
                ->label('Send Email')
                ->color('info')
                ->icon('heroicon-o-envelope')
                ->modalHeading('Send Email to ' . $this->record->name)
                ->modalWidth('2xl')
                ->form([
                    Select::make('template')
                        ->label('Email Template')
                        ->options([
                            'failed_order'    => 'Failed to Place Order',
                            'payment_failed'  => 'Payment Failed',
                            'payment_pending' => 'Payment Pending',
                            'order_shipped'   => 'Order Shipped',
                            'account_warning' => 'Account Warning',
                            'account_blocked' => 'Account Blocked',
                            'welcome'         => 'Welcome / Activation',
                            'wallet_credit'   => 'Wallet Credited',
                            'custom'          => 'Custom Email',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $templates = static::emailTemplates();
                            if ($state && isset($templates[$state])) {
                                $set('subject', $templates[$state]['subject']);
                                $set('body', '');
                            } else {
                                $set('subject', '');
                                $set('body', '');
                            }
                        })
                        ->placeholder('Choose a template or write custom…'),

                    TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Email subject line'),

                    RichEditor::make('body')
                        ->label('Message Body')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'h2', 'h3'])
                        ->placeholder('Write your email content here… (only needed for Custom Email)')
                        ->helperText('For named templates the content is loaded from a styled blade file automatically.'),
                ])
                ->action(function (array $data) {
                    $user     = $this->record;
                    $template = $data['template'] ?? 'custom';
                    $subject  = $data['subject'];

                    if (! $user->email) {
                        Notification::make()->danger()
                            ->title('No email address')
                            ->body('This user does not have an email address on file.')
                            ->send();
                        return;
                    }

                    $viewMap = [
                        'failed_order'    => 'emails.admin.failed_order',
                        'payment_failed'  => 'emails.admin.payment_failed',
                        'payment_pending' => 'emails.admin.payment_pending',
                        'order_shipped'   => 'emails.admin.order_shipped',
                        'account_warning' => 'emails.admin.account_warning',
                        'account_blocked' => 'emails.admin.account_blocked',
                        'welcome'         => 'emails.admin.welcome',
                        'wallet_credit'   => 'emails.admin.wallet_credit',
                    ];

                    if ($template !== 'custom' && isset($viewMap[$template])) {
                        $html = view($viewMap[$template], [
                            'user'    => $user,
                            'subject' => $subject,
                        ])->render();
                    } else {
                        $body = $data['body'] ?? '';
                        $html = str_replace(
                            ['{name}', '{phone}', '{email}', '{wallet_balance}'],
                            [$user->name, $user->phone, $user->email ?? '—', 'BDT ' . number_format($user->wallet_balance, 2)],
                            $body
                        );
                    }

                    Mail::html($html, function ($message) use ($user, $subject) {
                        $message->to($user->email, $user->name)->subject($subject);
                    });

                    Notification::make()->success()
                        ->title('Email sent!')
                        ->body("Email sent to {$user->email}")
                        ->send();
                })
                ->visible(fn() => (bool) $this->record->email),

            // ── Send Password Reset Link ────────────────────────────────
            Action::make('send_password_reset')
                ->label('Reset Password')
                ->color('gray')
                ->icon('heroicon-o-key')
                ->requiresConfirmation()
                ->modalHeading('Send Password Reset Link')
                ->modalDescription("This will send a password reset email to {$this->record->email}. The link will expire in 60 minutes.")
                ->action(function () {
                    $user = $this->record;

                    if (! $user->email) {
                        Notification::make()->danger()
                            ->title('No email address')
                            ->body('This user does not have an email address on file.')
                            ->send();
                        return;
                    }

                    $token    = Password::broker('users')->createToken($user);
                    $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

                    Mail::html(
                        view('emails.admin.password_reset', [
                            'user'     => $user,
                            'resetUrl' => $resetUrl,
                            'subject'  => 'Reset Your Password',
                        ])->render(),
                        fn($m) => $m->to($user->email, $user->name)->subject('Reset Your Password')
                    );

                    Notification::make()->success()
                        ->title('Reset link sent!')
                        ->body("Password reset email sent to {$user->email}")
                        ->send();
                })
                ->visible(fn() => (bool) $this->record->email),

            // ── Send SMS ────────────────────────────────────────────
            Action::make('send_sms')
                ->label('Send SMS')
                ->color('warning')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->modalHeading('Send SMS to ' . $this->record->name)
                ->form([
                    Select::make('template')
                        ->label('Quick Template')
                        ->options([
                            'order_placed'    => 'Order Placed Successfully',
                            'order_shipped'   => 'Order Shipped',
                            'order_delivered' => 'Order Delivered',
                            'payment_pending' => 'Payment Reminder',
                            'account_warning' => 'Account Warning',
                            'custom'          => 'Custom Message',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            $msgs = [
                                'order_placed'    => "Dear {$record->name}, your order has been successfully placed. Thank you!",
                                'order_shipped'   => "Dear {$record->name}, your order has been shipped and will arrive soon.",
                                'order_delivered' => "Dear {$record->name}, your order has been delivered. Thank you!",
                                'payment_pending' => "Dear {$record->name}, your payment has not been completed yet. Please complete the payment.",
                                'account_warning' => "Dear {$record->name}, a warning has been issued on your account.",
                            ];
                            $set('message', $msgs[$state] ?? '');
                        })
                        ->placeholder('Choose a template or write custom…'),

                    Textarea::make('message')
                        ->label('SMS Message')
                        ->required()
                        ->rows(4)
                        ->maxLength(500)
                        ->helperText('Variables available: {name}, {phone}'),
                ])
                ->action(function (User $record, array $data) {
                    $message = str_replace(['{name}', '{phone}'], [$record->name, $record->phone], $data['message']);
                    app(SmsService::class)->send($record->phone, $message);
                    Notification::make()->success()->title('SMS sent to ' . $record->phone)->send();
                }),

            // ── Add / Deduct Wallet ─────────────────────────────────
            Action::make('wallet')
                ->label('Wallet')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->modalHeading('Manage Wallet — ' . $this->record->name)
                ->modalDescription('Current balance: BDT ' . number_format($this->record->wallet_balance, 2))
                ->form([
                    Select::make('operation')
                        ->label('Operation')
                        ->options(['credit' => 'Add Credit', 'debit' => 'Deduct'])
                        ->default('credit')
                        ->required(),

                    TextInput::make('amount')
                        ->label('Amount (BDT )')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->prefix('BDT '),

                    Select::make('reason_preset')
                        ->label('Reason (Quick Pick)')
                        ->options([
                            'Refund for cancelled order'     => 'Refund — cancelled order',
                            'Compensation for delay'         => 'Compensation — delivery delay',
                            'Cashback reward'                => 'Cashback reward',
                            'Referral bonus'                 => 'Referral bonus',
                            'Manual correction'              => 'Manual correction',
                            'Promotional credit'             => 'Promotional credit',
                            'Penalty for order cancellation' => 'Penalty — order cancellation',
                            'Other'                          => 'Other (type below)',
                        ])
                        ->live()
                        ->afterStateUpdated(fn($state, callable $set) => $set('reason', $state))
                        ->placeholder('Pick a reason…'),

                    TextInput::make('reason')
                        ->label('Custom Reason')
                        ->maxLength(255)
                        ->placeholder('Or type your own reason…'),
                ])
                ->action(function (User $record, array $data) {
                    $op     = $data['operation'];
                    $amount = (float) $data['amount'];
                    $reason = $data['reason'] ?: ($data['reason_preset'] ?? 'Admin adjustment');
                    $before = $record->wallet_balance;

                    if ($op === 'credit') {
                        $record->increment('wallet_balance', $amount);
                    } else {
                        if ($record->wallet_balance < $amount) {
                            Notification::make()->danger()->title('Insufficient balance')->send();
                            return;
                        }
                        $record->decrement('wallet_balance', $amount);
                    }

                    $after = $record->fresh()->wallet_balance;

                    Transaction::create([
                        'user_id'        => $record->id,
                        'type'           => $op,
                        'amount'         => $amount,
                        'balance_before' => $before,
                        'balance_after'  => $after,
                        'description'    => "Admin {$op}: {$reason}",
                        'status'         => 'completed',
                    ]);

                    $label = $op === 'credit' ? 'added to' : 'deducted from';
                    Notification::make()->success()
                        ->title("BDT {$amount} {$label} wallet")
                        ->body("New balance: BDT " . number_format($after, 2))
                        ->send();
                }),

            // ── Block ───────────────────────────────────────────────
            Action::make('block')
                ->label('Block User')
                ->color('danger')
                ->icon('heroicon-o-no-symbol')
                ->requiresConfirmation()
                ->modalHeading('Block ' . $this->record->name)
                ->modalDescription('This will immediately restrict the user from placing orders and logging in.')
                ->form([
                    Select::make('reason_preset')
                        ->label('Block Reason')
                        ->options([
                            'Too many order cancellations'       => 'Too many cancellations',
                            'Repeated no-answer on delivery'     => 'Repeated no-answer on delivery',
                            'Fraudulent activity detected'       => 'Fraudulent activity',
                            'Abusive behavior towards support'   => 'Abusive behavior',
                            'Failed payment — multiple attempts' => 'Payment fraud',
                            'Fake/duplicate account'             => 'Fake / duplicate account',
                            'Violation of terms of service'      => 'Terms of service violation',
                            'Other'                              => 'Other (specify below)',
                        ])
                        ->live()
                        ->afterStateUpdated(fn($state, callable $set) => $set('reason', $state))
                        ->placeholder('Select a reason…'),

                    Textarea::make('reason')
                        ->label('Detailed Reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('Provide a detailed reason for blocking…'),

                    Select::make('duration')
                        ->label('Block Duration')
                        ->options([
                            '7 days'    => '7 Days',
                            '30 days'   => '30 Days',
                            '90 days'   => '90 Days',
                            '180 days'  => '6 Months',
                            'permanent' => 'Permanent',
                        ])
                        ->default('permanent')
                        ->required(),

                    Toggle::make('block_ip')
                        ->label('Also block IP address')
                        ->helperText('Last known IP: ' . ($this->record->last_login_ip ?? 'Unknown'))
                        ->default(true),

                    Toggle::make('send_notification')
                        ->label('Send block notification to user (SMS/notification)')
                        ->default(false),
                ])
                ->action(function (User $record, array $data) {
                    app(BlockingService::class)->blockUser(
                        $record,
                        $data['reason'],
                        auth()->id(),
                        $data['block_ip'] ?? true,
                        $data['duration']
                    );
                    Notification::make()->danger()
                        ->title("{$record->name} has been blocked")
                        ->body("Duration: {$data['duration']}")
                        ->send();
                })
                ->visible(fn() => ! $this->record->is_blocked),

            // ── Unblock ─────────────────────────────────────────────
            Action::make('unblock')
                ->label('Unblock User')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Unblock ' . $this->record->name . '?')
                ->modalDescription('The user will regain full access to the platform.')
                ->action(function (User $record) {
                    app(BlockingService::class)->unblockUser($record, auth()->id());
                    Notification::make()->success()->title("{$record->name} has been unblocked")->send();
                })
                ->visible(fn() => $this->record->is_blocked),

            EditAction::make()->label('Edit Profile'),
        ];
    }

    // ── Email Template Library ────────────────────────────────────────

    protected static function emailTemplates(): array
    {
        return [
            'failed_order'    => ['subject' => 'Your order could not be completed — we want to help'],
            'payment_failed'  => ['subject' => 'Payment Failed — Please Retry Your Order'],
            'payment_pending' => ['subject' => 'Action Required: Your Payment is Pending'],
            'order_shipped'   => ['subject' => 'Your Order Has Been Shipped'],
            'account_warning' => ['subject' => 'Important: Account Warning Notice'],
            'account_blocked' => ['subject' => 'Your Account Has Been Suspended'],
            'welcome'         => ['subject' => 'Welcome to Our Platform!'],
            'wallet_credit'   => ['subject' => 'Your Wallet Has Been Credited'],
        ];
    }
}

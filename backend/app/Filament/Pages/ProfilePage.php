<?php

namespace App\Filament\Pages;

use App\Models\AdminUser;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\FileUpload};
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\Rules\Password;

class ProfilePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $title = 'Edit Profile';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.profile';

    public array $profileData = [];
    public array $passwordData = [];

    public function mount(): void
    {
        /** @var AdminUser $admin */
        $admin = Auth::user();

        $this->profileForm->fill([
            'name'   => $admin->name,
            'email'  => $admin->email,
            'phone'  => $admin->phone,
            'avatar' => $admin->avatar,
        ]);
    }

    protected function getForms(): array
    {
        return ['profileForm', 'passwordForm'];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(' Personal Information')
                    ->description('Update your profile information')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Profile Picture')
                            ->image()
                            ->directory('avatars')
                            ->imageEditor()
                            ->circleCropper()
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(AdminUser::class, 'email', Auth::user())
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(' Change Password')
                    ->description('Use a strong password for better security')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(),

                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::defaults())
                            ->same('password_confirmation'),

                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveProfile')
                ->label(' Save Profile')
                ->color('success')
                ->action('saveProfile'),

            Action::make('changePassword')
                ->label(' Change Password')
                ->color('warning')
                ->action('changePassword'),
        ];
    }

    public function saveProfile(): void
    {
        $data = $this->profileForm->getState();

        /** @var AdminUser $admin */
        $admin = Auth::user();

        $admin->update([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'phone'  => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? $admin->avatar,
        ]);

        Notification::make()
            ->success()
            ->title('Profile updated successfully!')
            ->send();
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm->getState();

        /** @var AdminUser $admin */
        $admin = Auth::user();

        $admin->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordForm->fill([]);

        Notification::make()
            ->success()
            ->title('Password changed successfully!')
            ->send();
    }
}

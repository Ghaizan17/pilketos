<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

/**
 * Login admin memakai username (bukan email).
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->placeholder('Masukkan username Anda')
            ->required()
            ->maxLength(255)
            ->autocomplete()
            ->autofocus()
            ->prefixIcon('heroicon-o-user');
    }

    protected function getCredentialsFromFormData(#[\SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'Masuk Panel Admin';
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'Pilketos SMKN 2 Banjarmasin — khusus panitia dan operator bilik.';
    }
}

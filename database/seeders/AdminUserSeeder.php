<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the operator account.
     *
     * Login: operatorskenda / operatorskenda123
     * NOTE: force-change this password after first login (Filament profile
     * or via tinker: User::where('username', 'operatorskenda')->update(['password' => '...'])).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'operatorskenda'],
            [
                'name' => 'Operator Skenda',
                'email' => 'operator@pilketos.local',
                // Hashed by the User model's 'hashed' cast — store plain here.
                'password' => 'operatorskenda123',
            ],
        );

        User::updateOrCreate(
            ['username' => 'operatorscan'],
            [
                'name' => 'Operator Scan Bilik',
                'email' => 'scan@pilketos.local',
                'password' => 'operatorscan123',
            ],
        );

        $this->command?->info('Admin login: operatorskenda / operatorskenda123 (change after first login).');
        $this->command?->info('Booth login: operatorscan / operatorscan123 (change after first login).');
    }
}

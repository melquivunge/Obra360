<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        if (app()->isProduction() && empty($adminPassword)) {
            throw new \RuntimeException('SEED_ADMIN_PASSWORD must be set before seeding the production admin user.');
        }

        if (empty($adminPassword)) {
            $adminPassword = Str::password(20);
            $this->command?->warn('Generated temporary admin password for local seed: '.$adminPassword);
        }

        User::factory()->create([
            'name' => 'Administrador',
            'email' => env('SEED_ADMIN_EMAIL', 'admin+'.Str::lower(Str::random(8)).'@obra360.test'),
            'password' => Hash::make($adminPassword),
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        Setting::set('company_name', 'Obra360');
        Setting::set('company_email', 'contato@obra360.com.br');
        Setting::set('company_phone', '');
        Setting::set('allow_comments', '1');
        Setting::set('show_client_financials', '1');
        Setting::set('currency', 'BRL');
    }
}

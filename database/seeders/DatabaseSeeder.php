<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@obra360.test',
            'password' => Hash::make($adminPassword ?: 'password'),
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);

        \App\Models\Setting::set('company_name', 'Obra360');
        \App\Models\Setting::set('company_email', 'contato@obra360.com.br');
        \App\Models\Setting::set('company_phone', '');
        \App\Models\Setting::set('allow_comments', '1');
        \App\Models\Setting::set('show_client_financials', '1');
        \App\Models\Setting::set('currency', 'BRL');
    }
}

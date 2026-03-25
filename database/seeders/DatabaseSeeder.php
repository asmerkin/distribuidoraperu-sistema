<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::create([
            'name' => 'Admin',
            'email' => 'admin@distribuidoraperu.com',
            'password' => bcrypt('password'),
        ]);

        \App\Models\Location::create(['name' => 'Depósito Principal']);

        \App\Models\Setting::set('company_name', 'Distribuidora Perú');
        \App\Models\Setting::set('company_address', 'Mendoza, Argentina');
        \App\Models\Setting::set('company_phone', '');
        \App\Models\Setting::set('company_tax_id', '');
        \App\Models\Setting::set('company_email', '');
        \App\Models\Setting::set('po_reply_to_email', '');
    }
}

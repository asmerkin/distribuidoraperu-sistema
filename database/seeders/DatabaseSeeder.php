<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Setting;
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

        Location::create(['name' => 'Depósito Principal']);

        Setting::set('company_name', 'Distribuidora Perú');
        Setting::set('company_address', 'Mendoza, Argentina');
        Setting::set('company_phone', '');
        Setting::set('company_tax_id', '');
        Setting::set('company_email', '');
        Setting::set('po_reply_to_email', '');
    }
}

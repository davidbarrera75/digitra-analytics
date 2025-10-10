<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Digitra Analytics',
            'email' => 'admin@digitra.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);
    }
}

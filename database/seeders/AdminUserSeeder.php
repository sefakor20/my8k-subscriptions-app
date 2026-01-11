<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $adminExists = User::where('email', 'admin@example.com')->exists();

        if ($adminExists) {
            $this->command->info('Admin user already exists');

            return;
        }

        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->command->info('Admin user created successfully');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
        $this->command->warn('⚠️  Please change the admin password after first login!');
    }
}

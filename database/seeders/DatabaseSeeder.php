<?php

namespace Database\Seeders;

use App\Http\Modules\Auth\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::unguarded(function (): void {
            User::query()->updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'is_active' => true,
                    'username' => 'admin',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                ],
            );
        });
    }
}

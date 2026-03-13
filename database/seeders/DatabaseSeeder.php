<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@alumni.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            TrackingConfigSeeder::class,
            DummyAlumniSeeder::class,
        ]);
    }
}

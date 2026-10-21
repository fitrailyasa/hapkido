<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ChampionshipSeeder::class,
        ]);

        $users = [
            ['name' => 'Administrator', 'email' => 'test@example.com', 'role' => 'Administrator'],
            ['name' => 'Operator 01', 'email' => 'operator@example.com', 'role' => 'Operator'],
            ['name' => 'Coach ITERA', 'email' => 'coach@example.com', 'role' => 'Coach'],
            ['name' => 'Manajer ITERA', 'email' => 'manajer@example.com', 'role' => 'Manajer Tim'],
        ];

        foreach ($users as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$row['role']]);
        }
    }
}

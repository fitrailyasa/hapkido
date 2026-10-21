<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed only what a real installation needs.
 *
 * The demo data of ChampionshipSeeder (classes, athletes, schedules, ...) is
 * for local development only, so a machine that gets the installer runs this
 * seeder instead: the roles and the first administrator account.
 */
class ProductionSeeder extends Seeder
{
    /**
     * Login of the first administrator, created on the very first run.
     */
    protected string $adminEmail = 'admin@hapkido.local';

    /**
     * Password of the first administrator. Change it right after the first
     * login (menu Pengaturan / User).
     */
    protected string $adminPassword = 'password';

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $user = User::firstOrCreate(
            ['email' => $this->adminEmail],
            [
                'name' => 'Administrator',
                'password' => bcrypt($this->adminPassword),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $user->syncRoles(['Administrator']);

        $this->command?->info("Akun admin: {$this->adminEmail} / {$this->adminPassword}");
    }
}

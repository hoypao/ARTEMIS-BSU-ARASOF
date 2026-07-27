<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * ARTEMIS has no seeders.
 *
 * The schema and its reference data (application types, talent categories,
 * settings, and the demo accounts) are created by the migrations in
 * database/migrations/, which port the legacy ARTEMIS SQL verbatim — so
 * `php artisan migrate` is the entire setup step.
 *
 * This class previously called User::factory() with Laravel's default
 * name/email/password columns. Those do not exist on the legacy `users` table
 * (it uses first_name/last_name/password_hash), so `db:seed` always failed.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}

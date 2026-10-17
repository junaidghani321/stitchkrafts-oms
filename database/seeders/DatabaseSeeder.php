<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: the four role accounts, and nothing
     * else. Orders, tasks, revisions and quotes are created through the app.
     *
     * Note: WithoutModelEvents is deliberately NOT used here. Order numbers
     * are stamped by OrderObserver and the activity timeline is written on
     * model events, so muting them would seed rows with neither.
     */
    public function run(): void
    {
        $this->call([
            CreateUsersSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\User;
use App\Models\File;
use App\Models\Comment;
use App\Models\ActivityLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting seed...');
        DB::connection()->disableQueryLog();

        /**
         * Organizations
         */
        $this->command->info('Creating organizations...');
        $orgs = collect();

        for ($i = 0; $i < 100; $i++) {
            $orgs->push(
                Organization::create([
                    'name' => "Company $i",
                    'plan' => fake()->randomElement(['free', 'pro', 'enterprise']),
                ])
            );
        }

        /**
         * Users (10k)
         * Let Postgres auto-generate UUID (DEFAULT)
         */
        $this->command->info('Creating users...');
        $batchSize = 1000;
        $users = [];

        for ($i = 0; $i < 10000; $i++) {
            $users[] = [
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
                'organization_id' => $orgs->random()->id,
                'avatar_url' => "https://i.pravatar.cc/150?img={$i}",
                'last_login' => now()->subDays(rand(0, 30)),
                'metadata' => json_encode([
                    'role' => fake()->randomElement(['designer', 'developer', 'manager'])
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($users) === $batchSize) {
                DB::table('users')->insert($users);
                $users = [];
                $this->command->info("Inserted {$i} users");
            }
        }

        if ($users) {
            DB::table('users')->insert($users);
        }

        /**
         * Build user-org map (UUID SAFE)
         */
        $this->command->info('Preparing user-org map...');
        $userOrgMap = DB::table('users')
            ->select('id', 'organization_id')
            ->get()
            ->groupBy('organization_id');

        /**
         * Files (100k)
         */
        $this->command->info('Creating files...');
        $files = [];
        $batchSize = 1000;

        for ($i = 0; $i < 100000; $i++) {
            $org = $orgs->random();
            $usersInOrg = $userOrgMap[$org->id] ?? collect();

            if ($usersInOrg->isEmpty()) {
                continue;
            }

            $ownerId = $usersInOrg->random()->id; // UUID string

            $files[] = [
                'name' => "Design File {$i}",
                'owner_id' => $ownerId,
                'organization_id' => $org->id,
                'version' => rand(1, 50),
                'is_public' => fake()->boolean(30),
                'view_count' => rand(0, 10000),
                'last_modified' => now()->subDays(rand(0, 90)),
                'metadata' => json_encode([
                    'type' => fake()->randomElement(['design', 'prototype', 'component'])
                ]),
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ];

            if (count($files) === $batchSize) {
                DB::table('files')->insert($files);
                $files = [];
                $this->command->info("Inserted {$i} files");
            }
        }

        if ($files) {
            DB::table('files')->insert($files);
        }

        /**
         * Done
         */
        $this->command->info('Seed completed!');
        $this->command->table(
            ['Table', 'Count'],
            [
                ['organizations', Organization::count()],
                ['users', User::count()],
                ['files', File::count()],
                ['comments', Comment::count()],
                ['activity_logs', ActivityLog::count()],
            ]
        );
    }
}

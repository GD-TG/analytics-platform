<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\YandexAccount;
use App\Models\YandexCounter;
use App\Models\Project;
use App\Models\MetricsMonthly;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Database\Seeders\DevSampleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with test data.
     */
    public function run(): void
    {
        echo "🌱 Seeding database with test data...\n";

        // Create test users
        $user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        echo "✓ Created 2 test users\n";

        // Create projects for users
        $project1 = Project::create([
            'user_id' => $user1->id,
            'name' => 'Ecommerce Site',
            'description' => 'Main e-commerce platform analytics',
        ]);

        $project2 = Project::create([
            'user_id' => $user2->id,
            'name' => 'SaaS Dashboard',
            'description' => 'Analytics dashboard for SaaS product',
        ]);

        echo "✓ Created 2 projects\n";

        // Create Yandex counters (these are attached to projects, not accounts)
        $counter1 = YandexCounter::create([
            'counter_id' => 12345678,
            'project_id' => $project1->id,
            'name' => 'ecommerce.ru',
            'is_primary' => true,
        ]);

        $counter2 = YandexCounter::create([
            'counter_id' => 87654321,
            'project_id' => $project2->id,
            'name' => 'saas-app.io',
            'is_primary' => true,
        ]);

        echo "✓ Created 2 Yandex counters\n";

        // Create sample monthly metrics
        $now = Carbon::now();
        for ($i = 0; $i < 3; $i++) {
            $month = $now->copy()->subMonths($i);

            MetricsMonthly::create([
                'project_id' => $project1->id,
                'year' => $month->year,
                'month' => $month->month,
                'visits' => rand(1000, 5000),
                'users' => rand(500, 2500),
                'pageviews' => rand(2000, 8000),
                'bounce_rate' => rand(30, 60),
                'avg_session_duration_sec' => rand(60, 300),
                'conversions' => rand(10, 100),
            ]);

            MetricsMonthly::create([
                'project_id' => $project2->id,
                'year' => $month->year,
                'month' => $month->month,
                'visits' => rand(500, 3000),
                'users' => rand(250, 1500),
                'pageviews' => rand(1000, 5000),
                'bounce_rate' => rand(25, 50),
                'avg_session_duration_sec' => rand(120, 400),
                'conversions' => rand(5, 50),
            ]);
        }

        echo "✓ Created 6 months of sample metrics\n";

        // Create demo Yandex accounts (with encrypted tokens)
        // NOTE: These are DEMO tokens - not real, just for testing structure
        $account1 = YandexAccount::create([
            'user_id' => $user1->id,
            'provider_user_id' => 'demo_yandex_user_1',
            'access_token' => 'demo_access_token_1', // Will be encrypted by mutator
            'refresh_token' => 'demo_refresh_token_1', // Will be encrypted by mutator
            'scopes' => json_encode(['login:info', 'metrika:read']),
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        $account2 = YandexAccount::create([
            'user_id' => $user2->id,
            'provider_user_id' => 'demo_yandex_user_2',
            'access_token' => 'demo_access_token_2',
            'refresh_token' => 'demo_refresh_token_2',
            'scopes' => json_encode(['login:info', 'metrika:read']),
            'expires_at' => Carbon::now()->addDays(30),
            'revoked' => false,
        ]);

        echo "✓ Created 2 demo Yandex accounts (encrypted tokens)\n";

        echo "\n✅ Database seeding completed!\n";
        echo "\n📋 Test credentials:\n";
        echo "  User 1: test1@example.com / password123\n";
        echo "  User 2: test2@example.com / password123\n";
        echo "\n";

        // Dev sample: project + metrics + direct + seo entries for quick testing
        $this->call(DevSampleSeeder::class);
    }
}

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

        // ...auth/user/yandex demo data removed...
        echo "\n✅ Database seeding completed!\n";

        // Dev sample: project + metrics + direct + seo entries for quick testing
        $this->call(DevSampleSeeder::class);
    }
}

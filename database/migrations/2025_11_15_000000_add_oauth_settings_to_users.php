<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Yandex Metrika OAuth credentials
            $table->string('yandex_metrika_client_id')->nullable()->after('email');
            $table->string('yandex_metrika_client_secret')->nullable()->after('yandex_metrika_client_id');
            
            // Yandex Direct OAuth credentials
            $table->string('yandex_direct_client_id')->nullable()->after('yandex_metrika_client_secret');
            $table->string('yandex_direct_client_secret')->nullable()->after('yandex_direct_client_id');
            
            // Sync settings
            $table->integer('sync_interval_minutes')->default(60)->after('yandex_direct_client_secret');
            $table->boolean('sync_enabled')->default(true)->after('sync_interval_minutes');
            
            // Add indexes for faster lookups
            $table->index('yandex_metrika_client_id');
            $table->index('yandex_direct_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['yandex_metrika_client_id']);
            $table->dropIndex(['yandex_direct_client_id']);
            $table->dropColumn([
                'yandex_metrika_client_id',
                'yandex_metrika_client_secret',
                'yandex_direct_client_id',
                'yandex_direct_client_secret',
                'sync_interval_minutes',
                'sync_enabled',
            ]);
        });
    }
};

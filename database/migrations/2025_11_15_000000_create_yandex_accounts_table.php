<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yandex_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider_user_id')->nullable()->index();
            $table->string('counter_id')->nullable()->index();
            $table->text('encrypted_access_token')->nullable();
            $table->text('encrypted_refresh_token')->nullable();
            $table->text('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yandex_accounts');
    }
};

<?php 
use Illuminate\Database\Migrations\Migration; 
use Illuminate\Database\Schema\Blueprint; 
use Illuminate\Support\Facades\Schema; 
 
return new class extends Migration 
{ 
    public function up(): void 
    { 
        Schema::create('yandex_counters', function (Blueprint $table) { 
            $table->id(); 
            $table->foreignId('project_id')->constrained()->onDelete('cascade'); 
            $table->bigInteger('counter_id'); 
            $table->string('name')->nullable(); 
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_fetched_at')->nullable()->index();
            $table->timestamps(); 
        }); 
    } 
 
    public function down(): void 
    { 
        Schema::dropIfExists('yandex_counters'); 
    } 
}; 

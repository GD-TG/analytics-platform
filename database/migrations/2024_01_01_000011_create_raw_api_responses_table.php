<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('raw_api_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            // Источник данных (yandex_metrika, yandex_direct, etc)
            $table->string('source', 50);
            
            // Эндпоинт API
            $table->string('endpoint')->nullable();
            
            // Сырые данные ответа
            $table->json('response_data')->nullable();
            
            // Параметры запроса
            $table->json('request_params')->nullable();
            
            // HTTP код ответа
            $table->integer('response_code')->nullable();
            
            // Время обработки
            $table->timestamp('processed_at')->nullable();
            
            // Сообщение об ошибке (если есть)
            $table->text('error_message')->nullable();
            
            // Индексы для оптимизации запросов
            $table->index(['source', 'processed_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['response_code']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('raw_api_responses');
    }
};
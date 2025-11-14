@echo off
chcp 65001 >nul

echo 📦 Создание модели RawApiResponse...

:: Создаем модель
echo ^<?php > "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo namespace App\Models; >> "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo use Illuminate\Database\Eloquent\Factories\HasFactory; >> "app\Models\RawApiResponse.php"
echo use Illuminate\Database\Eloquent\Model; >> "app\Models\RawApiResponse.php"
echo use Illuminate\Database\Eloquent\Relations\BelongsTo; >> "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo class RawApiResponse extends Model >> "app\Models\RawApiResponse.php"
echo { >> "app\Models\RawApiResponse.php"
echo     use HasFactory; >> "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo     protected \$fillable = [ >> "app\Models\RawApiResponse.php"
echo         'project_id', 'source', 'endpoint', 'response_data', >> "app\Models\RawApiResponse.php"
echo         'request_params', 'response_code', 'processed_at', 'error_message' >> "app\Models\RawApiResponse.php"
echo     ]; >> "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo     protected \$casts = [ >> "app\Models\RawApiResponse.php"
echo         'response_data' =^> 'array', >> "app\Models\RawApiResponse.php"
echo         'request_params' =^> 'array', >> "app\Models\RawApiResponse.php"
echo         'processed_at' =^> 'datetime' >> "app\Models\RawApiResponse.php"
echo     ]; >> "app\Models\RawApiResponse.php"
echo. >> "app\Models\RawApiResponse.php"
echo     public function project(): BelongsTo >> "app\Models\RawApiResponse.php"
echo     { >> "app\Models\RawApiResponse.php"
echo         return \$this-^>belongsTo(Project::class); >> "app\Models\RawApiResponse.php"
echo     } >> "app\Models\RawApiResponse.php"
echo } >> "app\Models\RawApiResponse.php"

echo.
echo ✅ Модель RawApiResponse создана!
echo 📋 Дальнейшие шаги:
echo    1. Создай миграцию: php artisan make:migration create_raw_api_responses_table
echo    2. Скопируй код миграции из сообщения выше
echo    3. Выполни миграцию: php artisan migrate
echo.
pause
<?php

namespace App\Jobs\Fetch;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\YandexCounter;
use App\Models\RawApiResponse;
use App\Services\Metrika\MetrikaClient;
use App\Services\Metrika\MetrikaFetcher;
use App\Jobs\Process\ParseMetrikaResponseJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class FetchMetrikaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $counter;
    public $startDate;
    public $endDate;
    public $tries = 3;
    public $timeout = 300;
    public $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(YandexCounter $counter, Carbon $startDate, Carbon $endDate)
    {
        $this->counter = $counter;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Настройки очереди
        $this->onQueue('metrika-fetch');
    }

    /**
     * Execute the job.
     */
    public function handle(MetrikaClient $metrikaClient, MetrikaFetcher $metrikaFetcher, \App\Services\Yandex\YandexTokenService $tokenService): void
    {
        try {
            Log::info("Starting Metrika fetch for counter {$this->counter->counter_id}", [
                'counter_id' => $this->counter->counter_id,
                'project_id' => $this->counter->project_id,
                'period' => $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d')
            ]);

            // Подготавливаем Authorization заголовок: ищем YandexAccount по counter_id
            $account = \App\Models\YandexAccount::where('counter_id', $this->counter->counter_id)->first();
            $accessToken = null;
            if ($account) {
                $accessToken = $tokenService->getAccessTokenFor($account);
            }

            // fallback к глобальному токену из конфига
            if (!$accessToken) {
                $accessToken = Config::get('integrations.yandex.oauth_token') ?: Config::get('metrika.api_token');
            }

            if ($accessToken) {
                $metrikaClient->setHeaders(['Authorization' => 'OAuth ' . $accessToken]);
            } else {
                Log::warning('No Yandex access token available for counter ' . $this->counter->counter_id);
            }

            // Получаем данные визитов и сессий
            $visitsData = $metrikaFetcher->fetchVisitsData(
                $this->counter->counter_id,
                $this->startDate,
                $this->endDate
            );

            // Получаем данные по возрастным группам
            $ageData = $metrikaFetcher->fetchAgeData(
                $this->counter->counter_id,
                $this->startDate,
                $this->endDate
            );

            // Получаем данные по целям
            $goalsData = $metrikaFetcher->fetchGoalsData(
                $this->counter->counter_id,
                $this->startDate,
                $this->endDate
            );

            // Сохраняем сырые данные в базу
            $rawResponse = RawApiResponse::create([
                'project_id' => $this->counter->project_id,
                'source' => 'yandex_metrika',
                'endpoint' => 'visits,age,goals',
                'response_data' => [
                    'visits' => $visitsData,
                    'age' => $ageData,
                    'goals' => $goalsData
                ],
                'request_params' => [
                    'counter_id' => $this->counter->counter_id,
                    'start_date' => $this->startDate->toIso8601String(),
                    'end_date' => $this->endDate->toIso8601String()
                ]
            ]);

            // Запускаем обработку данных
            ParseMetrikaResponseJob::dispatch($rawResponse)
                ->onQueue('metrika-process');

            Log::info("Successfully fetched Metrika data for counter {$this->counter->counter_id}", [
                'counter_id' => $this->counter->counter_id,
                'raw_response_id' => $rawResponse->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch Metrika data for counter {$this->counter->counter_id}", [
                'counter_id' => $this->counter->counter_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Повторяем job через некоторое время
            $this->release(60);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error("FetchMetrikaJob failed for counter {$this->counter->counter_id}", [
            'counter_id' => $this->counter->counter_id,
            'project_id' => $this->counter->project_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Можно отправить уведомление администратору
        // Notification::send(/* admin */, new SyncFailedNotification($this->counter, $exception));
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'metrika',
            'fetch',
            'counter:' . $this->counter->counter_id,
            'project:' . $this->counter->project_id
        ];
    }
}
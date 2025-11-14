<?php

namespace App\Jobs\Fetch;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\DirectAccount;
use App\Models\RawApiResponse;
use App\Services\Direct\DirectClient;
use App\Services\Direct\DirectFetcher;
use App\Jobs\Process\ParseDirectResponseJob;
use Carbon\Carbon;

class FetchDirectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $directAccount;
    public $startDate;
    public $endDate;
    public $tries = 3;
    public $timeout = 300;
    public $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(DirectAccount $directAccount, Carbon $startDate, Carbon $endDate)
    {
        $this->directAccount = $directAccount;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Настройки очереди
        $this->onQueue('direct-fetch');
    }

    /**
     * Execute the job.
     */
    public function handle(DirectClient $directClient, DirectFetcher $directFetcher): void
    {
        try {
            Log::info("Starting Direct fetch for account {$this->directAccount->client_login}", [
                'account_id' => $this->directAccount->id,
                'project_id' => $this->directAccount->project_id,
                'client_login' => $this->directAccount->client_login,
                'period' => $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d')
            ]);

            // Устанавливаем Client-Login для этого аккаунта
            $directClient->setClientLogin($this->directAccount->client_login);

            // Получаем данные по кампаниям
            $campaignsData = $directFetcher->fetchCampaignsData(
                $this->directAccount,
                $this->startDate,
                $this->endDate
            );

            // Получаем итоговые данные
            $totalsData = $directFetcher->fetchTotalsData(
                $this->directAccount,
                $this->startDate,
                $this->endDate
            );

            // Сохраняем сырые данные в базу
            $rawResponse = RawApiResponse::create([
                'project_id' => $this->directAccount->project_id,
                'source' => 'yandex_direct',
                'endpoint' => 'campaigns,totals',
                'response_data' => [
                    'campaigns' => $campaignsData,
                    'totals' => $totalsData
                ],
                'request_params' => [
                    'direct_account_id' => $this->directAccount->id,
                    'client_login' => $this->directAccount->client_login,
                    'start_date' => $this->startDate->toISOString(),
                    'end_date' => $this->endDate->toISOString()
                ]
            ]);

            // Запускаем обработку данных
            ParseDirectResponseJob::dispatch($rawResponse)
                ->onQueue('direct-process');

            Log::info("Successfully fetched Direct data for account {$this->directAccount->client_login}", [
                'account_id' => $this->directAccount->id,
                'raw_response_id' => $rawResponse->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch Direct data for account {$this->directAccount->client_login}", [
                'account_id' => $this->directAccount->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Повторяем job через некоторое время
            throw $e;
        }
    }
}

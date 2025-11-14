<?php

namespace App\Jobs\Fetch;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Direct\DirectClient;

class FetchDirectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $directClient;

    public function __construct(DirectClient $directClient)
    {
        $this->directClient = $directClient;
    }

    public function handle()
    {
        // Используем $this->directClient
        $campaigns = $this->directClient->getCampaigns();
        // ...
    }
}
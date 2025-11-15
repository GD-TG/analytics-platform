<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectCampaignMonthly extends Model
{
    protected $table = 'direct_campaign_monthly';

    protected $fillable = [
        'project_id',
        'direct_campaign_id',
        'year',
        'month',
        'impressions',
        'clicks',
        'ctr_pct',
        'cpc',
        'conversions',
        'cpa',
        'cost'
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'ctr_pct' => 'decimal:6,2',
        'cpc' => 'decimal:12,2',
        'conversions' => 'integer',
        'cpa' => 'decimal:12,2',
        'cost' => 'decimal:14,2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function directCampaign(): BelongsTo
    {
        return $this->belongsTo(DirectCampaign::class);
    }
}

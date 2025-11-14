<?php 
namespace App\Models; 
 
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; 
 
class Project extends Model 
{ 
    protected $table = 'projects'; 
 
    protected $fillable = [ 
        'name', 'slug', 'timezone', 'currency', 'is_active' 
    ]; 
 
    protected $casts = [ 
        'is_active' => 'boolean', 
    ]; 
 
    public function yandexCounters(): HasMany 
    { 
        return $this->hasMany(YandexCounter::class); 
    }

    public function counters(): HasMany
    {
        return $this->yandexCounters();
    } 
 
    public function directAccounts(): HasMany 
    { 
        return $this->hasMany(DirectAccount::class); 
    } 
 
    public function metricsMonthly(): HasMany 
    { 
        return $this->hasMany(MetricsMonthly::class); 
    } 
 
    public function metricsAgeMonthly(): HasMany 
    { 
        return $this->hasMany(MetricsAgeMonthly::class); 
    } 
 
    public function directTotalsMonthly(): HasMany 
    { 
        return $this->hasMany(DirectTotalsMonthly::class); 
    } 
 
    public function seoQueriesMonthly(): HasMany 
    { 
        return $this->hasMany(SeoQueriesMonthly::class); 
    }

    /**
     * Scope для получения только активных проектов
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
} 

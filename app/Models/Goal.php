<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'external_id',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

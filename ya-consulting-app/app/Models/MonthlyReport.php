<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'name',
        'month',
        'year',
        'total_budget',
        'total_expenses',
        'net_profit',
        'file_path',
        'file_type',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'month'          => 'integer',
        'year'           => 'integer',
        'total_budget'   => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit'     => 'decimal:2',
        'generated_at'   => 'datetime',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

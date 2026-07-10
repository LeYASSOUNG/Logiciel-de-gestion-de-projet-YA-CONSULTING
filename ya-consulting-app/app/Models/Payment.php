<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'encrypted',
        'payment_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

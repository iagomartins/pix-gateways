<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'bank_account',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_account' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class, 'transaction_id')
            ->where('transaction_type', 'withdraw');
    }
}


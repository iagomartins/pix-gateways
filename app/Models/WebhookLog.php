<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'transaction_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function pixTransaction()
    {
        return $this->belongsTo(Pix::class, 'transaction_id')
            ->where('transaction_type', 'pix');
    }

    public function withdrawTransaction()
    {
        return $this->belongsTo(Withdraw::class, 'transaction_id')
            ->where('transaction_type', 'withdraw');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    use HasFactory;

    protected $table = 'pix_transactions';

    protected $fillable = [
        'user_id',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'payer_name',
        'payer_cpf',
        'qr_code',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
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
            ->where('transaction_type', 'pix');
    }
}


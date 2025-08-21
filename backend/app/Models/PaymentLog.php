<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway', 
        'payload', 
        'status',
    ];

    protected $casts = [
        'payload' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

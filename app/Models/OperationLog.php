<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperationLog extends Model
{
    use HasFactory;

    protected $fillable = ['user', 'ip', 'request', 'response', 'response_code', 'method', 'user_agent', 'time', 'path'];

    protected $casts = [
        'time'    => 'decimal:6',
        'request' => 'array',
    ];
}

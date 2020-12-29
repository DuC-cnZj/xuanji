<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfigTips extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'creator'];
}

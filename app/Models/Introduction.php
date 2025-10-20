<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Introduction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'introduced_from_id',
        'introduced_from_email',
        'introduced_from_first_name',
        'introduced_from_last_name',
        'introduced_id',
        'introduced_email',
        'introduced_first_name',
        'introduced_last_name',
        'introduced_status',
        'introduced_is_attempt',
        'introduced_message',
        'introduced_to_id',
        'introduced_to_email',
        'introduced_to_first_name',
        'introduced_to_last_name',
        'introduced_to_status',
        'introduced_to_is_attempt',
        'introduced_to_message',
        'over_all_status',
        'request_status',
        'message',
        'reminder_message',
        'revoke',
    ];

    protected $casts = [
        'introduced_is_attempt' => 'boolean',
        'introduced_to_is_attempt' => 'boolean',
        'revoke' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}



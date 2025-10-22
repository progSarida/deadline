<?php

namespace App\Models;

use App\Enums\Timespan;
use Illuminate\Database\Eloquent\Model;

class Deadline extends Model
{
    protected $fillable = [
        'contact_type',
        'client_id',
        'date',
        'time',
        'note',
        'outcome_type',
        'user_id',
    ];

    protected $casts = [
        'contact_type' => Timespan::class,
    ];

    public function scope()
    {
        return $this->belongsTo(ScopeType::class);
    }

    public function registrationUser()
    {
        return $this->belongsTo(User::class, 'registration_user_id');
    }

    protected static function booted()
    {
        static::creating(function ($contact) {
            //
        });

        static::created(function ($contact) {
            //
        });

        static::updating(function ($contact) {
            //
        });

        static::updated(function ($contact) {
            //
        });

        static::saved(function ($contact) {
            //
        });

        static::deleting(function ($contact) {
            //
        });

        static::deleted(function ($contact) {
            //
        });
    }
}

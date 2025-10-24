<?php

namespace App\Models;

use App\Enums\Timespan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Deadline extends Model
{
    protected $fillable = [
        'scope_type_id',
        'deadline_date',
        'recurrent',
        'quantity',
        'timespan',
        'description',
        'met',
        'met_date',
        'met_user_id',
        'note',
        'insert_user_id',
        'modify_user_id',
        'renew',
    ];

    protected $casts = [
        'timespan' => Timespan::class,
        'recurrent' => 'boolean',
        'met' => 'boolean',
        'renew' => 'boolean',
    ];

    public function insertUser()
    {
        return $this->belongsTo(User::class, 'insert_user_id');
    }

    public function modifyUser()
    {
        return $this->belongsTo(User::class, 'modify_user_id');
    }

    public function metUser()
    {
        return $this->belongsTo(User::class, 'met_user_id');
    }

    public function scopeType()
    {
        return $this->belongsTo(ScopeType::class, 'scope_type_id');
    }

    // query per filtro ambiti assegnati
    public function scopeUserTypes($query)
    {
        $scopes = ScopeType::join('user_scope_type', 'scope_types.id', '=', 'user_scope_type.scope_type_id')
                    ->where('user_scope_type.user_id', '=', Auth::user()->id)
                    ->pluck('scope_types.id');

        if(Auth::user()->is_admin) return $query;                                   // se l'utente è admin vede tutte le scadenze
        else return $query->whereIn('scope_type_id', $scopes);                      // altrimenti vede solo quelle per cui ha dei permessi
    }

    protected static function booted()
    {
        static::creating(function ($deadline) {
            $deadline->insert_user_id = Auth::user()->id;                           // salvo l'id dell'utente che ha inserito la scadenza
        });

        static::created(function ($deadline) {
            //
        });

        static::updating(function ($deadline) {
            //
        });

        static::updated(function ($deadline) {
            //
        });

        static::saving(function ($deadline) {
            $deadline->modify_user_id = Auth::user()->id;                           // salvo l'id dell'utente che per ultimo ha modificato la scadenza
            if($deadline->met){                                                     // se la scadenza è segnata rispettata
                $deadline->met_user_id = Auth::user()->id;                          // salvo l'id dell'utente che ha segnato rispettata la scadenza
            } else {
                $deadline->met_user_id = null;
            }
        });

        static::saved(function ($deadline) {
            //
        });

        static::deleting(function ($deadline) {
            //
        });

        static::deleted(function ($deadline) {
            //
        });
    }
}

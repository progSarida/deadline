<?php

namespace App\Models;

use App\Enums\Timespan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Deadline extends Model
{
    protected $fillable = [
        'prev_deadline_id',
        'scope_type_id',
        'deadline_date',
        'deadline_time',
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
        'deadline_date' => 'date',
        'deadline_time' => 'datetime:H:i:s',
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

    // scadenza da cui è stata rinnovata questa scadenza
    public function prevDeadline()
    {
        return $this->belongsTo(Deadline::class, 'prev_deadline_id');
    }

    // scadenze nate dal rinnovo di questa scadenza
    public function nextDeadlines()
    {
        return $this->hasMany(Deadline::class, 'prev_deadline_id');
    }

    // true se questa scadenza è già stata rinnovata (esiste una scadenza che la indica come precedente)
    public function hasNextDeadline(): bool
    {
        return $this->nextDeadlines()->exists();
    }

    /**
     * Id di tutte le scadenze della serie periodica a cui appartiene questa scadenza
     * (risale la catena dei prev_deadline_id fino alla prima e poi ridiscende i rinnovi).
     *
     * @return array<int>
     */
    public function seriesIds(): array
    {
        // risalgo la catena fino alla prima scadenza della serie
        $root = $this;
        $walked = [$root->id => true];

        while ($root->prev_deadline_id && !isset($walked[$root->prev_deadline_id])) {
            $prev = static::find($root->prev_deadline_id);
            if (!$prev) {
                break;
            }
            $walked[$prev->id] = true;
            $root = $prev;
        }

        // ridiscendo la catena dei rinnovi partendo dalla prima scadenza
        $ids = [$root->id];
        $level = [$root->id];

        while ($level) {
            $children = static::whereIn('prev_deadline_id', $level)->pluck('id')->all();
            $children = array_values(array_diff($children, $ids));                      // evito loop su catene incoerenti

            if (!$children) {
                break;
            }

            $ids = array_merge($ids, $children);
            $level = $children;
        }

        return $ids;
    }

    // query per filtro ambiti assegnati
    public function scopeUserTypes($query)
    {
        $scopes = ScopeType::join('user_scope_type', 'scope_types.id', '=', 'user_scope_type.scope_type_id')
                    ->where('user_scope_type.user_id', '=', Auth::user()->id)
                    ->pluck('scope_types.id');

        // if(Auth::user()->is_admin) return $query;                                   // se l'utente è admin vede tutte le scadenze
        if(Auth::user()->hasRole('super_admin')) return $query;                     // se l'utente è admin vede tutte le scadenze
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
            // if($deadline->met){                                                     // se la scadenza è segnata rispettata
            //     $deadline->met_user_id = Auth::user()->id;                          // salvo l'id dell'utente che ha segnato rispettata la scadenza
            // } else {
            //     $deadline->met_user_id = null;
            // }
        });

        static::saved(function ($deadline) {
            //
        });

        static::deleting(function ($deadline) {
            if ($deadline->hasNextDeadline()) {
                return false;                                                       // blocco l'eliminazione: la scadenza è già stata rinnovata (ultima difesa, l'interfaccia lo impedisce prima)
            }

            $prev = Deadline::find($deadline->prev_deadline_id);
            if ($prev) {
                $prev->renew = false;
                $prev->save();
            }
        });

        static::deleted(function ($deadline) {
            //
        });
    }
}

<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['team_id','user_id','category_id', 'display_id', 'name', 'description', 'currency_code', 'index', 'archivable', 'archived'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(team::class);
    }

}

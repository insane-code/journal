<?php

namespace Insane\Journal\Models\Core;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransactionLine extends Model
{
    protected $fillable = ['team_id','user_id','category_id', 'account_id', 'concept', 'amount', 'index', 'anchor', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }


    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function category() {
        return $this->belongsTo(Account::class);
    }
}

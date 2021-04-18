<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Model;

class TransactionLine extends Model
{
    protected $fillable = ['team_id','user_id','category_id', 'account_id', 'concept', 'amount', 'index', 'anchor'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(team::class);
    }


    public function account() {
        return $this->belongsTo('Insane\Journal\Account');
    }

    public function category() {
        return $this->belongsTo('Insane\Journal\Category');
    }
}

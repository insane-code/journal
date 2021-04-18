<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Model;

class TransactionLine extends Model
{
    protected $fillable = ['team_id','user_id','category_id', 'display_id', 'name', 'description', 'currency_code', 'index', 'archivable', 'archived'];
    protected $with = ['account', 'category'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(team::class);
    }


    public function account() {
        return $this->belongsTo('Insane/Journal/Account', 'id', 'account_id');
    }

    public function category() {
        return $this->belongsTo('Insane/Journal/Category', 'id', 'category_id');
    }
}

<?php

namespace Insane\Journal;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['team_id','user_id','category_id', 'client_id', 'display_id', 'name', 'description', 'currency_code', 'index', 'archivable', 'archived'];

    protected static function booted()
    {
        static::creating(function ($account) {
            if (is_string($account->category_id)) {
                $account->category_id = Category::findOrCreateByName($account->category_id);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function lastTransactionDate() {
        return $this->hasOneThrough(Transaction::class, TransactionLine::class, 'account_id', 'id')->orderByDesc('date')->limit(1);
    }
}

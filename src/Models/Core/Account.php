<?php

namespace Insane\Journal\Models\Core;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public static function guessAccount($session, $labels, $type = "DEBIT") {

        $accountSlug = Str::slug($labels[0], "_");
        $account = Account::where(['user_id' => $session->user_id, 'display_id' => $accountSlug])->limit(1)->get();
        if (count($account)) {
            return $account[0]->id;
        } else {
            $account = Account::create([
                'user_id' => $session->user_id,
                'team_id' => $session->team_id,
                'display_id' => $accountSlug,
                'name' => $labels[0],
                'description' => $labels[0],
                'currency_code' => "DOP",
            ]);

            return $account->id;
        }
    }
}

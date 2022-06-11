<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payee extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'name', 'account_id'];

    protected static function booted()
    {
        static::creating(function ($payee) {
            $payee->account_id = $payee->account_id ?? Payee::createContactAccount($payee);
        });
    }

    public static function findOrCreateByName($session, string $name) {
        $payee = Payee::where(
            [
                'name' => $name,
                'team_id' => $session['team_id'],
            ])->limit(1)->get();

        if (!count($payee)) {
            return Payee::create([
                'name' => $name,
                'user_id' => $session['user_id'],
                'team_id' => $session['team_id'],
            ]);
        } else {
            return $payee->first();
        }
    }

    public static function createContactAccount($payee)
    {

        $account = Account::where([
                'client_id' => $payee->id,
                'name' => "payments: $payee->name",
                'team_id' => $payee->team_id
            ])->first();
        if ($account) {
           return $account->id;
        } else {
           $account = Account::create([
                "team_id" => $payee->team_id,
                "user_id" => $payee->user_id,
                "display_id" => "General",
                "name" => "payments: $payee->name",
                "currency_code" => "DOP"
            ]);
            return $account->id;
        }

    }
}

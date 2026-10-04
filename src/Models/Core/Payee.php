<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
            $teamId = $session['team_id'];

            // Honor payee aliases (a merged-away name resolves to its canonical
            // payee) when the host app provides the table, so a re-imported or
            // re-entered name doesn't re-create a duplicate that was merged away.
            if (\Illuminate\Support\Facades\Schema::hasTable('payee_aliases')) {
                $alias = \Illuminate\Support\Facades\DB::table('payee_aliases')
                    ->where('team_id', $teamId)
                    ->where('name', $name)
                    ->first();
                if ($alias) {
                    $aliased = Payee::where('team_id', $teamId)->find($alias->payee_id);
                    if ($aliased) {
                        return $aliased;
                    }
                }
            }

            return Payee::create([
                'name' => $name,
                'user_id' => $session['user_id'],
                'team_id' => $session['team_id'],
            ]);
        } else {
            $payee = $payee->first();
            if ($payee->account_id) {

            }
            return $payee;
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
           $accountName = "payments: $payee->name";
           $clientTrustType = AccountDetailType::where([
                'name' => 'client_trust_account',
            ])->first();

           $account = Account::create([
                "team_id" => $payee->team_id,
                "user_id" => $payee->user_id,
                "display_id" => Str::slug($accountName),
                "name" => $accountName,
                "account_detail_type_id" => $clientTrustType?->id ?? 1,
                "currency_code" => "DOP"
            ]);
            return $account->id;
        }

    }
}

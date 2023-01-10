<?php

namespace Insane\Journal\Models\Core;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use \Znck\Eloquent\Traits\BelongsToThrough;

class TransactionLine extends Model
{
    use BelongsToThrough;

    protected $appends = [
        'total',
        'description',
        'direction',
        'status',
    ];

    protected $with = [
        'payee',
        'category',
        'accountFrom',
        'accountTo'
    ];

    protected $fillable = [
        'team_id',
        'user_id',
        'category_id',
        'account_id',
        'payee_id',
        'date',
        'concept',
        'amount',
        'index',
        'anchor',
        'type'
    ];

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


    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionable() {
        return $this->hasOneThrough('transactionable', Transaction::class);
    }

    public function accountFrom() {
        return $this->belongsToThrough(Account::class, Transaction::class, );
    }

    public function accountTo() {
        return $this->belongsToThrough(Account::class, Transaction::class, null, '', [
            Account::class => 'counter_account_id'
        ]);
    }

    public function payee()
    {
        return $this->belongsToThrough(Payee::class, Transaction::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function getTotalAttribute() {
        return $this->transaction?->total;
    }

    public function getDescriptionAttribute() {
        return $this->transaction?->description;
    }

    public function getDirectionAttribute() {
        return $this->type == 1 ?  Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT;
    }

    public function getStatusAttribute() {
        return $this->transaction?->status;
    }
}

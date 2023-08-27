<?php

namespace Insane\Journal\Models\Accounting;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Core\TransactionLine;

class Reconciliation extends Model
{
    const STATUS_COMPLETED = 'completed';
    const STATUS_PENDING = 'pending';

    protected $fillable = [
        'team_id',
        'user_id',
        'account_id',
        'date',
        'amount',
        'difference',
        'status',
    ];

    public function entries() {
        return $this->hasMany(ReconciliationEntry::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function createEntries($items = []) {
        ReconciliationEntry::query()->where('reconciliation_id', $this->id)->delete();
        if (!count($items)) {
            throw new Exception("Missing transactions");
        }

        foreach ($items as $item) {
            $this->entries()->create([
                'user_id' => $this->id,
                'team_id' => $this->team_id,
                'reconciliation_id' => $this->id,
                'transaction_id' => $item->transaction_id,
                'transaction_line_id' => $item->id,
                'matched' => $this->status == self::STATUS_COMPLETED,
            ]);
        }

        if ($this->status == self::STATUS_COMPLETED) {
            TransactionLine::whereIn('id', collect($items)->pluck('id'))->update([
                'matched' => true
            ]);
        }
    }
    
    public function getTransactions($limit = 15) {

        return Transaction::whereHas('lines', function ($query) {
            $query->where('account_id', $this->account_id);
        })
        ->join('reconciliation_entries', 'transactions.id', 'reconciliation_entries.transaction_id')
        ->with(['splits','payee', 'category', 'splits.payee','account', 'counterAccount'])
        ->orderByDesc('date')
        ->limit($limit)
        ->get();
    }
}

<?php

namespace Insane\Journal\Models\Accounting;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    public function scopePending($query) {
        return $query->where('status', self::STATUS_PENDING);
    }


    public function entries() {
        return $this->hasMany(ReconciliationEntry::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function createEntries($items = []) {
        ReconciliationEntry::query()->where('reconciliation_id', $this->id)->delete();
        if (!count($items)) {
           return;
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

    public function addEntries($items = []) {
        if (!count($items)) {
            return;
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

    public function addEntry($item) {
        $this->entries()->create([
            'user_id' => $this->id,
            'team_id' => $this->team_id,
            'reconciliation_id' => $this->id,
            'transaction_id' => $item->transaction_id,
            'transaction_line_id' => $item->id,
            'matched' => $this->status == self::STATUS_COMPLETED,
        ]);

        if($this->difference == 0) {
            $this->update(['status' => Reconciliation::STATUS_COMPLETED]);
        }

        if ($this->status == self::STATUS_COMPLETED) {
            TransactionLine::whereIn('id', collect($items)->pluck('id'))->update([
                'matched' => true
            ]);
        }
    }

    public function checkStatus() {
        $entries = $this->entries()->select([
            'id',
            'transaction_id',
            'transaction_line_id',
        ])->get();

        $isMatched = $this->status == self::STATUS_COMPLETED;


        ReconciliationEntry::whereIn('id', collect($entries)->pluck('id'))->update([
            'matched' => $isMatched
        ]);

        TransactionLine::whereIn('id', collect($entries)->pluck('transaction_line_id'))->update([
            'matched' => $isMatched
        ]);
    }

    public function getTransactions($limit = 15, $page) {
        return Transaction::whereHas('lines', function ($query) {
            $query->where('account_id', $this->account_id);
        })
        ->join('reconciliation_entries', fn($q) => $q->on('transactions.id', 'reconciliation_entries.transaction_id')
        ->where('reconciliation_id', $this->id))
        ->with(['splits','payee', 'category', 'splits.payee','account', 'counterAccount'])
        ->select()
        ->addSelect(DB::raw('reconciliation_entries.id as entry_id, reconciliation_entries.matched is_matched'))
        ->orderByDesc('date')
        ->paginate($limit);
    }
}

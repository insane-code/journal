<?php

namespace Insane\Journal;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['team_id','user_id','client_id', 'date','due_date','series','concept','number', 'description', 'direction', 'notes', 'total', 'subtotal', 'discount'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->series = $invoice->series ?? substr($invoice->date, 0, 4);
            self::setNumber($invoice);
        });

        static::saving(function ($invoice) {
            Invoice::calculateTotal($invoice);
            Invoice::checkPayments($invoice);
            $invoice->account_id = Invoice::createClientAccount($invoice);
        });

        static::saved(function (Invoice $invoice) {
            $invoice->createTransaction();
        });

        static::deleting(function ($invoice) {
            $invoice->status = 'draft';
            $invoice->save();
            foreach ($invoice->lines as $item) {
                if ($item->product) {
                    $item->product->updateStock();
                }
            }

            Payment::where('invoice_id', $invoice->id)->delete();
            InvoiceLine::where('invoice_id', $invoice->id)->delete();
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction() {
        return $this->morphOne(Transaction::class, "transactionable");
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }


    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    //  Utils
    public static function setNumber($invoice)
    {
        $isInvalidNumber = true;

        if ($invoice->number) {
            $isInvalidNumber = Invoice::where([
                "team_id" => $invoice->team_id,
                "series" => $invoice->series,
                "number" => $invoice->number,
            ])->whereNot([
                "id" => $invoice->id
            ]);

            $isInvalidNumber = count($isInvalidNumber);
        }

        if ($isInvalidNumber) {
            $result = Invoice::where([
                "team_id" => $invoice->team_id,
                "series" => $invoice->series,
            ])->max('number');
            $invoice->number = $result + 1;
        }
    }

    public static function calculateTotal($invoice)
    {
        $result = [
            'subtotal' => 0,
            'total' => 0,
            'discount' => 0
        ];

        if ($invoice) {
            $total = InvoiceLine::where(["invoice_id" =>  $invoice->id])->selectRaw('sum(price) as price, sum(discount) as discount, sum(amount) as amount')->get();
            $result['subtotal'] = $total[0]['price'] ?? 0;
            $result['discount'] = $total[0]['discount'] ?? 0;
            $result['total'] =  $total[0]['amount'] ?? 0;
            Invoice::where(['id' => $invoice->id])->update($result);
        }
    }

    public function createLines($items)
    {
        InvoiceLine::query()->where('invoice_id', $this->id)->delete();
        foreach ($items as $item) {
            $this->lines()->create([
                "team_id" => $this->team_id,
                "user_id" => $this->user_id,
                "concept" => $item['concept'],
                "index" => $item['index'],
                "product_id" => $item['product_id'] ?? null,
                "quantity" => $item['quantity'],
                "price" => $item['price'],
                "amount" => $item['amount'],
                "team_id" => $this->team_id
            ]);
        }
    }

    // accounting

    public static function createClientAccount($invoice)
    {
        $accounts = Account::where('client_id', $invoice->client_id)->limit(1)->get();
        if (count($accounts)) {
           return $accounts[0]->id;
        } else {
           $account = Account::create([
                "team_id" => $invoice->team_id,
                "client_id" => $invoice->client_id,
                "user_id" => $invoice->user_id,
                "category_id" => 18,
                "display_id" => "client_{$invoice->user_id}_{$invoice->user->names}",
                "name" => "Payment from {$invoice->client->names}",
                "currency_code" => "DOP"
            ]);
            return $account->id;
        }

    }

    public static function checkPayments($invoice)
    {
        if ($invoice && $invoice->payments) {
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->debt = $invoice->total - $totalPaid;
            $invoice->status = Invoice::checkStatus($invoice);
        }
    }

    public static function checkStatus($invoice)
    {
        $status = $invoice->status;
        if ($invoice->debt == 0) {
            $status = 'paid';
        } elseif ($invoice->debt > 0 && $invoice->debt < $invoice->total) {
            $status = 'partial';
        } elseif ($invoice->debt) {
            $status = 'unpaid';
        } else {
            $status = 'draft';
        }

        return $status;
    }

    public function createPayment($formData)
    {
        $formData['amount'] = $formData['amount'] > $this->debt ? $this->debt : $formData['amount'];

        return $this->payments()->create(array_merge(
            $formData,
            [
                'user_id' => $this->user_id,
                'team_id' => $this->team_id,
                'client_id' => $this->client_id
            ]
        ));
    }

    public function deletePayment($id)
    {
        Payment::find($id)->delete();
    }

    public function createTransaction() {
        $transactionData = [
            "team_id" => $this->team_id,
            "user_id" => $this->user_id,
            "date" => $this->date,
            "description" => $this->concept,
            "direction" => "DEPOSIT",
            "total" => $this->total,
            "account_id" => $this->account_id,
            "category_id" => 10
        ];
        if (!$this->transaction) {
            $transaction = $this->transaction()->create($transactionData);
        } else {
            $this->transaction->update($transactionData);
            $transaction = $this->transaction;
        }
        $transaction->createLines($transactionData, []);
    }
}

<?php

namespace Insane\Journal\Models\Invoice;

use App\Models\Client;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Jobs\Invoice\CreateInvoiceLine;
use Insane\Journal\Jobs\Invoice\CreateInvoiceTransaction;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Transaction;
use Illuminate\Support\Facades\Bus;

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
            $invoice->invoice_account_id = Invoice::createInvoiceAccount($invoice);
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

    public function account_client()
    {
        return Account::where('client_id', $this->client_id)->limit(1)->get()[0];
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
            ])->get();

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
            $totalTax = InvoiceLineTax::where(["invoice_id" =>  $invoice->id])->selectRaw('sum(amount) as amount')->get();
            $result['subtotal'] = $total[0]['price'] ?? 0;
            $discount = $total[0]['discount'] ?? 0;
            $taxTotal = $totalTax[0]['amount'] ?? 0;
            $invoiceTotal =  ($total[0]['amount'] ?? 0);
            $result['total'] = $invoiceTotal - $discount + $taxTotal;
            Invoice::where(['id' => $invoice->id])->update($result);
        }
    }

    public static function createDocument($invoiceData) {
        DB::transaction(function () use ($invoiceData) {
            $invoice = self::create($invoiceData);
            Bus::chain([
                new CreateInvoiceLine($invoice, $invoiceData),
                new CreateInvoiceTransaction($invoice, array_merge(
                    $invoice->toArray(),
                    [
                        'transactionType' => 'invoice',
                        'direction' => 'DEPOSIT',
                        'account_id' => $invoice->account_id,
                        'date' => $invoice->date,
                        'description' => $invoice->concept,
                        'total' => $invoice->total,
                    ]
                )),
            ])->dispatch();
            return $invoice;
        });
    }

    // accounting

    public static function createClientAccount($invoice)
    {
        $accounts = Account::where('client_id', $invoice->client_id)->limit(1)->get();
        if (count($accounts)) {
           return $accounts[0]->id;
        } else {
           $category = Category::where('display_id', 'expected_payments_customers')->first();
           $account = Account::create([
                "team_id" => $invoice->team_id,
                "client_id" => $invoice->client_id,
                "user_id" => $invoice->user_id,
                "category_id" => $category->id,
                "display_id" => "client_{$invoice->client_id}_{$invoice->client->names}",
                "name" => "{$invoice->client->names} Account",
                "currency_code" => "DOP"
            ]);
            return $account->id;
        }

    }

    public static function createInvoiceAccount($invoice)
    {
        $accounts = Account::where([
                'display_id' =>  'sales',
                'team_id' => $invoice->team_id
        ])->limit(1)->get();
        if (count($accounts)) {
           return $accounts[0]->id;
        } else {
           $category = Category::where('display_id', 'income')->first();
           $account = Account::create([
                "team_id" => $invoice->team_id,
                "client_id" => 0,
                "user_id" => $invoice->user_id,
                "category_id" => $category->id,
                "display_id" => "sales",
                "name" => "Sales",
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

    public function markAsPaid() 
    {
        if ($this->debt <= 0) {
            throw new Exception("This invoice is already paid");
        }

        $formData = [
            "amount" => $this->debt,
            "payment_date" => date("Y-m-d"),
            "concept" => "Payment for invoice #{$this->number}",
            "account_id" => Account::guessAccount($this, ['cash_on_hand']),
            "category_id" => $this->account_id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'client_id' => $this->client_id,
            'currency_code' => $this->currency_code,
            'currency_rate' => $this->currency_rate,
            'status' => 'verified'
        ];

        $this->payments()->create($formData);
        $this->save();

    }

    public function deletePayment($id)
    {
        Payment::find($id)->delete();
    }
}

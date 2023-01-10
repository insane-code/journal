<?php

namespace Insane\Journal\Models\Invoice;

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
use Insane\Journal\Jobs\Invoice\CreateInvoiceRelations;
use Insane\Journal\Journal;
use Insane\Journal\Traits\IPayableDocument;

class Invoice extends Model implements IPayableDocument
{
    protected $fillable = [
        'team_id',
        'user_id',
        'client_id',
        'invoiceable_type',
        'invoiceable_id',
        'account_id',
        'invoice_account_id',
        'date',
        'due_date',
        'series',
        'concept',
        'number',
        'order_number',
        'category_type',
        'type',
        'description',
        'direction',
        'notes',
        'total',
        'subtotal',
        'discount',
        'taxes_included',
        'status'
    ];

    const DOCUMENT_TYPE_INVOICE = 'INVOICE';
    const DOCUMENT_TYPE_BILL = 'EXPENSE';

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->series = $invoice->series ?? substr($invoice->date, 0, 4);
            $invoice->account_id = $invoice->account_id ?? Invoice::createContactAccount($invoice);
            $invoice->invoice_account_id = $invoice->invoice_account_id ?? Invoice::createInvoiceAccount($invoice);
            self::setNumber($invoice);
        });

        static::saving(function ($invoice) {
            Invoice::calculateTotal($invoice);
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

     /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLate($query)
    {
        return $query->whereNotIn('status', ['paid', 'draft'])->whereRaw("curdate() > due_date");
    }

    public function scopePaid($query)
    {
        return $query->whereIn('invoices.status', ['paid']);
    }

    public function scopeNoRefunded($query)
    {
        return $query->whereNull('refund_id');
    }

    public function scopeInvoiceAccount($query, $invoiceAccountId)
    {
        return $query->where('invoice_account_id', $invoiceAccountId);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('invoices.status', ['paid', 'draft']);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('invoices.category_type', $category);
    }

    //  relationships
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
        return $this->belongsTo(Journal::$customerModel, 'client_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function invoiceAccount()
    {
        return $this->belongsTo(Account::class, 'invoice_account_id');
    }

    public function invoiceable()
    {
        return $this->morphTo('invoiceable');
    }

    public function account_client()
    {
        return Account::where('client_id', $this->client_id)->limit(1)->get()[0];
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function taxesLines()
    {
        return $this->hasMany(InvoiceLineTax::class);
    }

    public function transaction() {
        return $this->morphOne(Transaction::class, "transactionable");
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function relatedParents() {
      return $this->belongsToMany(
        Invoice::class,
        'invoice_relations',
        'related_invoice_id',
        'invoice_id',
      )->as('parent')->withPivot('name', 'date', 'description');
    }

    public function relatedChilds() {
      return $this->belongsToMany(
        Invoice::class,
        'invoice_relations',
        'invoice_id',
        'related_invoice_id',
      )->withPivot('name', 'date', 'description');
    }

    public function isBill() {
      return $this->type == self::DOCUMENT_TYPE_BILL;
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
            $totalTax = InvoiceLineTax::where(["invoice_id" =>  $invoice->id])->selectRaw('sum(amount * type) as amount')->get();

            $discount = $total[0]['discount'] ?? 0;
            $taxTotal = $totalTax[0]['amount'] ?? 0;
            $invoiceTotal =  ($total[0]['amount'] ?? 0);
            $invoice->subtotal = $total[0]['price'] ?? 0;
            $invoice->discount = $discount;
            $invoice->total = $invoiceTotal + $taxTotal - $discount;
        }
        self::checkPayments($invoice);
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
                new CreateInvoiceRelations($invoice, $invoiceData)
            ])->dispatch();
            return $invoice;
        });
    }

    public function updateDocument($postData) {
        $this->update($postData);
        Bus::chain([
          new CreateInvoiceLine($this, $postData),
          new CreateInvoiceTransaction($this,
            [
                'transactionType' => 'invoice',
                'direction' => 'DEPOSIT',
                'account_id' => $this->account_id,
                'date' => $this->date,
                'description' => $this->concept,
                'total' => $this->total,
            ]
          ),
          new CreateInvoiceRelations($this, $postData)
        ])->dispatch();
    }

    public static function checkStatus($invoice)
    {
        $status = $invoice->status;
        if ($invoice->debt == 0) {
            $status = 'paid';
        } elseif ($invoice->debt > 0 && $invoice->debt < $invoice->total) {
            $status = 'partial';
        } elseif ($invoice->debt && $invoice->due_date < date('Y-m-d')) {
            $status = 'overdue';
        } elseif ($invoice->debt) {
            $status = 'unpaid';
        } else {
            $status = 'draft';
        }

        return $status;
    }

    // accounting
    public static function createContactAccount($invoice)
    {
        $categoryNames = [
          self::DOCUMENT_TYPE_INVOICE => 'expected_payments_customers',
          self::DOCUMENT_TYPE_BILL => 'expected_payments_vendors',
        ];

        if ($invoice->type) {
          $categoryName = $categoryNames[$invoice->type];
          $category = Category::where('display_id', $categoryName)->first();

          $account = Account::where([
                  'client_id' => $invoice->client_id,
                  'category_id' => $category->id
              ])->first();
          if ($account) {
             return $account->id;
          } else {
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
    }

    public static function createInvoiceAccount($invoice)
    {
       if ($invoice->invoice_account_id) return $invoice->invoice_account_id;
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

    public function getInvoiceData() {
        $invoiceData = $this->toArray();
        $invoiceData['client'] = $this->client;
        $invoiceData['lines'] = $this->lines->toArray();
        $invoiceData['payments'] = $this->payments()->with(['transaction'])->get()->toArray();
        $invoiceData['transaction'] = $this->transaction;

        return $invoiceData;
    }

    // payable functions

    public function getStatusField(): string {
      return 'status';
    }

    public function getConceptLine(): string {
      return "Payment of ". $this->concept;
    }

    public function getTransactionDirection(): string {
      return $this->isBill() ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_DEBIT;
    }

    public function getCounterAccountId(): int {
      return $this->isBill() ? $this->invoice_account_id : $this->account_id;
    }
}

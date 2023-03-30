<?php

namespace Insane\Journal\Models\Invoice;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Jobs\Invoice\CreateInvoiceLine;
use Insane\Journal\Jobs\Invoice\CreateInvoiceTransaction;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Transaction;
use Illuminate\Support\Facades\Bus;
use Insane\Journal\Events\InvoiceCreated;
use Insane\Journal\Events\InvoiceSaving;
use Insane\Journal\Jobs\Invoice\CreateExpenseDetails;
use Insane\Journal\Jobs\Invoice\CreateInvoicePayments;
use Insane\Journal\Jobs\Invoice\CreateInvoiceRelations;
use Insane\Journal\Journal;
use Insane\Journal\Traits\HasPayments;
use Insane\Journal\Traits\IPayableDocument;

class Invoice extends Model implements IPayableDocument
{
    use HasPayments;

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

    const STATUS_DRAFT = 'draft';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_CANCELED = 'canceled';
    const STATUS_OVERDUE = 'overdue';

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

            $invoice->payments()->delete();
            $invoice->transaction()->delete();
            $invoice->lines()->delete();
            $invoice->taxesLines()->delete();
            DB::table('invoice_relations')->where('invoice_id', $invoice->id)->delete();
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
        return $query->whereNotIn('invoices.status', ['paid', 'draft'])->whereRaw("curdate() > due_date");
    }

    public function scopePaid($query)
    {
        return $query->where('invoices.status', 'paid');
    }

    public function scopeNoRefunded($query)
    {
        return $query->whereNull('invoices.refund_id');
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

    public function scopeCategoryType($query, $category)
    {
        return $query->where('invoices.category_type', $category);
    }

    public function scopeByClient($query, $clientId = null) {
      if ($clientId) {
         $query->where('invoices.client_id', $clientId);
      }
      return $query;
    }

    public function scopeByTeam($query, $teamId = null) {
      if ($teamId) {
         $query->where('invoices.team_id', $teamId);
      }
      return $query;
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
        $total = InvoiceLine::where(["invoice_id" =>  $invoice->id])->selectRaw('sum(price) as price, sum(discount) as discount, sum(amount) as amount')->get();
        $totalTax = InvoiceLineTax::where(["invoice_id" =>  $invoice->id])->selectRaw('sum(amount * type) as amount')->get();

        $discount = $total[0]['discount'] ?? 0;
        $taxTotal = $totalTax[0]['amount'] ?? 0;
        $invoiceTotal =  ($total[0]['amount'] ?? 0);
        $invoice->subtotal = $total[0]['price'] ?? 0;
        $invoice->discount = $discount;
        $invoice->total = $invoiceTotal + $taxTotal - $discount;
        self::checkPayments($invoice);
    }

    public static function createDocument($invoiceData) {
      $invoice = self::create($invoiceData);
      event(new InvoiceSaving($invoiceData));
      DB::transaction(function () use ($invoiceData, $invoice) {
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
            new CreateInvoiceRelations($invoice, $invoiceData),
            new CreateExpenseDetails($invoice, $invoiceData),
            new CreateInvoicePayments($invoice, $invoiceData)
        ])->dispatch();
      });
      event(new InvoiceCreated($invoice, $invoiceData));
      return $invoice;
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
        return $this;
    }

    public static function checkStatus($invoice)
    {
        $status = $invoice->status;
        if ($invoice->debt == 0) {
            $status = 'paid';
        } elseif ($invoice->debt > 0 && $invoice->debt < $invoice->total) {
            $status = 'partial';
        } elseif ($invoice->debt > 0 && $invoice->due_date < date('Y-m-d')) {
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
           $category = Category::where('display_id', 'operating_income')->first();
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

    public function getInvoiceData() {
        $invoiceData = $this->toArray();
        $invoiceData['client'] = $this->client;
        $invoiceData['lines'] = $this->lines->toArray();
        $invoiceData['payments'] = $this->payments()->with(['transaction'])->get()->toArray();
        $invoiceData['transaction'] = $this->transaction;

        return $invoiceData;
    }

    // payable functions
    public static function checkPayments($invoice)
    {
      if ($invoice && $invoice->payments) {
          $totalPaid = $invoice->payments()->sum('amount');
          $invoice->debt = $invoice->total - $totalPaid;
          $invoice->status = Invoice::checkStatus($invoice);
        }
    }

    public function getStatusField(): string {
      return 'status';
    }

    public function getConceptLine(): string {
      return "Payment of ". $this->concept;
    }

    public function getTransactionDirection(): string {
      return $this->isBill() ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT;
    }

    public function getCounterAccountId(): int {
      return $this->isBill() ? $this->invoice_account_id : $this->account_id;
    }

    public function createPaymentTransaction(Payment $payment) {
        if (method_exists($this->invoiceable, 'createPaymentTransaction')) {
            return $this->invoiceable->createPaymentTransaction($payment, $this);
        } else {
            $direction = $this->getTransactionDirection() ?? Transaction::DIRECTION_DEBIT;
            $counterAccountId = $this->getCounterAccountId();

            return [
                "team_id" => $payment->team_id,
                "user_id" => $payment->user_id,
                "date" => $payment->payment_date,
                "description" => $payment->concept,
                "direction" => $direction,
                "total" => $payment->amount,
                "account_id" => $payment->account_id,
                "counter_account_id" => $counterAccountId,
                "items" => $this->isBill() ? $this->getBillPaymentItems($payment) : []
            ];
        }
    }

    protected function getBillPaymentItems($payment)
    {
        $isExpense = $this->isBill();
        $items = [];

        $mainAccount = $isExpense ? $this->invoice_account_id : Account::where([
          "team_id" => $this->team_id,
          "display_id" => "products"])->first()->id;

        $lineCount = 0;
        $taxAmount = 0;

        foreach ($this->lines as $line) {
            // debits
            $lineTaxAmount = $line->taxes->sum('amount');
            $items[] = [
                "index" => $lineCount,
                "account_id" =>  $mainAccount,
                "category_id" => null,
                "type" => 1,
                "concept" => $line->concept ?? $this->formData['concept'],
                "amount" => ($line->amount ?? $payment->total) - $lineTaxAmount,
                "anchor" => false,
            ];
            echo $lineTaxAmount . PHP_EOL;

            // taxes and retentions
            $lineCount+= 1;
            foreach ($line->taxes as $index => $tax) {
                $lineCount+=$index;
                $items[] = [
                    "index" => $lineCount,
                    "account_id" => $tax->tax->translate_account_id ?? Account::guessAccount($this, [$tax['name'], 'sales_taxes']),
                    "category_id" => null,
                    "type" => -1,
                    "concept" => $tax['name'],
                    "amount" => $tax['amount'],
                    "anchor" => false,
                ];

                $taxAmount += $tax['amount'];

                $items[] = [
                    "index" => $lineCount + 1,
                    "account_id" => $tax->tax->account_id ?? Account::guessAccount($this, [$tax['name'], 'sales_taxes']),
                    "category_id" => null,
                    "type" =>  1,
                    "concept" => $tax['name'],
                    "amount" => $tax['amount'],
                    "anchor" => false,
                ];
            }
        }

          // credits
          $items[] = [
            "index" => count($items),
            "account_id" => $payment->account_id,
            "category_id" => null,
            "type" => -1,
            "concept" => $payment->concept,
            "amount" => $payment->amount,
            "anchor" => true,
        ];

        return $items;
    }
}

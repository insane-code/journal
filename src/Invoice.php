<?php

namespace Insane\Journal;

use App\Models\Client;
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
            Invoice::checkPayments($invoice);
        });

        static::saved(function ($invoice) {
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

            // await PaymentDoc
            // .query()
            // .where('resource_id', InvoiceInstance.id)
            // .delete()

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
                "product_id" => $item['product_id'],
                "quantity" => $item['quantity'],
                "price" => $item['price'],
                "amount" => $item['amount'],
                "team_id" => $this->team_id
            ]);
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
}

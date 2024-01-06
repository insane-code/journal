<?php

namespace Insane\Journal\Models\Quote;

use App\Models\Team;
use App\Models\User;
use Insane\Journal\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Insane\Journal\Events\QuoteSaving;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Events\QuoteCreated;
use Insane\Journal\Jobs\quote\CreateQuoteLine;

class Quote extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'client_id',
        'document_type_id',
        'quotable_type',
        'quotable_id',
        'date',
        'due_date',
        'series',
        'concept',
        'number',
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

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($quote) {
            $quote->series = $quote->series ?? substr($quote->date, 0, 4);
            self::setNumber($quote);
        });

        static::saving(function ($quote) {
            self::calculateTotal($quote);
        });

        static::deleting(function ($quote) {
            $quote->status = 'draft';
            $quote->save();
            foreach ($quote->lines as $item) {
                if ($item->product) {
                    $item->product->updateStock();
                }
            }

            $quote->lines()->delete();
            $quote->taxesLines()->delete();
            // DB::table('document_relations')->where('main_document_id', $quote->id)->delete();
        });

        static::deleted(function ($quote) {
            event(new QuoteDeleted($quote));
        });
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

    public function quotable()
    {
        return $this->morphTo('quotable');
    } 

    public function lines()
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function taxesLines()
    {
        return $this->hasMany(QuoteLineTax::class);
    }


    //  Utils
    public static function setNumber($quote)
    {
        $isInvalidNumber = true;

        if ($quote->number) {
            $isInvalidNumber = self::where([
                "team_id" => $quote->team_id,
                "series" => $quote->series,
                "number" => $quote->number,
            ])->whereNot([
                "id" => $quote->id
            ])->get();

            $isInvalidNumber = count($isInvalidNumber);
        }

        if ($isInvalidNumber) {
            $result = self::where([
                "team_id" => $quote->team_id,
                "series" => $quote->series,
            ])->max('number');
            $quote->number = $result + 1;
        }
    }

    public static function calculateTotal($quote)
    {
        $total = QuoteLine::where(["quote_id" =>  $quote->id])->selectRaw('sum(price) as price, sum(discount) as discount, sum(amount) as amount')->get();
        $totalTax = QuoteLineTax::where(["quote_id" =>  $quote->id])->selectRaw('sum(amount * type) as amount')->get();

        $discount = $total[0]['discount'] ?? 0;
        $taxTotal = $totalTax[0]['amount'] ?? 0;
        $quoteTotal =  ($total[0]['amount'] ?? 0);
        $quote->subtotal = $total[0]['price'] ?? 0;
        $quote->discount = $discount;
        $quote->total = $quoteTotal + $taxTotal - $discount;
    }

    public static function createDocument($quoteData) {
      $quote = self::create($quoteData);
      event(new QuoteSaving($quoteData));
      DB::transaction(function () use ($quoteData, $quote) {
        Bus::chain([
            new CreateQuoteLine($quote, $quoteData),
        ])->dispatch();
      });
      event(new QuoteCreated($quote, $quoteData));
      return $quote;
    }

    public function updateDocument($postData) {
        $this->update($postData);
        Bus::chain([
          new CreateQuoteLine($this, $postData),
        ])->dispatch();
        return $this;
    }

    public function addLine($lines = []) {
        $this->updateDocument([
            ...$this->toArray(),
            "items" => [
                ...$this->lines,
                ...$lines
            ]
        ]);
    }

    public function getQuoteData() {
        $quoteData = $this->toArray();
        $quoteData['client'] = $this->client;
        $quoteData['lines'] = $this->lines->toArray();
        return $quoteData;
    }
}

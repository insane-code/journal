<?php

namespace Insane\Journal\Models\Invoice;

use Illuminate\Database\Eloquent\Model;

class InvoiceNote extends Model {
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    
    protected $fillable = ['id','team_id', 'user_id', 'amount', 'date', 'number', 'type', 'invoice_id', 'note_id'];
}

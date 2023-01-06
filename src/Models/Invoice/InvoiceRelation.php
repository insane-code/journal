<?php

namespace Insane\Journal\Models\Invoice;

use Illuminate\Database\Eloquent\Model;

class InvoiceRelation extends Model {
    protected $fillable = ['id','team_id', 'user_id', 'name', 'label', 'description', 'invoice_id', 'related_invoice_id'];
}

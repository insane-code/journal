<?php

namespace Insane\Journal\Models\Invoice;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model {

    protected $table = 'document_types';
    protected $fillable = ['name', 'label', 'description', 'team_id'];
    public function subTypes() {
        return $this->hasMany(DocumentType::class);
    }

}

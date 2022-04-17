<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountDetailType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'label', 'config'];
}

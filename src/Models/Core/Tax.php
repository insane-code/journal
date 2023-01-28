<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tax extends Model
{
    use HasFactory;
    protected $fillable = [
      'user_id',
      'team_id',
      'account_id',
      'translate_account_id',
      'name',
      'label',
      'description',
      'rate',
      'type',
      'is_default'
    ];

    public static function guessRetention($name, $rate, $session, $attrs) {
      $nameSlug = Str::lower(Str::slug($name, "_"))."_".$rate;
      $tax = self::where([
        'team_id' => $session['team_id'],
        'name' => $nameSlug,
        'type' => -1]
        )->first();
        if ($tax) {
          $tax;
      } else {
          $tax = Tax::create([
              'user_id' => $session['user_id'],
              'team_id' => $session['team_id'],
              'name' => $nameSlug,
              'label' => $name,
              'rate' => $rate,
              "type" => -1,
              'description' => $attrs['description'],
          ]);
      }
      return $tax;
    }
}

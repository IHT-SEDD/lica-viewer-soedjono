<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductOut extends Model
{
   protected $guarded = ['id'];

    //   protected $with = ['product', 'analyzer'];

      public function product()
      {
          return $this->belongsTo('App\Product', 'product_id', 'id');
      }

      public function analyzer()
      {
          return $this->belongsTo('App\Analyzer', 'analyzer_id', 'id');
      }

      public function user()
      {
          return $this->belongsTo('App\User', 'user_id', 'id');
      }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $guarded = ['id'];

    // protected $with = ['product'];

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogInProduct extends Model
{
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }

    public function inventory()
    {
        return $this->belongsTo('App\Inventory', 'inventory_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}

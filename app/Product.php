<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Product extends Model
{
    protected $guarded = ['id'];

    public static function validate($request)
    {
        return Validator::make($request->all(),
        [
            'name' => 'required',
            'stock_buffer' => 'numeric',
        ]
    );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_Shopping_Cart_Item extends Model
{
    use HasFactory;

    protected $table = "user_shopping_cart_item";
    protected $guarded = [];
    public $timestamps = false;
}

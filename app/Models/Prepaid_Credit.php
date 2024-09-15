<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prepaid_Credit extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = "prepaid_credits";
}

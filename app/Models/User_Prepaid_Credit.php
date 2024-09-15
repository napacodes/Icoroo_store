<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_Prepaid_Credit extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'user_prepaid_credits';
}

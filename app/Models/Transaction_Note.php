<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction_Note extends Model
{
    use HasFactory;

    protected $table    = 'transaction_note';
    protected $guarded  = [];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temp_Direct_Url extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = [];
    protected $table = "temp_direct_urls";
}

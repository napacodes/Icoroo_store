<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Custom_Route extends Model
{
    use HasFactory;

    protected $table = 'custom_routes';
    protected $guarded = [];
}

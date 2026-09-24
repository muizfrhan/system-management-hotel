<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'hotel_name',
        'tagline',
        'address',
        'phone',
        'email',
        'logo',
    ];
}

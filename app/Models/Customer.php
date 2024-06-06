<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'first_name', 'last_name', 'company', 'city', 'country', 'phone1', 'phone2', 'email', 'subscription_date', 'website'];
}

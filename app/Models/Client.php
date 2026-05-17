<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'type',
    'name',
    'email',
    'phone',
    'billing_address',
    'profile_data'
])]
class Client extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            // This magically converts the JSON string in the database to a PHP Array in code
            'profile_data' => 'array', 
        ];
    }

    /**
     * Relationship: A client belongs to a User (The Professional)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
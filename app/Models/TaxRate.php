<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'year',
        'type',
        'rates_json',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rates_json' => 'array',
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'client_id', 'invoice_number', 'issue_date', 
        'due_date', 'subtotal', 'vat_total', 'total', 'status', 'items', 'notes'
    ];

    protected $casts = [
        'items' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    // Get the professional who owns this invoice
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get the client billed on this invoice
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
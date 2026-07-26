<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'client_id', 'parent_document_id', 'invoice_number', 'issue_date', 
        'due_date', 'subtotal', 'vat_total', 'total', 'status', 
        'type', 'linked_document_id', 'items', 'notes', 'amount_paid'
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

    // Get the client billed on this invoice (include soft-archived clients for ledger history)
    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    // If this is an Invoice, it might be linked to a previous RFP
    public function linkedDocument()
    {
        return $this->belongsTo(Invoice::class, 'linked_document_id');
    }

    // If this is an RFP, it might have subsequent Invoices or Credit Notes linked TO it
    public function relatedDocuments()
    {
        return $this->hasMany(Invoice::class, 'linked_document_id');
    }
    // A document can have multiple partial payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // A Credit Note belongs to an original Invoice
    public function parentDocument()
    {
        return $this->belongsTo(Invoice::class, 'parent_document_id');
    }

    // An original Invoice can have multiple Credit Notes (if correcting mistakes over time)
    public function childDocuments()
    {
        return $this->hasMany(Invoice::class, 'parent_document_id');
    }
}
<?php

namespace App\Models;

use App\Support\CompanyBooks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'company_client_id',
        'parent_document_id',
        'document_number',
        'issue_date',
        'supply_date',
        'due_date',
        'subtotal',
        'vat_total',
        'total',
        'amount_paid',
        'status',
        'type',
        'linked_document_id',
        'company_recurring_invoice_id',
        'items',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'issue_date' => 'date',
            'supply_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function effectiveSupplyDate(): \Carbon\CarbonInterface
    {
        return $this->supply_date ?? $this->issue_date;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CompanyClient::class, 'company_client_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CompanyPayment::class);
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    public function childDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_document_id');
    }

    public function linkedDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_document_id');
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(CompanyRecurringInvoice::class, 'company_recurring_invoice_id');
    }

    public function balance(): float
    {
        $credits = (float) $this->childDocuments()->where('type', 'credit_note')->sum('total');

        return round(((float) $this->total - $credits) - (float) $this->amount_paid, 2);
    }

    /**
     * Unpaid proformas only. Tax invoices and converted RFPs stay on the books.
     */
    public function canDelete(): bool
    {
        if ($this->type !== 'rfp' || $this->status === 'converted') {
            return false;
        }

        if ((float) $this->amount_paid > 0.009) {
            return false;
        }

        $hasPayments = $this->relationLoaded('payments')
            ? $this->payments->isNotEmpty()
            : $this->payments()->exists();

        return ! $hasPayments;
    }

    public function isMonthlyBill(): bool
    {
        if ($this->company_recurring_invoice_id) {
            return true;
        }

        return str_contains((string) $this->notes, 'Recurring proforma:');
    }

    public function pdfDownloadName(): string
    {
        return CompanyBooks::documentPdfFilename(
            (string) $this->document_number,
            $this->effectiveSupplyDate(),
            $this->isMonthlyBill(),
        );
    }
}

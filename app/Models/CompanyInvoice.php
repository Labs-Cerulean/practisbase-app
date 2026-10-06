<?php

namespace App\Models;

use App\Support\CompanyBooks;
use App\Support\InvoiceCoverage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CompanyInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'company_client_id',
        'parent_document_id',
        'document_number',
        'issue_date',
        'supply_date',
        'coverage_start',
        'coverage_end',
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
            'coverage_start' => 'date',
            'coverage_end' => 'date',
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

    /**
     * Service period printed on the proforma and on the tax invoice.
     * A converted invoice keeps the proforma's month, not the payment date.
     *
     * @return array{0: string, 1: string}|null
     */
    public function resolvedCoverage(): ?array
    {
        if ($this->coverage_start && $this->coverage_end) {
            return [
                $this->coverage_start->toDateString(),
                $this->coverage_end->toDateString(),
            ];
        }

        if ($this->type === 'credit_note' && $this->parentDocument) {
            return $this->parentDocument->resolvedCoverage();
        }

        $source = $this;
        if ($this->type === 'invoice' && $this->linked_document_id) {
            $rfp = $this->relationLoaded('linkedDocument')
                ? $this->linkedDocument
                : $this->linkedDocument()->first();
            if ($rfp) {
                $fromRfp = $rfp->resolvedCoverage();
                if ($fromRfp) {
                    return $fromRfp;
                }
                $source = $rfp;
            }
        }

        if (! $source->isMonthlyBill() || ! $source->issue_date) {
            return null;
        }

        $start = $source->issue_date->toDateString();

        return [$start, InvoiceCoverage::monthEnd($start)];
    }

    public function coverageLabel(): ?string
    {
        $bounds = $this->resolvedCoverage();
        if (! $bounds) {
            return null;
        }

        return InvoiceCoverage::label($bounds[0], $bounds[1]);
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
        if ($this->relationLoaded('childDocuments')) {
            $credits = (float) $this->childDocuments
                ->where('type', 'credit_note')
                ->sum(fn (self $doc) => (float) $doc->total);
        } else {
            $credits = (float) $this->childDocuments()->where('type', 'credit_note')->sum('total');
        }

        return round(((float) $this->total - $credits) - (float) $this->amount_paid, 2);
    }

    /**
     * A converted proforma has no balance of its own. The tax invoice carries the amount.
     *
     * @param  Collection<int, self>  $documents
     * @return Collection<int, self>
     */
    public static function withoutSupersededProformas(Collection $documents): Collection
    {
        $linkedIds = [];
        foreach ($documents as $doc) {
            if ($doc->type === 'invoice' && $doc->linked_document_id) {
                $linkedIds[(int) $doc->linked_document_id] = true;
            }
        }

        return $documents
            ->reject(fn (self $doc) => $doc->type === 'rfp' && isset($linkedIds[(int) $doc->id]))
            ->values();
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

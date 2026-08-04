<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyJournalLine extends Model
{
    protected $fillable = [
        'user_id',
        'journal_entry_id',
        'gl_account_id',
        'company_client_id',
        'side',
        'amount',
        'memo',
        'bank_statement_line_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompanyJournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyGlAccount::class, 'gl_account_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CompanyClient::class, 'company_client_id');
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->side === 'debit' ? $amount : -$amount;
    }
}

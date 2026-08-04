<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBankStatementLine extends Model
{
    protected $fillable = [
        'user_id',
        'statement_date',
        'description',
        'amount',
        'status',
        'matched_journal_line_id',
        'import_batch',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedLine(): BelongsTo
    {
        return $this->belongsTo(CompanyJournalLine::class, 'matched_journal_line_id');
    }
}

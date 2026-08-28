<?php

namespace App\Mail;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use App\Models\CompanyRecurringInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Classic build() mailable — avoids Envelope/Content named-arg path that
 * surfaced as a PHP parse error on send in production.
 */
class CompanyClientBillingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{rows: mixed, official_owed: float, rfp_owed: float, total_owed: float}|null  $statement
     */
    public function __construct(
        CompanyProfile $profile,
        CompanyClient $client,
        CompanyRecurringInvoice $schedule,
        string $kind,
        ?CompanyInvoice $document = null,
        ?array $statement = null,
    ) {
        $this->profile = $profile;
        $this->client = $client;
        $this->schedule = $schedule;
        $this->kind = $kind;
        $this->document = $document;
        $this->statement = $statement;
    }

    public CompanyProfile $profile;

    public CompanyClient $client;

    public CompanyRecurringInvoice $schedule;

    public string $kind;

    public ?CompanyInvoice $document = null;

    /** @var array{rows: mixed, official_owed: float, rfp_owed: float, total_owed: float}|null */
    public ?array $statement = null;

    public function build(): self
    {
        $company = $this->profile->legal_name ?: 'Cerulean Labs Ltd';

        $subject = match ($this->kind) {
            'proforma' => $company.' - monthly proforma '.($this->document?->document_number ?? ''),
            'reminder' => $company.' - payment reminder',
            'statement' => $company.' - account statement',
            default => $company.' - billing notice',
        };

        return $this->subject($subject)
            ->view('company.mail.billing-notice', [
                'profile' => $this->profile,
                'client' => $this->client,
                'schedule' => $this->schedule,
                'kind' => $this->kind,
                'document' => $this->document,
                'statement' => $this->statement,
            ]);
    }
}

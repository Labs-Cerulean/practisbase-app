<?php

namespace App\Mail;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use App\Models\CompanyRecurringInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyClientBillingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{rows: mixed, official_owed: float, rfp_owed: float, total_owed: float}|null  $statement
     */
    public function __construct(
        public CompanyProfile $profile,
        public CompanyClient $client,
        public CompanyRecurringInvoice $schedule,
        public string $kind,
        public ?CompanyInvoice $document = null,
        public ?array $statement = null,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->profile->legal_name ?: 'Cerulean Labs Ltd';

        $subject = match ($this->kind) {
            'proforma' => $company.' — monthly proforma '.$($this->document?->document_number ?? ''),
            'reminder' => $company.' — payment reminder',
            'statement' => $company.' — account statement',
            default => $company.' — billing notice',
        };

        return new Envelope(subject: trim($subject));
    }

    public function content(): Content
    {
        return new Content(
            view: 'company.mail.billing-notice',
            with: [
                'profile' => $this->profile,
                'client' => $this->client,
                'schedule' => $this->schedule,
                'kind' => $this->kind,
                'document' => $this->document,
                'statement' => $this->statement,
            ],
        );
    }
}

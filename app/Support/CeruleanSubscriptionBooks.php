<?php

namespace App\Support;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyPayment;
use App\Models\CompanyProfile;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Posts a paid PractisBase subscription onto the Cerulean Labs Limited books
 * and emails the tax invoice and receipt.
 */
class CeruleanSubscriptionBooks
{
    public static function operator(): ?User
    {
        $users = User::query()
            ->where('company_books_enabled', true)
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $name = CompanyProfile::query()->where('user_id', $user->id)->value('legal_name');
            if (is_string($name) && strcasecmp($name, CompanyBooks::DEFAULT_LEGAL_NAME) === 0) {
                return $user;
            }
        }

        return $users->first();
    }

    public static function recordPaidInvoice(object $invoice): void
    {
        $stripeInvoiceId = (string) data_get($invoice, 'id', '');
        if (! str_starts_with($stripeInvoiceId, 'in_')) {
            return;
        }

        $currency = strtolower((string) data_get($invoice, 'currency', 'eur'));
        if ($currency !== 'eur') {
            throw new \RuntimeException('Stripe invoice '.$stripeInvoiceId.' is not in EUR.');
        }

        $totalCents = (int) data_get($invoice, 'total', 0);
        $paidCents = (int) data_get($invoice, 'amount_paid', 0);
        if ($totalCents <= 0 || $paidCents <= 0) {
            return;
        }

        RegisterBillingSchema::ensure();
        if (! Schema::hasTable('company_invoices') || ! Schema::hasColumn('company_invoices', 'stripe_invoice_id')) {
            throw new \RuntimeException('Company invoice Stripe column is missing. Apply database/manual/stripe_company_billing_postgresql.sql.');
        }

        $subscriber = self::subscriber($invoice);
        if (! $subscriber || $subscriber->company_books_enabled) {
            return;
        }

        $operator = self::operator();
        if (! $operator) {
            throw new \RuntimeException('No Cerulean Labs Limited company books account is enabled.');
        }

        $split = CeruleanBillingDocument::splitGross($totalCents / 100);
        $paid = CeruleanBillingDocument::splitGross($paidCents / 100);
        $issueDate = self::issueDate($invoice);
        [$coverageStart, $coverageEnd, $description] = self::lineDetails($invoice, $subscriber);
        $vatNumber = self::customerVat($invoice, $subscriber);
        $address = CeruleanBillingDocument::formatAddress(data_get($invoice, 'customer_address'));
        $customerName = trim((string) (data_get($invoice, 'customer_name') ?: $subscriber->name));
        $customerEmail = trim((string) (data_get($invoice, 'customer_email') ?: $subscriber->email));

        $created = false;
        $document = null;
        $payment = null;

        DB::transaction(function () use (
            $operator,
            $subscriber,
            $stripeInvoiceId,
            $split,
            $paid,
            $issueDate,
            $coverageStart,
            $coverageEnd,
            $description,
            $vatNumber,
            $address,
            $customerName,
            $customerEmail,
            &$created,
            &$document,
            &$payment
        ) {
            User::query()->whereKey($operator->id)->lockForUpdate()->first();

            $existing = CompanyInvoice::query()
                ->where('user_id', $operator->id)
                ->where('stripe_invoice_id', $stripeInvoiceId)
                ->first();
            if ($existing) {
                $document = $existing;

                return;
            }

            $client = self::clientFor($operator, $subscriber, $customerName, $customerEmail, $address, $vatNumber);
            $number = CompanyBooks::nextDocumentNumber($operator->id, 'invoice', (int) substr($issueDate, 0, 4));

            $document = CompanyInvoice::create([
                'user_id' => $operator->id,
                'company_client_id' => $client->id,
                'type' => 'invoice',
                'document_number' => $number,
                'issue_date' => $issueDate,
                'supply_date' => $issueDate,
                'coverage_start' => $coverageStart,
                'coverage_end' => $coverageEnd,
                'due_date' => $issueDate,
                'subtotal' => $split['subtotal'],
                'vat_total' => $split['vat'],
                'total' => $split['total'],
                'amount_paid' => 0,
                'status' => 'unpaid',
                'items' => [[
                    'description' => $description,
                    'quantity' => 1,
                    'unit_price' => $split['subtotal'],
                    'row_total' => $split['subtotal'],
                ]],
                'notes' => 'PractisBase subscription. Stripe invoice '.$stripeInvoiceId.'.',
                'stripe_invoice_id' => $stripeInvoiceId,
            ]);

            CompanyLedger::ensureChart($operator);
            CompanyLedger::postInvoiceIssued($document);

            $payment = CompanyPayment::create([
                'user_id' => $operator->id,
                'company_invoice_id' => $document->id,
                'amount' => $paid['total'],
                'payment_date' => $issueDate,
                'payment_method' => 'stripe',
                'notes' => 'Card receipt for '.$number.'. Stripe invoice '.$stripeInvoiceId.'.',
                'is_transfer' => false,
                'stripe_invoice_id' => $stripeInvoiceId,
            ]);
            CompanyLedger::postPayment($payment, $document);

            $document->update([
                'amount_paid' => $paid['total'],
                'status' => $paid['total'] + 0.009 >= $split['total'] ? 'paid' : 'partial',
            ]);
            $created = true;
        });

        if ($created && $document && $payment) {
            self::emailDocuments($operator, $document, $payment, $customerEmail);
        }
    }

    public static function subscriber(object $invoice): ?User
    {
        $userId = (int) data_get($invoice, 'subscription_details.metadata.user_id', data_get($invoice, 'metadata.user_id', 0));
        if ($userId > 0) {
            $user = User::query()->whereKey($userId)->first();
            if ($user) {
                return $user;
            }
        }

        $customerId = data_get($invoice, 'customer');
        if (is_object($customerId)) {
            $customerId = data_get($customerId, 'id');
        }
        if (is_string($customerId) && $customerId !== '') {
            $user = User::query()->where('stripe_customer_id', $customerId)->first();
            if ($user) {
                return $user;
            }
        }

        $subscriptionId = data_get($invoice, 'subscription');
        if (is_object($subscriptionId)) {
            $subscriptionId = data_get($subscriptionId, 'id');
        }
        if (is_string($subscriptionId) && $subscriptionId !== '') {
            return User::query()->where('stripe_subscription_id', $subscriptionId)->first();
        }

        return null;
    }

    private static function clientFor(
        User $operator,
        User $subscriber,
        string $name,
        string $email,
        ?string $address,
        ?string $vatNumber
    ): CompanyClient {
        $marker = 'PractisBase subscriber '.$subscriber->id;
        $client = CompanyClient::query()
            ->where('user_id', $operator->id)
            ->where('notes', 'like', '%'.$marker.'%')
            ->first();

        if (! $client && $email !== '') {
            $client = CompanyClient::query()
                ->where('user_id', $operator->id)
                ->whereRaw('lower(email) = ?', [strtolower($email)])
                ->first();
        }

        $notes = $marker;
        if ($client && filled($client->notes) && ! str_contains((string) $client->notes, $marker)) {
            $notes = trim($client->notes."\n".$marker);
        }

        $attributes = [
            'name' => $name !== '' ? $name : $subscriber->name,
            'email' => $email !== '' ? $email : $subscriber->email,
            'notes' => $notes,
        ];
        if ($address) {
            $attributes['billing_address'] = $address;
        }
        if ($vatNumber) {
            $attributes['vat_number'] = $vatNumber;
        } elseif ($client && ! $subscriber->include_billing_vat) {
            $attributes['vat_number'] = null;
        }

        if ($client) {
            $client->fill($attributes)->save();

            return $client;
        }

        return CompanyClient::create($attributes + ['user_id' => $operator->id]);
    }

    private static function customerVat(object $invoice, User $subscriber): ?string
    {
        $taxIds = data_get($invoice, 'customer_tax_ids', []);
        if (is_array($taxIds)) {
            foreach ($taxIds as $tax) {
                $value = trim((string) data_get($tax, 'value', ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        if ($subscriber->include_billing_vat && filled($subscriber->billing_vat_number)) {
            return (string) $subscriber->billing_vat_number;
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private static function lineDetails(object $invoice, User $subscriber): array
    {
        $tier = (string) data_get($invoice, 'subscription_details.metadata.tier', $subscriber->tier);
        $description = 'PractisBase '.TierPolicy::label($tier);
        $start = null;
        $end = null;
        $lines = data_get($invoice, 'lines.data', []);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $lineDescription = trim((string) data_get($line, 'description', ''));
                if ($lineDescription !== '') {
                    $description = $lineDescription;
                }
                $start = data_get($line, 'period.start');
                $end = data_get($line, 'period.end');
                break;
            }
        }

        $coverage = CeruleanBillingDocument::coverageBounds(
            is_numeric($start) ? (int) $start : null,
            is_numeric($end) ? (int) $end : null,
        );

        return [$coverage[0] ?? null, $coverage[1] ?? null, $description];
    }

    private static function issueDate(object $invoice): string
    {
        $paidAt = (int) (data_get($invoice, 'status_transitions.paid_at') ?: time());
        $date = Carbon::createFromTimestamp($paidAt)->timezone('Europe/Malta')->toDateString();
        $today = Carbon::now('Europe/Malta')->toDateString();

        return $date > $today ? $today : $date;
    }

    private static function emailDocuments(User $operator, CompanyInvoice $document, CompanyPayment $payment, string $to): void
    {
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $profile = CompanyBooks::ensureProfile($operator);
            $document->load('client');
            $invoicePdf = Pdf::loadView('company.pdf.document', [
                'document' => $document,
                'profile' => $profile,
                'creditedInvoice' => null,
            ])->output();
            $receiptPdf = Pdf::loadView('company.pdf.payment-receipt', [
                'document' => $document,
                'payment' => $payment,
                'profile' => $profile,
            ])->output();

            $company = $profile->legal_name ?: CompanyBooks::DEFAULT_LEGAL_NAME;
            $html = view('company.mail.subscription-invoice', [
                'company' => $company,
                'clientName' => (string) ($document->client->name ?? ''),
                'documentNumber' => (string) $document->document_number,
                'total' => number_format((float) $document->total, 2),
                'vatNumber' => (string) ($profile->vat_number ?: ''),
            ])->render();

            Mail::html($html, function ($message) use ($to, $company, $document, $invoicePdf, $receiptPdf, $profile) {
                $from = (string) config('mail.from.address');
                if ($from !== '') {
                    $message->from($from, $company);
                }
                $message->to($to)->subject($company.' invoice '.$document->document_number);
                if ($copy = CompanyBooks::accountsCopyAddress($to)) {
                    $message->cc($copy);
                }
                $reply = trim((string) ($profile->user?->email ?? ''));
                if ($reply !== '' && filter_var($reply, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($reply, $company);
                }
                $message->attachData($invoicePdf, $document->pdfDownloadName(), ['mime' => 'application/pdf']);
                $message->attachData($receiptPdf, $document->document_number.'-receipt.pdf', ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyPersonalReceipt;
use App\Support\CompanyBooks;
use App\Support\CompanyLedger;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonalReceiptController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $receipts = CompanyPersonalReceipt::where('user_id', $user->id)
            ->orderByDesc('received_on')
            ->orderByDesc('id')
            ->get();

        $holding = round((float) $receipts
            ->filter(fn (CompanyPersonalReceipt $receipt) => $receipt->isHolding())
            ->sum(fn (CompanyPersonalReceipt $receipt) => (float) $receipt->amount), 2);

        return view('company.accounts.personal', [
            'receipts' => $receipts,
            'holding' => $holding,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $alreadyReturned = $request->boolean('already_returned');

        $validated = $request->validate([
            'received_on' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:120',
            'returned_on' => $alreadyReturned ? 'required|date|before_or_equal:today' : 'nullable|date',
            'return_reference' => 'nullable|string|max:120',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $receivedOn = $validated['received_on'];
        $returnedOn = $alreadyReturned ? $validated['returned_on'] : null;

        if ($returnedOn && $returnedOn < $receivedOn) {
            return back()->withErrors([
                'returned_on' => 'The return date cannot be earlier than the day the money arrived.',
            ])->withInput();
        }

        $this->assertPeriodOpen($profile->first_period_end->getTimestamp(), $receivedOn, 'received_on');
        if ($returnedOn) {
            $this->assertPeriodOpen($profile->first_period_end->getTimestamp(), $returnedOn, 'returned_on');
        }

        try {
            CompanyLedger::assertDateOpen($user->id, $receivedOn);
            if ($returnedOn) {
                CompanyLedger::assertDateOpen($user->id, $returnedOn);
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $proofPath = null;
        if ($returnedOn && $request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store(
                TenantStorage::companyPersonalPath($user->id),
                TenantStorage::diskName()
            );
        }

        try {
            DB::transaction(function () use ($user, $validated, $receivedOn, $returnedOn, $proofPath) {
                $receipt = CompanyPersonalReceipt::create([
                    'user_id' => $user->id,
                    'received_on' => $receivedOn,
                    'amount' => round((float) $validated['amount'], 2),
                    'description' => trim($validated['description']),
                    'reference' => $this->blankToNull($validated['reference'] ?? null),
                    'returned_on' => $returnedOn,
                    'return_reference' => $returnedOn ? $this->blankToNull($validated['return_reference'] ?? null) : null,
                    'proof_path' => $proofPath,
                ]);

                CompanyLedger::postPersonalFundsReceived($receipt);
                if ($returnedOn) {
                    CompanyLedger::postPersonalFundsReturned($receipt);
                }
            });
        } catch (ValidationException $e) {
            if ($proofPath) {
                TenantStorage::disk()->delete($proofPath);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            if ($proofPath) {
                TenantStorage::disk()->delete($proofPath);
            }
            throw $e;
        }

        $message = $returnedOn
            ? 'Recorded €'.number_format((float) $validated['amount'], 2).' received and returned. Profit is unchanged.'
            : 'Recorded €'.number_format((float) $validated['amount'], 2).' in the company bank. It is owed back to you. Profit is unchanged.';

        return redirect('/company/personal')->with('success', $message);
    }

    public function returnFunds(Request $request, int $receipt)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $model = CompanyPersonalReceipt::where('user_id', $user->id)->where('id', $receipt)->firstOrFail();

        if ($model->isReversed()) {
            return back()->withErrors(['receipt' => 'This receipt is reversed.']);
        }
        if ($model->returned_on) {
            return back()->withErrors(['receipt' => 'This amount has already been returned.']);
        }

        $validated = $request->validate([
            'returned_on' => 'required|date|before_or_equal:today|after_or_equal:'.$model->received_on->format('Y-m-d'),
            'return_reference' => 'nullable|string|max:120',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $this->assertPeriodOpen($profile->first_period_end->getTimestamp(), $validated['returned_on'], 'returned_on');

        try {
            CompanyLedger::assertDateOpen($user->id, $validated['returned_on']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store(
                TenantStorage::companyPersonalPath($user->id),
                TenantStorage::diskName()
            );
        }

        try {
            DB::transaction(function () use ($user, $model, $validated, $proofPath) {
                $locked = CompanyPersonalReceipt::where('user_id', $user->id)
                    ->where('id', $model->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->reversed_at || $locked->returned_on) {
                    throw ValidationException::withMessages([
                        'receipt' => 'This amount is no longer waiting to be returned.',
                    ]);
                }

                $locked->update([
                    'returned_on' => $validated['returned_on'],
                    'return_reference' => $this->blankToNull($validated['return_reference'] ?? null),
                    'proof_path' => $proofPath,
                ]);

                CompanyLedger::ensureChart($user);
                CompanyLedger::postPersonalFundsReturned($locked->fresh());
            });
        } catch (ValidationException $e) {
            if ($proofPath) {
                TenantStorage::disk()->delete($proofPath);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            if ($proofPath) {
                TenantStorage::disk()->delete($proofPath);
            }
            throw $e;
        }

        return back()->with('success', 'Returned €'.number_format((float) $model->amount, 2).' to your personal account. The bank and the director loan are cleared for this amount.');
    }

    public function reverse(Request $request, int $receipt)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $model = CompanyPersonalReceipt::where('user_id', $user->id)->where('id', $receipt)->firstOrFail();

        if ($model->isReversed()) {
            return back()->withErrors(['receipt' => 'This receipt is already reversed.']);
        }

        $validated = $request->validate([
            'reversed_at' => 'required|date|before_or_equal:today|after_or_equal:'.$model->received_on->format('Y-m-d'),
            'reversal_note' => 'nullable|string|max:500',
        ]);

        if ($model->returned_on && $validated['reversed_at'] < $model->returned_on->format('Y-m-d')) {
            return back()->withErrors([
                'reversed_at' => 'The reversal date cannot be earlier than the return date.',
            ])->withInput();
        }

        $this->assertPeriodOpen($profile->first_period_end->getTimestamp(), $validated['reversed_at'], 'reversed_at');

        $note = trim((string) ($validated['reversal_note'] ?? ''));

        try {
            CompanyLedger::reversePersonalReceipt($model, $validated['reversed_at'], $note !== '' ? $note : null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Reversed the personal receipt of €'.number_format((float) $model->amount, 2).'. It no longer sits on the bank or the director loan.');
    }

    public function proof(int $receipt)
    {
        $model = CompanyPersonalReceipt::where('user_id', Auth::id())
            ->where('id', $receipt)
            ->firstOrFail();

        if (! filled($model->proof_path)) {
            abort(404);
        }

        return TenantStorage::disk()->download($model->proof_path);
    }

    private function assertPeriodOpen(int $periodEnd, string $date, string $field): void
    {
        if (strtotime($date) > $periodEnd) {
            throw ValidationException::withMessages([
                $field => 'That date is after the first financial period end.',
            ]);
        }
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

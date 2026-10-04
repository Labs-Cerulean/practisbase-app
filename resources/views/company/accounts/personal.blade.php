@extends('layouts.app')

@section('page_title', 'Personal money')

@section('content')
    <style>
        #returnFields[hidden] { display: none !important; }
    </style>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Personal money</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 40rem;">
                Money that landed in the company bank but belongs to you. It is not income and it is not an expense.
            </p>
        </div>
        <a href="/company" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Desk</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Still to send to your personal account</div>
        <div style="font-size: 1.6rem; font-weight: 700; color: {{ $holding > 0 ? '#b45309' : '#059669' }};">€{{ number_format($holding, 2) }}</div>
    </div>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.35rem;">Money arrived in the company account</div>
        <p id="personalGuide" style="margin: 0 0 0.9rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.45;">The bank goes up and the director loan goes up. Profit stays the same. Transfer it to your personal account, then record the return here.</p>
        <form method="POST" action="/company/personal" enctype="multipart/form-data">
            @csrf
            <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: end;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Date received</label>
                    <input type="date" name="received_on" value="{{ old('received_on', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Amount €</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required style="width: 8rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="flex: 1; min-width: 14rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">What it was</label>
                    <input type="text" name="description" value="{{ old('description') }}" required maxlength="500" placeholder="Transfer meant for my personal account" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Bank reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" maxlength="120" placeholder="Optional" style="width: 9rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0.9rem 0; font-size: 0.85rem; color: var(--primary-navy); cursor: pointer;">
                <input type="checkbox" name="already_returned" id="alreadyReturned" value="1" @checked(old('already_returned')) style="width: 1.05rem; height: 1.05rem;">
                I have already sent this back to my personal account
            </label>
            <div id="returnFields" @if(!old('already_returned')) hidden @endif style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: end; margin-bottom: 0.9rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Date returned</label>
                    <input type="date" name="returned_on" id="returnedOn" value="{{ old('returned_on', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Return reference</label>
                    <input type="text" name="return_reference" value="{{ old('return_reference') }}" maxlength="120" placeholder="BOV reference" style="width: 10rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="flex: 1; min-width: 12rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Proof of the transfer</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" style="width: 100%; font-size: 0.85rem;">
                </div>
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; cursor: pointer;">Record</button>
        </form>
    </div>

    <div style="display: grid; gap: 0.75rem;">
        @forelse($receipts as $receipt)
            <div style="background: {{ $receipt->isReversed() ? '#f8fafc' : 'white' }}; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $receipt->description }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.45;">
                            Received {{ $receipt->received_on->format('d M Y') }}
                            @if($receipt->reference) · {{ $receipt->reference }} @endif
                            @if($receipt->isHolding())
                                · <span style="color: #b45309; font-weight: 700;">Holding in the company account</span>
                            @elseif($receipt->isReturned())
                                · <span style="color: #059669; font-weight: 700;">Returned {{ $receipt->returned_on->format('d M Y') }}</span>
                                @if($receipt->return_reference) · {{ $receipt->return_reference }} @endif
                            @else
                                · Reversed {{ $receipt->reversed_at->format('d M Y') }}
                                @if($receipt->reversal_note) · {{ $receipt->reversal_note }} @endif
                            @endif
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--primary-navy); font-size: 1.1rem;">€{{ number_format((float) $receipt->amount, 2) }}</div>
                </div>
                @if($receipt->proof_path)
                    <div style="margin-top: 0.55rem;">
                        <a href="/company/personal/{{ $receipt->id }}/proof" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Transfer proof</a>
                    </div>
                @endif
                @if($receipt->isHolding())
                    <form method="POST" action="/company/personal/{{ $receipt->id }}/return" enctype="multipart/form-data" style="margin-top: 0.85rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end;">
                        @csrf
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Returned on</label>
                            <input type="date" name="returned_on" value="{{ date('Y-m-d') }}" min="{{ $receipt->received_on->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.4rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Reference</label>
                            <input type="text" name="return_reference" maxlength="120" placeholder="BOV reference" style="width: 9rem; padding: 0.4rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Proof</label>
                            <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" style="font-size: 0.8rem;">
                        </div>
                        <button type="submit" style="background: #059669; color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.8rem; cursor: pointer;">Returned to me</button>
                    </form>
                @endif
                @if(!$receipt->isReversed())
                    <form method="POST" action="/company/personal/{{ $receipt->id }}/reverse" style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end;" onsubmit="return confirm('Reverse this personal receipt? The opposite journals take it off the bank and the director loan.');">
                        @csrf
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Reverse on</label>
                            <input type="date" name="reversed_at" value="{{ date('Y-m-d') }}" min="{{ $receipt->returned_on ? $receipt->returned_on->format('Y-m-d') : $receipt->received_on->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.4rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        </div>
                        <input type="text" name="reversal_note" maxlength="500" placeholder="Why (optional)" style="flex: 1; min-width: 8rem; padding: 0.4rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        <button type="submit" style="background: white; color: #991b1b; border: 1px solid #fecaca; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Reverse</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No personal money has been recorded in the company account.
            </div>
        @endforelse
    </div>

    <script>
        (function () {
            var box = document.getElementById('alreadyReturned');
            var fields = document.getElementById('returnFields');
            var returnedOn = document.getElementById('returnedOn');
            var guide = document.getElementById('personalGuide');
            if (!box || !fields) return;

            function sync() {
                fields.hidden = !box.checked;
                if (returnedOn) returnedOn.required = box.checked;
                if (guide) {
                    guide.textContent = box.checked
                        ? 'Both movements are posted together. The bank and the director loan end up unchanged, and profit stays the same.'
                        : 'The bank goes up and the director loan goes up. Profit stays the same. Transfer it to your personal account, then record the return here.';
                }
            }

            box.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection

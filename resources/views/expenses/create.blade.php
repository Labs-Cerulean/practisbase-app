@extends('layouts.app')

@section('page_title', 'New expense')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: var(--primary-navy); margin: 0;">Log Expense</h2>
            <a href="/expenses" style="color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Cancel</a>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.5rem;">
            <p style="margin: 0; font-size: 0.85rem; color: #78350f; line-height: 1.45;">
                Claim only costs for your practice (or the business share of mixed costs such as car, fuel, or home bills).
                <button type="button" data-open-expense-guide style="background: none; border: none; padding: 0; font: inherit; color: #92400e; font-weight: 700; cursor: pointer; border-bottom: 1px dotted #92400e;">What is claimable?</button>
            </p>
        </div>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="/expenses" method="POST" enctype="multipart/form-data" id="expenseForm">
            @csrf

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Category</label>
                <select name="category" id="expenseCategory" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p id="categoryHint" style="font-size: 0.8rem; color: var(--text-muted); margin: 0.45rem 0 0; line-height: 1.4;"></p>
            </div>

            <div id="businessUseWrap" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem;">Practice-use percentage <span style="color: #b91c1c;">*</span></label>
                <p style="margin: 0 0 0.65rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                    Required for car and fuel. Personal use is not deductible.
                    <button type="button" data-open-car-helper style="background: none; border: none; padding: 0; font: inherit; color: var(--primary-navy); font-weight: 600; cursor: pointer; border-bottom: 1px dotted var(--primary-navy);">Estimate with helper</button>
                </p>
                <div style="display: flex; align-items: center; gap: 0.5rem; max-width: 12rem;">
                    <input type="number" name="business_use_percent" id="businessUsePercent" step="0.01" min="1" max="100" value="{{ old('business_use_percent', $user->car_business_use_percent) }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <span style="font-weight: 600; color: var(--text-muted);">%</span>
                </div>
            </div>

            <div id="wfhWrap" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
                <p style="margin: 0; font-size: 0.85rem; color: #1e3a8a; line-height: 1.45;">
                    Home bills use your saved home-office share
                    (<strong id="wfhPctLabel">{{ $user->home_office_percent !== null ? number_format((float) $user->home_office_percent, 0).'%' : 'not set' }}</strong>).
                    <button type="button" data-open-wfh-helper style="background: none; border: none; padding: 0; font: inherit; color: #1d4ed8; font-weight: 700; cursor: pointer; border-bottom: 1px dotted #1d4ed8;">Set / update helper</button>
                </p>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" required maxlength="1000" placeholder="e.g. Accounting software annual plan" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Amount (ex VAT)</label>
                    <input type="number" name="amount" id="expenseAmount" step="0.01" min="0.01" value="{{ old('amount') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT amount <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="number" name="vat_amount" step="0.01" min="0" value="{{ old('vat_amount', '0') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <p id="deductPreview" style="display: none; font-size: 0.85rem; color: var(--primary-navy); margin: -0.5rem 0 1.25rem; line-height: 1.45; padding: 0.65rem 0.85rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);"></p>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Receipt upload <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" style="width: 100%; padding: 0.5rem 0;">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.35rem 0 0;">JPG, PNG or PDF · max 5MB · stored privately on your tenant object storage (Cloudflare R2 in production).</p>
            </div>

            <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save Expense</button>
        </form>
    </div>

    @include('expenses.partials.claim-guide-modal')
    @include('expenses.partials.business-use-helpers', [
        'user' => $user,
        'redirectTo' => '/expenses/create',
    ])

    <script>
        (function () {
            var rates = @json($capitalRates ?? ['laptop' => 0.25, 'equipment' => 0.25, 'car' => 0.20]);
            var cat = document.getElementById('expenseCategory');
            var hint = document.getElementById('categoryHint');
            var bizWrap = document.getElementById('businessUseWrap');
            var bizInput = document.getElementById('businessUsePercent');
            var wfhWrap = document.getElementById('wfhWrap');
            var preview = document.getElementById('deductPreview');
            var amount = document.getElementById('expenseAmount');
            var homePct = @json($user->home_office_percent !== null ? (float) $user->home_office_percent : null);

            function sync() {
                var v = cat.value;
                var biz = v === 'car' || v === 'fuel';
                var wfh = v.indexOf('wfh_') === 0;

                bizWrap.style.display = biz ? 'block' : 'none';
                bizInput.required = biz;
                wfhWrap.style.display = wfh ? 'block' : 'none';

                if (v === 'laptop' || v === 'equipment') {
                    hint.textContent = 'Capital item — wear & tear of about ' + Math.round((rates[v] || 0.25) * 100) + '% per year for tax (not 100% in year one).';
                } else if (v === 'car') {
                    hint.textContent = 'Capital item — wear & tear uses your practice-use %. Full private use is not claimable.';
                } else if (v === 'fuel') {
                    hint.textContent = 'Only the practice-use share of fuel is deductible.';
                } else if (wfh) {
                    hint.textContent = homePct
                        ? ('Only about ' + homePct + '% of this bill (your home-office share) is deductible.')
                        : 'Set your home-office percentage with the helper before saving.';
                } else {
                    hint.textContent = 'Logged in full for the year (subject to being a genuine practice cost).';
                }

                updateDeductPreview();
            }

            function updateDeductPreview() {
                var v = cat.value;
                var cost = Number(amount.value || 0);
                if (cost <= 0) {
                    preview.style.display = 'none';
                    return;
                }

                if (['laptop', 'equipment', 'car'].indexOf(v) !== -1) {
                    var rate = rates[v] || 0.25;
                    var pct = v === 'car' ? Number(bizInput.value || 0) / 100 : 1;
                    if (pct <= 0) {
                        preview.style.display = 'none';
                        return;
                    }
                    var year1 = (cost * rate * pct).toFixed(2);
                    preview.style.display = 'block';
                    preview.innerHTML = 'Counts as <strong>€' + year1 + '</strong> this year (about ' + Math.round(rate * 100) + '% wear &amp; tear'
                        + (v === 'car' ? ' × practice-use %' : '') + ').';
                    return;
                }

                if (v === 'fuel') {
                    var fuelPct = Number(bizInput.value || 0);
                    if (fuelPct <= 0) {
                        preview.style.display = 'none';
                        return;
                    }
                    var fuelShare = (cost * fuelPct / 100).toFixed(2);
                    preview.style.display = 'block';
                    preview.innerHTML = 'Counts as <strong>€' + fuelShare + '</strong> (' + fuelPct + '% practice-use of €' + cost.toFixed(2) + ').';
                    return;
                }

                if (v.indexOf('wfh_') === 0) {
                    if (!homePct || homePct <= 0) {
                        preview.style.display = 'block';
                        preview.innerHTML = 'Set your home-office % so we can show how much of this bill counts.';
                        return;
                    }
                    var wfhShare = (cost * homePct / 100).toFixed(2);
                    preview.style.display = 'block';
                    preview.innerHTML = 'Counts as <strong>€' + wfhShare + '</strong> (' + homePct + '% home-office of €' + cost.toFixed(2) + ').';
                    return;
                }

                preview.style.display = 'block';
                preview.innerHTML = 'Counts as <strong>€' + cost.toFixed(2) + '</strong> toward deductible expenses this year.';
            }

            cat.addEventListener('change', sync);
            amount.addEventListener('input', updateDeductPreview);
            bizInput.addEventListener('input', updateDeductPreview);
            document.addEventListener('business-use-updated', function (e) {
                if (e.detail && e.detail.car != null && bizInput) {
                    bizInput.value = e.detail.car;
                    updateDeductPreview();
                }
                if (e.detail && e.detail.home != null) {
                    homePct = Number(e.detail.home);
                    var label = document.getElementById('wfhPctLabel');
                    if (label) label.textContent = homePct + '%';
                    sync();
                }
            });
            sync();
        })();
    </script>
@endsection

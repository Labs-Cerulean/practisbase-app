@php
    $modalId = $modalId ?? 'companyTaxModal';
    $cardId = $cardId ?? 'company-tax';
@endphp
<div id="{{ $cardId }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Corporate tax</div>
            <h2 style="margin: 0.2rem 0 0.25rem; font-size: 1.2rem; color: var(--primary-navy);">
                @if(! empty($companyTax['period_label']))
                    {{ $companyTax['period_label'] }}
                @else
                    Not assessed
                @endif
            </h2>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.85rem; line-height: 1.45; max-width: 40rem;">
                @if(! empty($companyTax['measured_label']))
                    Measured to {{ $companyTax['measured_label'] }}.
                @endif
                {{ $companyTax['assumption'] ?? '' }}
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Liability</div>
            <button type="button" onclick="document.getElementById('{{ $modalId }}').style.display='flex'" style="background: none; border: none; padding: 0; font-size: 1.6rem; font-weight: 700; color: var(--primary-navy); cursor: pointer; border-bottom: 1px dotted var(--primary-navy);">€{{ number_format((float) ($companyTax['liability'] ?? 0), 2) }}</button>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $companyTax['working'] ?? '' }}</div>
        </div>
    </div>

    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.45rem;">Receipt of the math</div>
    <div style="display: flex; flex-direction: column; gap: 0.55rem;">
        @foreach($companyTax['receipt'] ?? [] as $line)
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; padding-bottom: 0.45rem; border-bottom: 1px dashed #e2e8f0; {{ ! empty($line['strong']) ? 'border-bottom: none; margin-top: 0.25rem;' : '' }}">
                <div style="min-width: 0;">
                    <div style="font-weight: {{ ! empty($line['strong']) ? '700' : '600' }}; color: var(--primary-navy); font-size: 0.88rem;">{{ $line['label'] }}</div>
                    @if(! empty($line['detail']))
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; margin-top: 0.15rem;">{{ $line['detail'] }}</div>
                    @endif
                </div>
                <div style="font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: {{ ! empty($line['strong']) ? '700' : '600' }}; color: var(--primary-navy);">
                    @if($line['amount'] !== null)
                        €{{ number_format((float) $line['amount'], 2) }}
                    @elseif(! empty($line['text']))
                        {{ $line['text'] }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <p style="margin: 0.85rem 0 0; font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">{{ $companyTax['notice'] ?? '' }}</p>
</div>

<div id="{{ $modalId }}" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: var(--radius-lg); width: 90%; max-width: 520px; max-height: 85vh; overflow: auto; padding: 1.5rem 1.6rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
        <button type="button" onclick="document.getElementById('{{ $modalId }}').style.display='none'" style="position: absolute; top: 0.75rem; right: 0.85rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8;">&times;</button>
        <h3 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.15rem;">Corporate tax</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 1rem;">{{ $companyTax['working'] ?? '' }}</p>
        <div style="display: flex; flex-direction: column; gap: 0.7rem;">
            @foreach($companyTax['receipt'] ?? [] as $line)
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; padding-bottom: 0.45rem; border-bottom: 1px dashed #e2e8f0;">
                    <div>
                        <div style="font-weight: {{ ! empty($line['strong']) ? '700' : '600' }}; color: var(--primary-navy); font-size: 0.88rem;">{{ $line['label'] }}</div>
                        @if(! empty($line['detail']))
                            <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; margin-top: 0.15rem;">{{ $line['detail'] }}</div>
                        @endif
                    </div>
                    <div style="font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 700; color: var(--primary-navy);">
                        @if($line['amount'] !== null)
                            €{{ number_format((float) $line['amount'], 2) }}
                        @elseif(! empty($line['text']))
                            {{ $line['text'] }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p style="margin: 1rem 0 0; font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">{{ $companyTax['assumption'] ?? '' }} {{ $companyTax['notice'] ?? '' }}</p>
        <div style="margin-top: 1rem; text-align: right;">
            <button type="button" onclick="document.getElementById('{{ $modalId }}').style.display='none'" style="padding: 0.5rem 1.1rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Close</button>
        </div>
    </div>
</div>
<script>
    (function () {
        var modal = document.getElementById(@json($modalId));
        if (!modal || modal.dataset.bound === '1') {
            return;
        }
        modal.dataset.bound = '1';
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    })();
</script>

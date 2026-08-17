@php
    $type = $entry['model']->entry_type;
    $chrome = \App\Models\ClinicalEntry::typeChrome($type);
    $expandShare = (bool) ($expandShare ?? false);
@endphp
<div class="entry-row"
     id="entry-{{ $entry['model']->id }}"
     data-type="{{ $type }}"
     style="background: {{ $chrome['card_bg'] }}; border: 1px solid {{ $chrome['border'] }}; border-left: 6px solid {{ $chrome['accent'] }}; border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
        <div style="flex: 1; min-width: 180px;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.35rem;">
                <span style="display: inline-block; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.28rem 0.65rem; border-radius: 4px;">
                    {{ $entry['type_label'] }}
                </span>
                @if($entry['is_stampable'])
                    @if($entry['is_issued'])
                        <span style="font-size: 0.7rem; font-weight: 800; color: #065f46; text-transform: uppercase; background: #d1fae5; padding: 0.2rem 0.5rem; border-radius: 4px;">Issued</span>
                    @else
                        <span style="font-size: 0.7rem; font-weight: 800; color: #92400e; text-transform: uppercase; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px;">Draft</span>
                    @endif
                @endif
            </div>
            <strong style="color: var(--primary-navy); font-size: 1.05rem;">{{ $entry['title'] }}</strong>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                {{ $entry['model']->entry_date->format('d M Y') }}
                @if($type === 'certificate' && !empty($entry['certificate_kind_label']))
                    · {{ $entry['certificate_kind_label'] }}
                @endif
                @if($type === 'certificate' && !empty($entry['subject_name']))
                    · Subject: {{ $entry['subject_name'] }}
                @endif
                @if($type === 'referral' && !empty($entry['referred_to']))
                    · To: {{ $entry['referred_to'] }}
                @endif
                @if(!empty($entry['expires_on']))
                    · Expires {{ \Illuminate\Support\Carbon::parse($entry['expires_on'])->format('d M Y') }}
                @endif
                @if($entry['is_issued'])
                    · Issued {{ $entry['issued_at']->format('d M Y H:i') }}
                    @if(!empty($entry['issue_code']))
                        · <span style="font-family: ui-monospace, monospace; letter-spacing: 0.04em; color: var(--primary-navy); font-weight: 700;">{{ $entry['issue_code'] }}</span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if($type === 'prescription' && ! empty($entry['medicines']))
        <div style="margin-top: 0.75rem; display: grid; gap: 0.55rem;">
            @foreach($entry['medicines'] as $mi => $med)
                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.7rem 0.85rem;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ $mi + 1 }}. Medicine</div>
                    <div style="font-weight: 700; color: var(--primary-navy); margin-top: 0.15rem;">
                        {{ $med['name'] }}
                        @if(($med['strength'] ?? '') !== '')
                            <span style="font-weight: 600; color: var(--text-muted);"> · {{ $med['strength'] }}</span>
                        @endif
                    </div>
                    @if(($med['dose'] ?? '') !== '')
                        <div style="font-size: 0.85rem; margin-top: 0.2rem;"><span style="color: var(--text-muted);">Dose:</span> {{ $med['dose'] }}</div>
                    @endif
                    @if(($med['quantity'] ?? '') !== '')
                        <div style="font-size: 0.85rem;"><span style="color: var(--text-muted);">Qty:</span> {{ $med['quantity'] }}</div>
                    @endif
                    @if(($med['instructions'] ?? '') !== '')
                        <div style="font-size: 0.85rem; white-space: pre-wrap;"><span style="color: var(--text-muted);">Notes:</span> {{ $med['instructions'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif(!empty($entry['fields']) && is_array($entry['fields']))
        @php
            if (!empty($entry['field_defs']) && is_array($entry['field_defs'])) {
                $fieldDefs = $entry['field_defs'];
            } else {
                $fieldDefs = \App\Support\ClinicalNoteTemplates::fieldsListFromMap(
                    \App\Support\ClinicalNoteTemplates::fields($entry['template'] ?? 'general')
                );
            }
            $templateLabel = $entry['template_name']
                ?? (\App\Support\ClinicalNoteTemplates::builtinOptions()[$entry['template'] ?? ''] ?? 'Consult');
        @endphp
        <div style="margin-top: 0.65rem; display: grid; gap: 0.55rem;">
            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                {{ $templateLabel }}
            </div>
            @foreach($fieldDefs as $def)
                @php $fieldKey = is_array($def) ? ($def['key'] ?? '') : ''; @endphp
                @if($fieldKey !== '' && trim((string) ($entry['fields'][$fieldKey] ?? '')) !== '')
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">{{ $def['label'] ?? $fieldKey }}</div>
                        <div style="white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['fields'][$fieldKey] }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div style="margin-top: 0.65rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['body'] }}</div>
    @endif

    @if($entry['is_stampable'])
        <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: {{ $chrome['soft'] }}; border: 1px solid {{ $chrome['border'] }}; border-radius: var(--radius-md); font-size: 0.8rem; color: var(--text-main);">
            @if($entry['is_issued'])
                Issued {{ $entry['issue_code'] }} — confirm and share when ready.
            @else
                Confirm with Stamp &amp; issue — then Share appears.
            @endif
        </div>
    @endif

    <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        @if($entry['is_editable'])
            <a href="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/edit"
               style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                Edit
            </a>
        @endif

        @if($entry['is_stampable'] && ! $entry['is_issued'])
            <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/issue"
                  method="post"
                  style="margin: 0; display: inline;">
                @csrf
                <button type="submit"
                        formmethod="post"
                        formaction="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/issue"
                        onclick="return confirm('Confirm and stamp this document? It locks with an issue code on the PDF and cannot be edited afterwards.');"
                        style="padding: 0.4rem 0.75rem; background: {{ $chrome['badge_bg'] }}; color: {{ $chrome['badge_fg'] }}; border: none; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">
                    Stamp &amp; issue
                </button>
            </form>
        @endif

        @if($entry['is_stampable'] && $entry['is_issued'])
            @include('pro.medical._issued-share', [
                'pdfUrl' => '/pro/medical/patients/'.$patient->id.'/entries/'.$entry['model']->id.'/pdf',
                'issueCode' => $entry['issue_code'] ?? '',
                'docLabel' => ($entryTypes ?? \App\Models\ClinicalEntry::TYPES)[$entry['model']->entry_type] ?? 'Document',
                'patientTel' => $payload['tel'] ?? '',
                'patientEmail' => $payload['email'] ?? '',
                'expanded' => $expandShare,
            ])
        @endif
    </div>

    @if(!empty($entry['attachments']) && count($entry['attachments']))
        <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">Attachments</div>
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($entry['attachments'] as $att)
                    <li style="margin-bottom: 0.25rem;">
                        <a href="/pro/medical/patients/{{ $patient->id }}/attachments/{{ $att['id'] }}/download"
                           style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">
                            {{ $att['name'] }}
                        </a>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"> · {{ $att['mime'] }} · {{ number_format($att['byte_size'] / 1024, 1) }} KB ciphertext</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($entry['is_editable'])
        <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/attachments"
              method="POST"
              enctype="multipart/form-data"
              style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
            @csrf
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">
                Add photo / scan
                @include('partials.help-tip', ['text' => 'JPEG, PNG, WebP, or PDF · max 10 MB. Stored encrypted in your vault.'])
            </label>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required
                       style="font-size: 0.85rem;">
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Upload</button>
            </div>
        </form>
    @endif
</div>

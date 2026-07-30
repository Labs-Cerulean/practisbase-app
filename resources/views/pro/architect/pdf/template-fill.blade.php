<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $template['title'] }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        h2 { font-size: 12px; margin: 16px 0 6px; text-transform: uppercase; letter-spacing: 0.04em; color: #334155; }
        .meta { color: #475569; font-size: 10px; margin-bottom: 14px; }
        .box { border: 1px solid #cbd5e1; padding: 10px 12px; margin-bottom: 10px; }
        .label { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.04em; }
        .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .row td { vertical-align: top; padding: 4px 8px 4px 0; }
        .sign { margin-top: 28px; }
        .sign-line { border-top: 1px solid #334155; width: 240px; margin-top: 40px; padding-top: 6px; font-size: 10px; color: #475569; }
        .note { font-size: 9px; color: #64748b; margin-top: 16px; }
        .party { margin-bottom: 4px; }
        ul { margin: 6px 0 0 16px; padding: 0; }
    </style>
</head>
<body>
    <div class="meta">PractisBase filled draft · {{ $today }} · Professional aid only. Attach or transfer into the official BCA form where required.</div>
    <h1>{{ $template['title'] }}</h1>

    <table class="row">
        <tr>
            <td style="width: 50%;">
                <div class="label">Perit</div>
                <div class="value">{{ $perit_name }}@if($perit_postnominals) · {{ $perit_postnominals }}@endif</div>
                @if($perit_warrant)<div>Warrant No. {{ $perit_warrant }}</div>@endif
                @if($perit_email)<div>{{ $perit_email }}</div>@endif
                @if($perit_phone)<div>{{ $perit_phone }}</div>@endif
            </td>
            <td style="width: 50%;">
                <div class="label">Client / developer</div>
                <div class="value">{{ $client_name ?: '—' }}</div>
                @if($client_id_card)<div>ID {{ $client_id_card }}</div>@endif
                @if($client_phone)<div>{{ $client_phone }}</div>@endif
                @if($client_address)<div>{{ $client_address }}</div>@endif
            </td>
        </tr>
    </table>

    <div class="box">
        <table class="row" style="margin: 0;">
            <tr>
                <td style="width: 33%;">
                    <div class="label">Project</div>
                    <div class="value">{{ $project_name ?: '—' }}</div>
                    @if($project_reference)<div>Ref {{ $project_reference }}</div>@endif
                </td>
                <td style="width: 33%;">
                    <div class="label">PA number</div>
                    <div class="value">{{ $pa_number ?: '—' }}</div>
                    @if($pa_title)<div>{{ $pa_title }}</div>@endif
                </td>
                <td style="width: 33%;">
                    <div class="label">Site</div>
                    <div class="value">{{ $site_address ?: '—' }}</div>
                    @if($commencement_date)<div>Commencement {{ $commencement_date }}</div>@endif
                </td>
            </tr>
        </table>
    </div>

    @if(in_array($template['key'], ['declaration_ln136_out_of_scope', 'declaration_reg26_not_affecting', 'architect_progress_declaration', 'architect_supervision_declaration', 'architect_completion_declaration'], true))
        <h2>Declaration</h2>
        <div class="box">
            @if($template['key'] === 'declaration_ln136_out_of_scope')
                I, the undersigned perit in charge of the project, declare that the works do not fall under any of the Regulation 4 criteria of Legal Notice 136 of 2019 (excavation affecting third party property; demolition/removal abutting third party property; additional storeys/load-bearing works over third party property; new buildings/storeys adjacent to third-party property), and hence are not subject to those provisions.
            @elseif($template['key'] === 'declaration_reg26_not_affecting')
                I, the perit in charge of the project, hereby certify that the proposed structural interventions will not affect third party property save for minor damages that could occur, and hence regulations 4, 5, 6, 7 and 8 of the legal notice do not apply.
            @elseif($template['key'] === 'architect_progress_declaration')
                I, the undersigned perit, declare that the works being carried out on site are in accordance with the approved development planning permit referenced above, subject to any conditions therein.
            @elseif($template['key'] === 'architect_supervision_declaration')
                I, the undersigned perit, declare that I am supervising the works on the development referenced above in my professional capacity and in accordance with applicable Maltese building and construction regulations.
            @else
                I, the undersigned perit, declare that the works on the development referenced above have reached substantial completion in accordance with the approved permit, subject to snagging and outstanding conditions if any.
            @endif
            @if($extra_text)
                <p style="margin-top: 10px; white-space: pre-wrap;">{{ $extra_text }}</p>
            @endif
            @if($reasons)
                <p style="margin-top: 8px;"><strong>Reasons:</strong> {{ $reasons }}</p>
            @endif
        </div>
    @elseif($template['key'] === 'declaration_condition_reports_complexes')
        <h2>Declaration to BCA</h2>
        <div class="box">
            Permit No. {{ $pa_number ?: '—' }} · Location {{ $site_address ?: '—' }} · Project Perit {{ $perit_name }} · Developer {{ $client_name ?: '—' }}
            <p style="margin-top: 8px;">I, the undersigned architect, declare that <strong>{{ $third_party_count !== '' ? $third_party_count : '____' }}</strong> third party properties are eligible for a condition report in terms of SL 623.06.</p>
            <p>In case of excavation works, I declare that <strong>{{ $complex_count !== '' ? $complex_count : '____' }}</strong> complexes will be affected by the proposed excavation as per the attached block plan.</p>
            @if($extra_text)<p style="white-space: pre-wrap;">{{ $extra_text }}</p>@endif
        </div>
    @elseif($template['key'] === 'site_notice')
        <h2>Site notice board content</h2>
        <div class="box">
            <div><strong>Development title:</strong> {{ $project_name ?: '—' }}</div>
            <div><strong>Permit number:</strong> {{ $pa_number ?: '—' }}</div>
            <div><strong>Works commencement:</strong> {{ $commencement_date ?: '—' }}</div>
            <div><strong>Site manager:</strong> {{ optional($parties->get('site_manager'))->full_name ?: '—' }} · {{ optional($parties->get('site_manager'))->mobile }}</div>
            <div><strong>Lead contractor:</strong> {{ optional($parties->get('contractor_building'))->full_name ?: optional($parties->get('contractor_excavation'))->full_name ?: '—' }}</div>
            <div><strong>Perit / firm:</strong> {{ $perit_name }} · {{ $perit_phone }} · {{ $perit_email }}</div>
            <div><strong>Client:</strong> {{ $client_name ?: '—' }} · {{ $client_phone }}</div>
            <div><strong>OHSA officer:</strong> {{ optional($parties->get('ohsa_officer'))->full_name ?: '—' }} · {{ optional($parties->get('ohsa_officer'))->mobile }}</div>
        </div>
    @elseif(in_array($template['key'], ['site_management_summary', 'change_of_responsibility'], true))
        <h2>Site roles</h2>
        <div class="box">
            @forelse($parties as $party)
                <div class="party"><strong>{{ $party->roleLabel() }}:</strong> {{ $party->full_name }} @if($party->mobile)· {{ $party->mobile }}@endif @if($party->licence_number)· Licence {{ $party->licence_number }}@endif</div>
            @empty
                <div>No site parties saved on this project yet. Add them under the project Site team, then regenerate.</div>
            @endforelse
            @if($extra_text)<p style="margin-top: 8px; white-space: pre-wrap;">{{ $extra_text }}</p>@endif
        </div>
    @elseif(str_starts_with($template['key'], 'method_statement_'))
        <h2>Method statement draft pack</h2>
        <div class="box">
            <div><strong>Commencement date of works:</strong> {{ $commencement_date ?: ($start_date ?: '—') }}</div>
            @if($works_description)<p style="margin-top: 8px; white-space: pre-wrap;"><strong>Works / methodology notes:</strong> {{ $works_description }}</p>@endif
            @if($extra_text)<p style="white-space: pre-wrap;">{{ $extra_text }}</p>@endif
            <p style="margin-top: 8px;">Use this draft with the official BCA schedule blank (download from the templates library) and attach numbered appendices for drawings, photos and calculations.</p>
        </div>
    @elseif(in_array($template['key'], ['work_outside_hours_exemption', 'summer_break_exemption'], true))
        <h2>Exemption request draft</h2>
        <div class="box">
            <div><strong>PA Number:</strong> {{ $pa_number ?: '—' }}</div>
            <div><strong>Site address:</strong> {{ $site_address ?: '—' }}</div>
            <div><strong>Duration:</strong> {{ $start_date ?: '—' }} to {{ $end_date ?: '—' }}</div>
            @if($works_description)<p style="margin-top: 8px; white-space: pre-wrap;"><strong>Description of works:</strong> {{ $works_description }}</p>@endif
            @if($extra_text)<p style="white-space: pre-wrap;"><strong>Reasons / notes:</strong> {{ $extra_text }}</p>@endif
            @if($mitigation)<p style="white-space: pre-wrap;"><strong>Mitigation:</strong> {{ $mitigation }}</p>@endif
        </div>
    @elseif($template['key'] === 'ds_clearance_application')
        <h2>Dangerous Structures clearance letter draft</h2>
        <div class="box">
            <p>CEO<br>Building and Construction Authority<br>(Att: Permitting Section)</p>
            <p>Subject: DS {{ $ds_number !== '' ? $ds_number : 'XXXXX/YY' }} - Address: {{ $site_address ?: 'Door Number, Street Name, Locality' }}</p>
            <p>Following the issuance of the Dangerous Structure Authorization in caption, I the undersigned Perit in charge, am hereby notifying the Building and Construction Authority of the intention to undertake the remedial works as soon as possible.</p>
            <p>I am also attaching herewith the responsibility forms (detailed and summary) which include the details of all responsible persons together with a declaration of insurance (certificate only) as per SL623.11.</p>
            @if($extra_text)<p style="white-space: pre-wrap;">{{ $extra_text }}</p>@endif
            <p>Regards<br>Perit {{ $perit_name }}</p>
        </div>
    @else
        <div class="box">
            @if($extra_text)<p style="white-space: pre-wrap;">{{ $extra_text }}</p>
            @else<p>Filled data pack for {{ $template['title'] }}.</p>@endif
        </div>
    @endif

    <div class="sign">
        <div class="label">Signature</div>
        <div class="sign-line">{{ $perit_name }} · Warrant {{ $perit_warrant ?: '—' }} · Date {{ $today }}</div>
    </div>

    <div class="note">
        This PDF is a PractisBase working draft prefilled from your practice records. Where BCA requires submission on an official locked form, transfer the values into the blank downloaded from the templates library and retain both on the PA document register.
    </div>
</body>
</html>

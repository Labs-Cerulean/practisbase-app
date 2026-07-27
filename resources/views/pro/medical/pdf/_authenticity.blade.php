{{-- Shared authenticity mark for stampable clinical PDFs. Requires $entry with issue_code + issued_at. --}}
<div class="auth-box">
    <div class="auth-label">Authenticity mark</div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 12px;">
                <div class="auth-caption">Unique issue code</div>
                <div class="auth-code">{{ $entry->issue_code }}</div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <div class="auth-caption">Issued on</div>
                <div class="auth-date">{{ $entry->issued_at->format('d M Y') }}</div>
                <div class="auth-time">{{ $entry->issued_at->format('H:i') }}</div>
            </td>
        </tr>
    </table>
    <div class="auth-note">
        This code identifies a single issued original. Photocopies, reprints, or reuse of the same code outside the issuing practice should be verified against the practitioner register.
    </div>
</div>

@extends('layouts.legal')

@section('page_title', 'Master Service Agreement')

@section('content')
    <h1>Master Service Agreement</h1>
    <p class="legal-meta">Effective date: pending final counsel review · PractisBase · Cerulean Labs Limited</p>

    <div class="legal-note">
        This page is a structured holding draft for closed beta. Replace the body with the counsel-reviewed text generated from
        <code>docs/gemini-privacy-msa-prompt.md</code> before public launch. The scroll-box summary on registration remains binding for acceptance until this full MSA is published.
    </div>

    <h2>1. Parties &amp; acceptance</h2>
    <p>
        This MSA is between you (a self-employed sole trader in Malta) and Cerulean Labs Limited for use of PractisBase.
        Creating an account and accepting terms constitutes agreement to this MSA and the <a href="/privacy" style="color: var(--primary-cerulean); font-weight: 600;">Privacy Policy</a>.
    </p>

    <h2>2. Sole-trader scope</h2>
    <p>
        PractisBase is for Maltese sole traders only — not Ltd companies, public companies, corporate partnerships as separate legal persons,
        VAT groups, or employer payroll/FSS systems. Company books elsewhere in the product (if enabled) are an internal Cerulean Labs tool, not a sold corporate accounting product for customers.
    </p>

    <h2>3. No professional advice</h2>
    <p>
        PractisBase provides administrative, templating, and data tools only. It does not provide medical, architectural, engineering, tax, accounting, or legal advice.
        You remain solely responsible for professional judgments, filings, certifications, prescriptions, and any figures submitted to authorities or clients.
    </p>

    <h2>4. Fiscal helpers</h2>
    <p>
        Tax, SSC, VAT (Article 10 / Article 11), TA22, and provisional tax figures are helpers based on your inputs and configured rates.
        They are not a substitute for a warrant / CPA / tax practitioner. Verify before filing.
    </p>

    <h2>5. Medical vault &amp; profession modules</h2>
    <p>
        Encrypted vault features may make recovery impossible without your recovery code. Protect credentials, recovery codes, and trusted devices.
        Profession templates do not replace your statutory or professional obligations.
    </p>

    <h2>6. Community feedback</h2>
    <p>
        Suggestions and bug reports submitted via Community Feedback may be used to improve PractisBase. We may reply in-thread and mark items as acknowledged, in progress, implemented, deferred, or closed.
        Do not include sensitive patient or client personal data in feedback.
    </p>

    <h2>7. Acceptable use, IP, liability</h2>
    <p>
        Full clauses on acceptable use, intellectual property, fees, suspension, limitation of liability, indemnity, termination, and governing law (Malta)
        will appear in the counsel-reviewed MSA. Until then, the registration summary and this outline govern beta access.
    </p>
@endsection

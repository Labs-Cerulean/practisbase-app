@extends('layouts.legal')

@section('page_title', 'Privacy Policy')

@section('content')
    <h1>Privacy Policy</h1>
    <p class="legal-meta">Effective date: pending final counsel review · Controller for platform data: Cerulean Labs Limited (Malta)</p>

    <div class="legal-note">
        This page is a structured holding draft for closed beta. Replace the body with the counsel-reviewed text generated from
        <code>docs/gemini-privacy-msa-prompt.md</code> before public launch. Registration still requires acceptance of the MSA &amp; Privacy summary shown at sign-up.
    </div>

    <h2>1. Who we are</h2>
    <p>
        PractisBase is operated by Cerulean Labs Limited. For privacy questions contact
        <strong>[PRIVACY CONTACT EMAIL]</strong>. This notice explains how we process personal data when you use PractisBase.
    </p>

    <h2>2. Two roles</h2>
    <ul>
        <li><strong>Your client / patient / project content:</strong> you are the data controller; we act as processor to host and process that content on your instructions.</li>
        <li><strong>Your account, billing, support, and community feedback:</strong> Cerulean Labs is the controller.</li>
    </ul>

    <h2>3. Data we process as controller</h2>
    <p>Account identity and contact details, profession and plan settings, authentication and security events, support messages, community feedback threads, and limited usage/diagnostics needed to run the service.</p>

    <h2>4. Special category / clinical data</h2>
    <p>
        Optional medical vault features are designed so clinical content remains under your controllership, with encryption and recovery-code controls.
        Do not paste patient-identifying clinical details into community feedback.
    </p>

    <h2>5. Purposes &amp; legal bases</h2>
    <p>Contract performance (providing the service), legitimate interests (security, product improvement from feedback), legal obligation where applicable, and consent where we specifically ask for it.</p>

    <h2>6. Sharing</h2>
    <p>We use vetted subprocessors for hosting, email, monitoring, and payments when live. A current list will be published at <strong>[HOSTING / SUBPROCESSORS LIST]</strong>.</p>

    <h2>7. Retention, security, transfers, rights</h2>
    <p>
        We retain data while your account is active and for a reasonable period afterwards, apply appropriate security measures, and restrict international transfers with GDPR safeguards where needed.
        You may exercise GDPR rights (access, rectification, erasure, restriction, portability, objection) and complain to the IDPC (Malta).
    </p>

    <h2>8. Related documents</h2>
    <p>Use of PractisBase is also governed by the <a href="/msa" style="color: var(--primary-cerulean); font-weight: 600;">Master Service Agreement</a>.</p>
@endsection

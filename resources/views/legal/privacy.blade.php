@extends('layouts.legal')

@section('page_title', 'Privacy Policy')

@section('content')
    <h1>PractisBase Privacy Policy</h1>
    <p class="legal-meta">Effective date: 5 August 2026 · Revision R01 · Cerulean Labs Limited (C 116764)</p>

    <h2>1. Introduction and Who We Are</h2>
    <p>1.1. This Privacy Policy explains how Cerulean Labs Limited (&ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;) handles personal data in connection with the PractisBase platform.</p>
    <p>1.2. We are a Maltese company registered as a Limited Liability Company. Our details are:</p>
    <ul>
        <li><strong>Full Legal Name:</strong> Cerulean Labs Limited</li>
        <li><strong>Company Registration Number:</strong> C 116764</li>
        <li><strong>Privacy Contact Email:</strong> <a href="mailto:privacy@labscerulean.com">privacy@labscerulean.com</a></li>
    </ul>

    <h2>2. Our Roles: Controller vs. Processor</h2>
    <p>2.1. Under the General Data Protection Regulation (GDPR) and the Data Protection Act (Cap. 586 of the Laws of Malta), data roles dictate our legal responsibilities and liabilities. It is strictly understood that PractisBase operates under a dual-role structure depending on the data category.</p>
    <p>2.2. <strong>Data Controller:</strong> We act exclusively as a Data Controller for your platform registration, billing details, usage data, and direct support communications. This means we decide how and why this specific subset of administrative data is processed.</p>
    <p>2.3. <strong>Data Processor:</strong> We act strictly as a Data Processor for all client, patient, and project content, as well as all financial ledgers, invoices, and practice data you upload or generate within PractisBase. You (the professional User) are the sole Data Controller for this information. We process this data solely on your instructions to provide the platform&rsquo;s functionality. We accept no controllership, ownership, or liability over the accuracy, legality, or regulatory compliance of your practice&rsquo;s data.</p>

    <h2>3. Personal Data We Collect as a Controller</h2>
    <p>3.1. We collect the following categories of data where we act as a Controller for our own administrative purposes:</p>
    <ul>
        <li><strong>Identity &amp; Contact Data:</strong> Name, email address, profession / professional title, warrant details (where provided), and VAT number (where provided) for account creation and service configuration.</li>
        <li><strong>Financial Data:</strong> Billing information and subscription history (processed securely via our payment sub-processors when billing is active).</li>
        <li><strong>Technical &amp; Usage Data:</strong> IP address, browser type, device information, login times, terms-acceptance records, and platform interaction telemetry to ensure security and performance.</li>
        <li><strong>Feedback &amp; Support Data:</strong> Content of support requests and community feedback / feature suggestions.</li>
    </ul>

    <h2>4. Purposes and Legal Bases for Processing</h2>
    <p>4.1. We process your Controller data (the administrative data outlined in Section 3) on the following legal bases:</p>
    <ul>
        <li><strong>Performance of a Contract:</strong> To register your account, provide access to the Service, and process your subscription payments in accordance with our Master Service Agreement.</li>
        <li><strong>Our Own Legal Obligations:</strong> To comply strictly with Cerulean Labs Limited&rsquo;s own corporate tax, statutory accounting, and anti-money laundering (AML) requirements under Maltese law. This includes retaining your billing information and subscription invoices to satisfy our own corporate audits.</li>
        <li><strong>Legitimate Interests:</strong> To analyse platform usage, improve our product features, maintain network security, and handle community feedback.</li>
        <li><strong>Consent:</strong> Where legally required (e.g., for non-essential cookies or direct marketing). You may withdraw consent at any time.</li>
    </ul>
    <p>4.2. <strong>Explicit Clarification on User Tax &amp; Financial Data:</strong> For the avoidance of doubt, the &ldquo;Legal Obligation&rdquo; basis cited in Section 4.1 refers exclusively to Cerulean Labs Limited&rsquo;s corporate obligations. We do not process, analyse, or audit the financial data, ledgers, or tax figures you input into the platform under any legal obligation to verify your practice&rsquo;s tax compliance. You remain the sole Data Controller and assume absolute liability for your own professional tax declarations, VAT returns, and financial submissions to the Malta Tax and Customs Administration (MTCA) or any other authority.</p>

    <h2>5. Medical Vault, Professional Desks, and Special Category Data</h2>
    <p>5.1. The PractisBase Medical Vault allows medical professionals to store special category personal data (e.g., patient health records).</p>
    <p>5.2. <strong>Your Controllership:</strong> You are strictly the Data Controller for this data. You must ensure you have a lawful basis to process your patients&rsquo; health data.</p>
    <p>5.3. <strong>Vault Encryption:</strong> The Medical Vault uses client-side cryptography. We do not hold the decryption keys and cannot read plaintext clinical content. If you lose your recovery codes, your vault data is irreversibly lost.</p>
    <p>5.4. <strong>Architect/Engineer DMS:</strong> Files uploaded to the Studio and Technical desks remain your intellectual property. We process them solely to provide document management and stamping features.</p>

    <h2>6. Accountant Pack and Third-Party Access</h2>
    <p>6.1. PractisBase provides an accountant export pack that you may download and share with your warranted accountant or tax advisor. You choose what to share; there is currently no separate accountant login seat on the platform.</p>
    <p>6.2. By downloading and sending that pack (or otherwise sharing practice data), you remain the Data Controller of that information. Your accountant acts as an independent Data Controller or authorised processor for their own professional obligations.</p>

    <h2>7. Sharing Data and Sub-processors</h2>
    <p>7.1. We do not sell your personal data.</p>
    <p>7.2. We share data with trusted sub-processors only as needed to host the platform, store files, send transactional email, and process payments.</p>
    <p>7.3. A current list of our sub-processors:</p>
    <ul>
        <li><strong>Railway</strong> — application hosting and database</li>
        <li><strong>Cloudflare</strong> — CDN/security and R2 file storage</li>
        <li><strong>Google Workspace / Google LLC</strong> — transactional email</li>
        <li><strong>Stripe</strong> — billing and payments (when live)</li>
    </ul>

    <h2>8. International Transfers</h2>
    <p>8.1. We prioritise hosting your data within the European Economic Area (EEA). If we transfer data outside the EEA, we ensure appropriate safeguards are in place, such as EU Standard Contractual Clauses (SCCs) or equivalent adequacy decisions.</p>

    <h2>9. Security &amp; Retention</h2>
    <p>9.1. We implement appropriate technical measures to protect data. You are responsible for safeguarding your credentials.</p>
    <p>9.2. Account Data is retained while active. Customer Content is deleted upon account closure, subject to short-term automated backup cycles.</p>

    <h2>10. Your Rights</h2>
    <p>10.1. You have the right to access, rectify, erase, restrict, or object to the processing of your personal data. Email <a href="mailto:privacy@labscerulean.com">privacy@labscerulean.com</a> to exercise these rights.</p>
    <p>10.2. You may lodge a complaint with the Maltese supervisory authority: the Office of the Information and Data Protection Commissioner (IDPC).</p>

    <h2>11. Cookies, Local Storage, and Biometrics</h2>
    <p>11.1. We use cookies and local storage to keep you logged in and ensure platform security.</p>
    <p>11.2. Any biometric unlock feature for the Medical Vault (e.g., fingerprint or facial recognition) operates entirely on your local device. We do not collect, transmit, or store your biometric data on our servers.</p>

    <h2>12. Children&rsquo;s Data</h2>
    <p>12.1. PractisBase is a B2B service for adult professionals. We do not knowingly collect personal data from individuals under 18 years of age.</p>

    <h2>13. Changes to this Policy</h2>
    <p>13.1. We may update this Privacy Policy. We will notify you of material changes via email or an in-app notice. Continued use of the platform after the effective date constitutes acknowledgement of the updated policy.</p>

    <h2>14. Data Processing Summary (Processor Schedule)</h2>
    <p>14.1. When we act as a Data Processor for your Customer Content, we strictly agree to:</p>
    <ul>
        <li>Process the data only on your documented instructions (which includes providing the PractisBase service).</li>
        <li>Ensure our personnel are bound by confidentiality obligations.</li>
        <li>Implement appropriate technical and organisational security measures.</li>
        <li>Only engage sub-processors under a written contract offering equivalent protection, and notify you of changes to our sub-processors.</li>
        <li>Delete or return the data upon termination of your account, subject to backup cycles.</li>
        <li>Cooperate reasonably with audits and inspections mandated by your GDPR obligations.</li>
    </ul>

    <h2>Related document</h2>
    <p>Use of PractisBase is also governed by the <a href="/msa">Master Service Agreement</a>.</p>
@endsection

@extends('layouts.legal')

@section('page_title', 'Master Service Agreement')

@section('content')
    <h1>PractisBase Master Service Agreement</h1>
    <p class="legal-meta">Terms of Service · Effective date: 5 August 2026 · Revision R02 · Cerulean Labs Limited</p>

    <h2>1. Acceptance of Terms</h2>
    <p>
        By registering for, accessing, or using PractisBase (&ldquo;The Service&rdquo;), you agree to be bound by this Master Service Agreement (the &ldquo;Terms&rdquo;).
        You register as a natural person operating a professional practice (including under a trading name). You confirm that you are authorised to accept these Terms for that practice.
        PractisBase records the IP address and timestamp of your acceptance of these Terms at registration.
    </p>

    <h2>2. Intended Users &amp; Entity Scope</h2>
    <p>
        PractisBase is built exclusively for Maltese self-employed sole traders and self-employed professionals (full-time or part-time),
        including medical, architectural, engineering, and similar practices that bill in their own name.
    </p>
    <p>
        The Service is strictly not designed for limited liability companies (Ltds), public companies, corporate partnerships as separate legal persons,
        VAT groups, or employers running payroll/FSS for staff. Those entities must use dedicated company accounting software and professional advisors.
        Using PractisBase for company books, corporate income tax, or company VAT group reporting is expressly outside the intended scope;
        any figures generated in that context are fundamentally invalid. A &ldquo;practice name&rdquo; on your profile is a trading name for your sole-trader activity.
        It does not convert The Service into a company accounting system.
    </p>

    <h2>3. No Professional Advice</h2>
    <p>
        The Service provides administrative, templating, and data management tools only. PractisBase does not provide medical, architectural, engineering, financial, or legal advice.
        The User assumes full, exclusive liability for all clinical diagnoses, structural calculations, certifications, prescriptions, and professional actions facilitated through The Service.
        The Service is never a substitute for professional judgement.
    </p>

    <h2>4. Absolute Tax &amp; Accounting Disclaimers</h2>
    <p>
        While The Service provides ledger management, VAT aids, and tax form automation for common self-employed / sole-trader situations in Malta
        (such as progressive income tax, TA22 where applicable, Class 2 SSC estimates, and Article 10/11 VAT monitoring),
        PractisBase is a software tool, not an accounting firm or a licensed tax advisor.
    </p>
    <p>
        The User assumes sole and absolute responsibility for the accuracy, legality, and completeness of all financial data, tax calculations, omissions, and submissions.
        The Service does not cover all aspects, exemptions, or edge cases of Maltese tax law. You are strictly advised to consult a certified public accountant (CPA)
        or recognised tax advisor prior to submitting any tax returns, VAT returns, or financial declarations to the Malta Tax and Customs Administration (MTCA).
    </p>
    <p>
        Tax tools reflect common sole-trader permutations. Unusual mid-year regime changes, reduced VAT rates, EU/cross-border special schemes,
        and situations outside standard Maltese sole-trader rules require manual adjustment and professional advisor review.
    </p>

    <h2>5. Medical Vault, Encryption &amp; Profession-Specific Risks</h2>
    <p>
        <strong>Medical Vault (Zero-Knowledge):</strong> The Medical Vault employs client-side cryptography. PractisBase does not possess your decryption keys and cannot recover your data.
        If you lose your password and recovery codes, your clinical data is irreversibly lost. The User accepts this absolute risk.
    </p>
    <p>
        <strong>Studio &amp; Technical Desks:</strong> Any document management systems (DMS), certificate templates, or digital stampables are structural aids.
        You are solely responsible for ensuring that all documents signed, stamped, or exported using the platform meet local regulatory and warrant requirements
        (e.g., KTP, Chamber of Engineers). We assume zero liability for structural defects, project delays, or compliance failures.
    </p>

    <h2>6. Data Processing &amp; GDPR Compliance</h2>
    <p>
        In accordance with the EU General Data Protection Regulation (GDPR) and the Data Protection Act (Cap. 586 of the Laws of Malta),
        the User acts as the exclusive &ldquo;Data Controller&rdquo; for all client and patient information uploaded. PractisBase acts solely as the &ldquo;Data Processor&rdquo;.
        We claim no ownership over your client data. You warrant that you have obtained all necessary legal consents from your clients/patients
        to store their sensitive data digitally within The Service.
    </p>

    <h2>7. Limitation of Liability</h2>
    <p>
        To the maximum extent permitted by applicable law, Cerulean Labs Limited, its founders, and affiliates shall not be liable for any indirect, incidental, special,
        consequential, or punitive damages, including without limitation: loss of profits, loss of data, loss of goodwill, malpractice claims, tax penalties, or regulatory fines.
        In no event shall our aggregate liability exceed the total amounts paid by you to PractisBase in the twelve (12) months immediately preceding the event giving rise to the claim.
        If you are utilising a &ldquo;Free Tier&rdquo; or &ldquo;Beta Trial&rdquo;, our total liability is strictly limited to zero Euros (€0.00).
    </p>

    <h2>8. Indemnification</h2>
    <p>
        You agree to defend, indemnify, and hold harmless Cerulean Labs Limited and its employees from and against any claims, damages, obligations, losses, liabilities,
        costs, or debt (including but not limited to legal fees) arising from: (a) your use of and access to The Service; (b) your violation of any term of this Agreement;
        (c) your violation of any third-party right, including without limitation any privacy or intellectual property right; or (d) any claim that your content, professional actions,
        or financial/tax submissions caused damage to a third party, resulted in regulatory action, or incurred tax penalties.
    </p>

    <h2>9. Service Availability &amp; Data Backups</h2>
    <p>
        The Service is provided on an &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo; basis. While we utilise enterprise-grade infrastructure, we do not guarantee absolute immunity from data loss.
        Users are provided with a &ldquo;Data Export&rdquo; tool and are strictly required to maintain their own independent backups of their client and financial records.
        Please note: The standard export tool downloads text and ledger data only; it does not include physical file uploads
        (e.g., PDFs, receipt images, or architectural documents) stored in the cloud.
    </p>

    <h2>10. Governing Law &amp; Jurisdiction</h2>
    <p>
        These Terms shall be governed and construed in accordance with the laws of the Republic of Malta, without regard to its conflict of law provisions.
        Any dispute arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction of the Courts of Malta.
    </p>

    <h2>11. Subscriptions, Fees &amp; Taxes</h2>
    <p>
        PractisBase is offered across various subscription plans (e.g., Free, Standard, Pro). Subscription fees are billed in advance in accordance with your chosen billing cycle.
        Where applicable, Malta Value Added Tax (VAT) will be applied to Cerulean Labs Limited&rsquo;s fees. We reserve the right to modify our pricing by providing you with thirty (30) days&rsquo; prior notice.
        In the event of non-payment, we may suspend your account following reasonable notice. Except where strictly required by Maltese or EU consumer protection law,
        all payments are non-refundable, and we do not issue refunds or credits for partial subscription periods.
        Any closed-beta or complimentary access may be terminated or converted to a paid subscription at our sole discretion, subject to prior notice.
    </p>

    <h2>12. Term &amp; Termination</h2>
    <p>
        This Agreement commences upon your registration and remains in effect for as long as your account is active.
        You may terminate this Agreement at any time by ceasing use of The Service and requesting account closure.
        We reserve the right to suspend or terminate your account immediately for material breach, non-payment, abuse of the platform, significant legal risk, or prolonged inactivity.
        Upon account closure, our obligations as a Data Processor will apply, and we will delete or return your Customer Content in accordance with our Privacy Policy.
        Where technically feasible, we may offer a limited export window (e.g., 14 to 30 days); however, you explicitly acknowledge that any clinical data within the encrypted Medical Vault
        remains unrecoverable without your personal decryption keys, even during an export window.
    </p>

    <h2>13. Force Majeure</h2>
    <p>
        Cerulean Labs Limited shall not be held liable for any delay, failure in performance, or loss of service caused by events beyond our reasonable control.
        This includes, but is not limited to, acts of God, war, pandemics, government actions, utility failures, distributed denial-of-service (DDoS) attacks,
        or outages experienced by third-party infrastructure and sub-processors (e.g., Railway, Cloudflare, Stripe, Google).
        Subscription fees will not be refunded solely as a result of temporary service unavailability caused by such force majeure events.
    </p>

    <h2>14. Acceptable Use</h2>
    <p>
        You agree to use The Service strictly for lawful professional purposes. You shall not: upload unlawful, defamatory, or malicious content;
        attempt to bypass, reverse-engineer, or break the platform&rsquo;s security or Medical Vault cryptography; scrape, data-mine, or resell access to The Service;
        or use PractisBase to manage the accounting of Limited Liability Companies (Ltds) or other corporate entities.
    </p>

    <h2>15. Intellectual Property &amp; Feedback</h2>
    <p>
        Cerulean Labs Limited retains all intellectual property rights, title, and interest in and to The Service, including all underlying software, branding, and default templates.
        You retain all ownership rights to the content and data you upload; however, you grant us a limited, non-exclusive licence to host, store, and process your content
        strictly to operate and provide The Service to you. If you provide any community feedback, feature suggestions, or improvements, you grant us a perpetual, royalty-free,
        and irrevocable licence to incorporate and use such feedback to improve PractisBase, without any obligation to compensate you or implement your suggestions.
    </p>

    <h2>16. Changes to the Terms</h2>
    <p>
        We reserve the right to modify these Terms at any time. We will notify you of any modifications via email or through an in-app notification.
        Your continued use of The Service after the effective date of any such changes constitutes your acceptance of the updated Terms, where lawful.
        Should a material, adverse change be made to these Terms, you maintain the right to terminate your account and cease using The Service before the changes take effect.
    </p>

    <h2>17. Miscellaneous</h2>
    <p>
        <strong>Severability:</strong> If any provision of these Terms is found to be unenforceable or invalid, that provision will be limited or eliminated to the minimum extent necessary
        so that these Terms will otherwise remain in full force and effect.
    </p>
    <p>
        <strong>Entire Agreement:</strong> These Terms, together with our Privacy Policy and official plan pricing pages, constitute the entire agreement between you and Cerulean Labs Limited concerning The Service.
    </p>
    <p>
        <strong>Assignment:</strong> You may not assign or transfer these Terms without our prior written consent; we may freely assign or transfer these Terms,
        including to a successor entity in the event of a merger or acquisition.
    </p>
    <p>
        <strong>Notices:</strong> Legal notices to you will be sent to the email address registered to your account; notices to us must be directed to your designated support or privacy contact email.
    </p>
@endsection

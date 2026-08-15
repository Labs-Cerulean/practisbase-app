<?php

namespace App\Support;

/**
 * Ready to post PractisBase marketing packs for the company Content Studio.
 * Edit copy here. The /company/content page renders screenshot graphics and captions.
 *
 * Copy uses British English. Avoid hyphens and em dashes in user facing marketing text.
 * Links use the standard query shape:
 * https://www.practisbase.com/{path}?utm_source=linkedin&utm_medium=social&utm_campaign={packId}
 * plus promo_code= when a Founding code applies.
 */
class MarketingContentPacks
{
    public const SITE = 'https://www.practisbase.com';

    public const FOUNDING_CODE = 'FOUNDING-J1EDKG';

    /**
     * @param  array<string, scalar|null>  $extra
     */
    public static function link(string $path, string $campaign, array $extra = []): string
    {
        $path = '/'.ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $query = array_merge([
            'utm_source' => 'linkedin',
            'utm_medium' => 'social',
            'utm_campaign' => $campaign,
        ], $extra);

        $query = array_filter($query, fn ($v) => $v !== null && $v !== '');

        return self::SITE.$path.'?'.http_build_query($query);
    }

    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   channel: string,
     *   theme: string,
     *   ready: bool,
     *   kicker: string,
     *   headline: string,
     *   subline: string,
     *   highlight: string|null,
     *   cta: string,
     *   caption: string,
     *   hashtags: string,
     *   tip: string
     * }>
     */
    public static function all(): array
    {
        $code = self::FOUNDING_CODE;

        $packs = [
            [
                'id' => 'launch-founding',
                'label' => 'Launch · Founding 25',
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'Now live',
                'headline' => 'PractisBase is live for Maltese sole traders.',
                'subline' => 'Accounts, VAT, and profession tools built for Malta, not generic SaaS.',
                'highlight' => 'First 25 · 3 months free · '.$code,
                'cta' => 'Register at practisbase.com',
                'caption' => "PractisBase is live.\n\n"
                    ."Built for Maltese sole traders: doctors, architects, engineers, and more. Invoices, Tax & VAT, and real profession tools in one place.\n\n"
                    ."Founding offer: the first 25 users get 3 months free with code {$code} at signup.\n\n"
                    .'{url}',
                'hashtags' => '#PractisBase #Malta #SoleTrader #Founding',
                'tip' => 'Screenshot the graphic below, paste the caption on LinkedIn, attach practisbase.com.',
                'url_path' => '/register',
                'url_extra' => ['promo_code' => $code],
            ],
            [
                'id' => 'malta-not-generic',
                'label' => 'Why Malta',
                'channel' => 'LinkedIn',
                'theme' => 'malta-skyline',
                'ready' => true,
                'kicker' => 'Built for Malta',
                'headline' => 'Not another generic invoicing app.',
                'subline' => 'TA22, SSC bands, Article 10 / 11 VAT, provisional tax: the way Maltese sole traders actually work.',
                'highlight' => null,
                'cta' => 'See Tax & VAT on PractisBase',
                'caption' => "Most “accounting” apps ignore how Malta taxes sole traders.\n\n"
                    ."PractisBase does not. Live fiscal maths for part time (TA22), full time SSC, Article 10 and Article 11 VAT, and provisional tax settlement, with a clear breakdown you can audit.\n\n"
                    .'{url}',
                'hashtags' => '#MaltaTax #SoleTrader #VAT #TA22 #PractisBase',
                'tip' => 'Pair with a screen recording of Tax & VAT on a demo Standard account.',
                'url_path' => '/',
            ],
            [
                'id' => 'doctors-vault',
                'label' => 'Doctors · vault',
                'channel' => 'LinkedIn',
                'theme' => 'medical-clean',
                'ready' => true,
                'kicker' => 'Pro Medical',
                'headline' => 'Patient journals you hold the key to.',
                'subline' => 'Encrypted clinical vault. Recovery code stays with the doctor. We cannot open it.',
                'highlight' => 'Practitioner held encryption',
                'cta' => 'Practice tools for doctors',
                'caption' => "For doctors on PractisBase: clinical journals live in an encrypted vault.\n\n"
                    ."You hold the recovery code. We cannot reset it or read your patient notes. Weekly medical backup keeps you in control.\n\n"
                    ."Billing and Tax & VAT sit alongside, without mixing clinical data into invoices.\n\n"
                    .'{url}',
                'hashtags' => '#DoctorsOfMalta #MedicalPractice #Privacy #PractisBase',
                'tip' => 'Record: vault unlock to patients list (no real patient data).',
                'url_path' => '/',
            ],
            [
                'id' => 'architects-desk',
                'label' => 'Architects · desk',
                'channel' => 'LinkedIn',
                'theme' => 'blueprint-texture',
                'ready' => true,
                'kicker' => 'Pro Architect',
                'headline' => 'Studio tools that match the job.',
                'subline' => 'Clients, projects, BCA templates, condition reports, method statements, and a stamper next to your books.',
                'highlight' => null,
                'cta' => 'Practice tools for periti',
                'caption' => "Architects and periti: PractisBase is not just invoices.\n\n"
                    ."Project desk, BCA aligned templates, documents, condition reports, and stamping, with sole trader Tax & VAT when you need Full Pro.\n\n"
                    .'{url}',
                'hashtags' => '#Perit #Architecture #Malta #BCA #PractisBase',
                'tip' => 'Screenshot BCA templates or a project workspace on a demo arch account.',
                'url_path' => '/',
            ],
            [
                'id' => 'engineers-field',
                'label' => 'Engineers · field',
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'Pro Engineer',
                'headline' => 'Field work tied back to the client.',
                'subline' => 'Projects, PA workspace, equipment certificates, field reports, and site photos: one desk.',
                'highlight' => null,
                'cta' => 'Practice tools for engineers',
                'caption' => "Engineers on PractisBase: client to project to PA, equipment certificates, field reports, and documents in one place.\n\n"
                    ."When you need the books too, Full Pro adds Tax & VAT without switching tools.\n\n"
                    .'{url}',
                'hashtags' => '#Engineering #Malta #Certificates #PractisBase',
                'tip' => 'Screen record the equipment due board or a certificate PDF.',
                'url_path' => '/',
            ],
            [
                'id' => 'plan-ladder',
                'label' => 'Plans ladder',
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'Clear pricing',
                'headline' => 'Start free. Add accounts or practice. Or take both.',
                'subline' => 'Free invoicing to Standard Tax & VAT to Practice tools to Full Pro bundle.',
                'highlight' => 'Full Pro saves vs buying both',
                'cta' => 'See pricing',
                'caption' => "PractisBase plans are a ladder, not a maze:\n\n"
                    ."• Free: invoices & RFPs (5 lifetime clients)\n"
                    ."• Standard: Tax & VAT, expenses, accountant pack\n"
                    ."• Practice: profession tools with free billing underneath\n"
                    ."• Full Pro: both, priced as a bundle\n\n"
                    .'{url}',
                'hashtags' => '#SaaS #Pricing #SoleTrader #PractisBase',
                'tip' => 'Screenshot /pricing or the onboarding plan cards.',
                'url_path' => '/pricing',
            ],
            [
                'id' => 'backup-trust',
                'label' => 'Trust · backups',
                'channel' => 'LinkedIn',
                'theme' => 'financial-chart',
                'ready' => true,
                'kicker' => 'Your data',
                'headline' => 'Weekly backup of your own books.',
                'subline' => 'Download a ZIP of your clients, invoices, and expenses anytime. Doctors keep a separate vault backup.',
                'highlight' => null,
                'cta' => 'Your desk, your export',
                'caption' => "We remind PractisBase users once a week to download their own data.\n\n"
                    ."One click: a ZIP of your clients, ledger, and expenses, scoped to you. Clinical vault backups stay separate and recovery code gated for doctors.\n\n"
                    .'{url}',
                'hashtags' => '#DataOwnership #SoleTrader #PractisBase',
                'tip' => 'Screenshot the calm weekly backup reminder on Overview.',
                'url_path' => '/',
            ],
            [
                'id' => 'one-desk',
                'label' => 'One desk',
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'One product',
                'headline' => 'Invoices, tax maths, and practice tools: same login.',
                'subline' => 'Stop juggling three apps. PractisBase is the sole trader toolkit for Malta.',
                'highlight' => null,
                'cta' => 'Open practisbase.com',
                'caption' => "Invoices in one tab. Tax spreadsheets in another. Profession templates somewhere else.\n\n"
                    ."PractisBase puts the sole trader toolkit in one login, built around Maltese rules from day one.\n\n"
                    .'{url}',
                'hashtags' => '#Productivity #MaltaBusiness #PractisBase',
                'tip' => 'Record a short walk from Overview to invoice to Tax & VAT.',
                'url_path' => '/',
            ],
            [
                'id' => 'founding-reminder',
                'label' => 'Founding code reminder',
                'channel' => 'LinkedIn',
                'theme' => 'gold',
                'ready' => true,
                'kicker' => 'Founding seats',
                'headline' => '3 months free for the first 25.',
                'subline' => 'Use code FOUNDING-J1EDKG at registration. Card billing comes later. Founding access is free now.',
                'highlight' => $code,
                'cta' => 'Register with the code',
                'caption' => "Still seats left on the Founding cohort.\n\n"
                    ."Code: {$code}\n"
                    ."Offer: 3 months free for the first 25 registrations.\n\n"
                    .'{url}',
                'hashtags' => '#Founding #PractisBase #MaltaStartups',
                'tip' => 'Post mid week as a short reminder with the code graphic.',
                'url_path' => '/register',
                'url_extra' => ['promo_code' => $code],
            ],
            [
                'id' => 'built-in-malta',
                'label' => 'Built in Malta',
                'channel' => 'LinkedIn',
                'theme' => 'malta-skyline',
                'ready' => true,
                'kicker' => 'Local product',
                'headline' => 'We built PractisBase for the way Malta works.',
                'subline' => 'Tools for self employed professionals, starting with sole traders.',
                'highlight' => null,
                'cta' => 'practisbase.com',
                'caption' => "PractisBase is built in Malta.\n\n"
                    ."Our focus: self employed professionals who need books and practice tools that respect local fiscal reality, not a US template with a euro sign.\n\n"
                    .'{url}',
                'hashtags' => '#MaltaTech #BuiltInMalta #PractisBase',
                'tip' => 'Good brand intro post; attach logo and launch graphic.',
                'url_path' => '/',
            ],
            [
                'id' => 'leave-excel',
                'label' => 'Leave Excel behind',
                'channel' => 'LinkedIn',
                'theme' => 'financial-chart',
                'ready' => true,
                'kicker' => 'For all sole traders',
                'headline' => 'Leave Excel behind for invoices and VAT.',
                'subline' => 'Spreadsheets are slow, fragile, and easy to get wrong. PractisBase handles the books automatically.',
                'highlight' => 'Invoices · VAT · live totals',
                'cta' => 'Leave the spreadsheet behind',
                'caption' => "Managing invoices and VAT in Excel is risky and slow.\n\n"
                    ."One wrong formula, one missing invoice, one forgotten VAT period, and you are guessing at year end.\n\n"
                    ."PractisBase keeps your ledger, VAT treatment, and totals in one place so you are not rebuilding Malta tax maths in a spreadsheet every month.\n\n"
                    .'{url}',
                'hashtags' => '#SoleTrader #VAT #MaltaBusiness #PractisBase #NoMoreExcel',
                'tip' => 'Screenshot Overview or ledger next to a blank spreadsheet. Keep it professional.',
                'url_path' => '/',
            ],
            [
                'id' => 'bca-compliance',
                'label' => 'BCA compliance',
                'channel' => 'LinkedIn',
                'theme' => 'blueprint-texture',
                'ready' => true,
                'kicker' => 'For periti / architects',
                'headline' => 'BCA paperwork without the panic.',
                'subline' => 'Stop worrying about missing forms. Generate compliant method statements in moments.',
                'highlight' => 'Method statements · BCA templates',
                'cta' => 'Open the architect desk',
                'caption' => "Periti: stop worrying about missing BCA paperwork.\n\n"
                    ."PractisBase helps you generate compliant method statements and keep studio documents beside the project, not scattered across drives and email threads.\n\n"
                    .'{url}',
                'hashtags' => '#Perit #BCA #Architecture #Malta #PractisBase',
                'tip' => 'Screen record creating a method statement from a BCA template.',
                'url_path' => '/',
            ],
            [
                'id' => 'secure-clinic',
                'label' => 'Secure clinic',
                'channel' => 'LinkedIn',
                'theme' => 'medical-clean',
                'ready' => true,
                'kicker' => 'For medical doctors',
                'headline' => 'A secure clinic vault, not a filing cabinet.',
                'subline' => 'Patient data belongs in a GDPR minded encrypted vault, not scattered notes and folders.',
                'highlight' => 'Encrypted · practitioner held key',
                'cta' => 'See Pro Medical',
                'caption' => "Patient data belongs in a secure, GDPR minded vault, not a scattered filing cabinet.\n\n"
                    ."PractisBase Pro Medical keeps clinical journals encrypted with a recovery code you hold. Billing stays separate. We cannot open your notes.\n\n"
                    .'{url}',
                'hashtags' => '#DoctorsOfMalta #GDPR #ClinicalPrivacy #PractisBase',
                'tip' => 'Screenshot vault setup / unlock screens (no patient PII).',
                'url_path' => '/',
            ],
            [
                'id' => 'equipment-certification',
                'label' => 'Equipment certification',
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'For engineers',
                'headline' => 'Built for Maltese equipment certification.',
                'subline' => 'A dedicated engine for engineers to manage and certify equipment, tied to the client and the job.',
                'highlight' => 'Register · due board · certificates',
                'cta' => 'Open the engineer desk',
                'caption' => "Equipment certification should not live in a random folder.\n\n"
                    ."PractisBase gives Maltese engineers a dedicated engine to manage and certify equipment: register, due board, certificates, linked back to the client and project.\n\n"
                    .'{url}',
                'hashtags' => '#Engineering #Malta #Equipment #Certificates #PractisBase',
                'tip' => 'Record the equipment due board and issuing a certificate PDF.',
                'url_path' => '/',
            ],
            [
                'id' => 'ta22-tax-season',
                'label' => 'TA22 / tax season',
                'channel' => 'LinkedIn',
                'theme' => 'financial-chart',
                'ready' => true,
                'kicker' => 'For all sole traders',
                'headline' => 'Stop guessing what you owe the CFR.',
                'subline' => 'Know your tax liability in real time: TA22, SSC, VAT, and provisional tax, with an auditable breakdown.',
                'highlight' => 'Live Tax & VAT report',
                'cta' => 'See the fiscal report',
                'caption' => "Stop guessing your tax liability.\n\n"
                    ."PractisBase shows what you owe towards the CFR in real time, including TA22 for part time work, SSC, VAT, and provisional tax, with a breakdown you can actually check.\n\n"
                    .'{url}',
                'hashtags' => '#TA22 #CFR #MaltaTax #SoleTrader #PractisBase',
                'tip' => 'Screen record opening Tax & VAT and clicking a dotted total for the receipt breakdown.',
                'url_path' => '/',
            ],
            [
                'id' => 'work-from-site',
                'label' => 'Work from the site',
                'channel' => 'LinkedIn',
                'theme' => 'mobile-mockup',
                'ready' => true,
                'kicker' => 'For periti & engineers',
                'headline' => 'Admin from the construction site.',
                'subline' => 'Condition reports, notes, and desk work on your phone, without waiting until you are back at the office.',
                'highlight' => 'Mobile · Add to Home Screen',
                'cta' => 'Take the desk on site',
                'caption' => "Do your admin and condition reports directly from the construction site on your phone.\n\n"
                    ."PractisBase works in the browser on mobile, and you can add it to your Home Screen for one tap return. Capture the job while you are still on site.\n\n"
                    .'{url}',
                'hashtags' => '#Perit #Engineering #SiteWork #Mobile #PractisBase',
                'tip' => 'Film phone and Download app / Add to Home Screen, then open a condition report form.',
                'url_path' => '/',
            ],
            [
                'id' => 'accountants-best-friend',
                'label' => "Accountant's best friend",
                'channel' => 'LinkedIn',
                'theme' => 'hero-blue-gradient',
                'ready' => true,
                'kicker' => 'For all sole traders',
                'headline' => "Be your accountant's favourite client.",
                'subline' => 'Hand over a clean, reconciled ZIP in one click: ledger detail ready for your warranted accountant.',
                'highlight' => 'Accountant pack · one click',
                'cta' => 'Export the pack',
                'caption' => "Be your accountant's favourite client.\n\n"
                    ."PractisBase lets you download a perfectly organised ZIP for your accountant in one click: documents, payments, expenses, and tax payments for the year, ready to hand over.\n\n"
                    .'{url}',
                'hashtags' => '#Accountant #SoleTrader #MaltaBusiness #PractisBase',
                'tip' => 'Screenshot For accountant download flow (Standard+ demo).',
                'url_path' => '/',
            ],
            [
                'id' => 'warrant-stamp',
                'label' => 'Warrant stamp',
                'channel' => 'LinkedIn',
                'theme' => 'blueprint-texture',
                'ready' => true,
                'kicker' => 'For periti & engineers',
                'headline' => 'Your warrant stamp, without the friction.',
                'subline' => 'Apply your official professional warrant stamp to digital documents in a few clicks.',
                'highlight' => 'Digital stamper',
                'cta' => 'Stamp with confidence',
                'caption' => "Apply your official professional warrant stamp to digital documents with zero friction.\n\n"
                    ."PractisBase includes a stamper workflow for periti and engineers so signed and stamped PDFs leave your desk cleanly, not as a scan of a wet stamp on a kitchen counter.\n\n"
                    .'{url}',
                'hashtags' => '#Perit #Engineering #Warrant #Malta #PractisBase',
                'tip' => 'Screen record the stamper on a demo document (no client secrets).',
                'url_path' => '/',
            ],
            [
                'id' => 'vat-threshold',
                'label' => 'VAT threshold',
                'channel' => 'LinkedIn',
                'theme' => 'financial-chart',
                'ready' => true,
                'kicker' => 'For all sole traders',
                'headline' => 'Never sleepwalk past €35k.',
                'subline' => 'Article 11 threshold progress is on your Tax & VAT desk, so you see the warning before CFR day.',
                'highlight' => '€35,000 Article 11 watch',
                'cta' => 'Watch your threshold',
                'caption' => "Never accidentally breach the €35k Article 11 VAT threshold without warning again.\n\n"
                    ."PractisBase tracks billed revenue against the exempt threshold and surfaces a clear notice when you are approaching or over, so you can act before it becomes a scramble.\n\n"
                    .'{url}',
                'hashtags' => '#VAT #Article11 #MaltaTax #SoleTrader #PractisBase',
                'tip' => 'Screenshot the Article 11 threshold progress card on Tax & VAT.',
                'url_path' => '/',
            ],
            [
                'id' => 'local-pride',
                'label' => 'Local pride',
                'channel' => 'LinkedIn',
                'theme' => 'malta-skyline',
                'ready' => true,
                'kicker' => 'Brand & community',
                'headline' => 'Built here. For Maltese sole traders.',
                'subline' => 'Generic foreign software does not know Maltese law. PractisBase is built in Malta, for you.',
                'highlight' => 'Built in Malta',
                'cta' => 'Join PractisBase',
                'caption' => "Generic foreign software does not know Maltese law.\n\n"
                    ."PractisBase is built here, for you: sole trader fiscal reality, profession desks, and how work actually gets done in Malta.\n\n"
                    .'{url}',
                'hashtags' => '#BuiltInMalta #MaltaTech #SoleTrader #PractisBase',
                'tip' => 'Use as a brand and community post; pair with Founding graphic if seats remain.',
                'url_path' => '/',
            ],
        ];

        foreach ($packs as &$pack) {
            $path = $pack['url_path'] ?? '/';
            $extra = $pack['url_extra'] ?? [];
            $url = self::link($path, $pack['id'], $extra);
            $pack['caption'] = str_replace('{url}', $url, $pack['caption']);
            unset($pack['url_path'], $pack['url_extra']);
        }
        unset($pack);

        return $packs;
    }
}

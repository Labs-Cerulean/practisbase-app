<?php

namespace App\Support;

/**
 * Ready-to-post PractisBase marketing packs for the company Content Studio.
 * Edit copy here — the /company/content page renders screenshot graphics + captions.
 */
class MarketingContentPacks
{
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
        $site = 'https://www.practisbase.com';
        $code = 'FOUNDING-J1EDKG';

        return [
            [
                'id' => 'launch-founding',
                'label' => 'Launch · Founding 25',
                'channel' => 'LinkedIn',
                'theme' => 'sea',
                'ready' => true,
                'kicker' => 'Now live',
                'headline' => 'PractisBase is live for Maltese sole traders.',
                'subline' => 'Accounts, VAT, and profession tools — built for Malta, not generic SaaS.',
                'highlight' => 'First 25 · 3 months free · '.$code,
                'cta' => 'Register at practisbase.com',
                'caption' => "PractisBase is live.\n\n"
                    ."Built for Maltese sole traders — doctors, architects, engineers, and more — with invoices, Tax & VAT, and real profession tools in one place.\n\n"
                    ."Founding offer: the first 25 users get 3 months free with code {$code} at signup.\n\n"
                    ."{$site}/register?promo_code={$code}",
                'hashtags' => '#PractisBase #Malta #SoleTrader #Founding #CeruleanLabs',
                'tip' => 'Screenshot the graphic below, paste the caption on LinkedIn, attach practisbase.com.',
            ],
            [
                'id' => 'malta-not-generic',
                'label' => 'Why Malta',
                'channel' => 'LinkedIn',
                'theme' => 'ink',
                'ready' => true,
                'kicker' => 'Built for Malta',
                'headline' => 'Not another generic invoicing app.',
                'subline' => 'TA22, SSC bands, Article 10 / 11 VAT, provisional tax — the way Maltese sole traders actually work.',
                'highlight' => null,
                'cta' => 'See Tax & VAT on PractisBase',
                'caption' => "Most “accounting” apps ignore how Malta taxes sole traders.\n\n"
                    ."PractisBase does not. Live fiscal math for part-time (TA22), full-time SSC, Article 10 and Article 11 VAT, and provisional tax settlement — with a clear breakdown you can audit.\n\n"
                    ."{$site}",
                'hashtags' => '#MaltaTax #SoleTrader #VAT #TA22 #PractisBase',
                'tip' => 'Pair with a screen recording of Tax & VAT on a demo Standard account.',
            ],
            [
                'id' => 'doctors-vault',
                'label' => 'Doctors · vault',
                'channel' => 'LinkedIn',
                'theme' => 'teal',
                'ready' => true,
                'kicker' => 'Pro Medical',
                'headline' => 'Patient journals you hold the key to.',
                'subline' => 'Encrypted clinical vault. Recovery code stays with the doctor. Cerulean Labs cannot open it.',
                'highlight' => 'Practitioner-held encryption',
                'cta' => 'Practice tools for doctors',
                'caption' => "For doctors on PractisBase: clinical journals live in an encrypted vault.\n\n"
                    ."You hold the recovery code. We cannot reset it or read your patient notes. Weekly medical backup keeps you in control.\n\n"
                    ."Billing and Tax & VAT sit alongside — without mixing clinical data into invoices.\n\n"
                    ."{$site}",
                'hashtags' => '#DoctorsOfMalta #MedicalPractice #Privacy #PractisBase',
                'tip' => 'Record: vault unlock → patients list (no real patient data).',
            ],
            [
                'id' => 'architects-desk',
                'label' => 'Architects · desk',
                'channel' => 'LinkedIn',
                'theme' => 'olive',
                'ready' => true,
                'kicker' => 'Pro Architect',
                'headline' => 'Studio tools that match the job.',
                'subline' => 'Clients, projects, BCA templates, condition reports, method statements, and a stamper — next to your books.',
                'highlight' => null,
                'cta' => 'Practice tools for periti',
                'caption' => "Architects and periti: PractisBase is not just invoices.\n\n"
                    ."Project desk, BCA-aligned templates, documents, condition reports, and stamping — with sole-trader Tax & VAT when you need Full Pro.\n\n"
                    ."{$site}",
                'hashtags' => '#Perit #Architecture #Malta #BCA #PractisBase',
                'tip' => 'Screenshot BCA templates or a project workspace on a demo arch account.',
            ],
            [
                'id' => 'engineers-field',
                'label' => 'Engineers · field',
                'channel' => 'LinkedIn',
                'theme' => 'sky',
                'ready' => true,
                'kicker' => 'Pro Engineer',
                'headline' => 'Field work tied back to the client.',
                'subline' => 'Projects, PA workspace, equipment certificates, field reports, and site photos — one desk.',
                'highlight' => null,
                'cta' => 'Practice tools for engineers',
                'caption' => "Engineers on PractisBase: client → project → PA, equipment certificates, field reports, and documents in one place.\n\n"
                    ."When you need the books too, Full Pro adds Tax & VAT without switching tools.\n\n"
                    ."{$site}",
                'hashtags' => '#Engineering #Malta #Certificates #PractisBase',
                'tip' => 'Screen-record the equipment due board or a certificate PDF.',
            ],
            [
                'id' => 'plan-ladder',
                'label' => 'Plans ladder',
                'channel' => 'LinkedIn',
                'theme' => 'mist',
                'ready' => true,
                'kicker' => 'Clear pricing',
                'headline' => 'Start free. Add accounts or practice. Or take both.',
                'subline' => 'Free invoicing → Standard Tax & VAT → Practice tools → Full Pro bundle.',
                'highlight' => 'Full Pro saves vs buying both',
                'cta' => 'See pricing',
                'caption' => "PractisBase plans are a ladder, not a maze:\n\n"
                    ."• Free — invoices & RFPs (5 lifetime clients)\n"
                    ."• Standard — Tax & VAT, expenses, accountant pack\n"
                    ."• Practice — profession tools with free billing underneath\n"
                    ."• Full Pro — both, priced as a bundle\n\n"
                    ."{$site}/pricing",
                'hashtags' => '#SaaS #Pricing #SoleTrader #PractisBase',
                'tip' => 'Screenshot /pricing or the onboarding plan cards.',
            ],
            [
                'id' => 'backup-trust',
                'label' => 'Trust · backups',
                'channel' => 'LinkedIn',
                'theme' => 'ink',
                'ready' => true,
                'kicker' => 'Your data',
                'headline' => 'Weekly backup of your own books.',
                'subline' => 'Download a ZIP of your clients, invoices, and expenses anytime. Doctors keep a separate vault backup.',
                'highlight' => null,
                'cta' => 'Your desk, your export',
                'caption' => "We remind PractisBase users once a week to download their own data.\n\n"
                    ."One click: a ZIP of your clients, ledger, and expenses — scoped to you. Clinical vault backups stay separate and recovery-code gated for doctors.\n\n"
                    ."{$site}",
                'hashtags' => '#DataOwnership #SoleTrader #PractisBase',
                'tip' => 'Screenshot the calm weekly backup reminder on Overview.',
            ],
            [
                'id' => 'one-desk',
                'label' => 'One desk',
                'channel' => 'LinkedIn',
                'theme' => 'sea',
                'ready' => true,
                'kicker' => 'One product',
                'headline' => 'Invoices, tax math, and practice tools — same login.',
                'subline' => 'Stop juggling three apps. PractisBase is the sole-trader toolkit for Malta.',
                'highlight' => null,
                'cta' => 'Open practisbase.com',
                'caption' => "Invoices in one tab. Tax spreadsheets in another. Profession templates somewhere else.\n\n"
                    ."PractisBase puts the sole-trader toolkit in one login — built around Maltese rules from day one.\n\n"
                    ."{$site}",
                'hashtags' => '#Productivity #MaltaBusiness #PractisBase #CeruleanLabs',
                'tip' => 'Record a short walk from Overview → invoice → Tax & VAT.',
            ],
            [
                'id' => 'founding-reminder',
                'label' => 'Founding code reminder',
                'channel' => 'LinkedIn',
                'theme' => 'gold',
                'ready' => true,
                'kicker' => 'Founding seats',
                'headline' => '3 months free for the first 25.',
                'subline' => 'Use code FOUNDING-J1EDKG at registration. Card billing comes later — Founding access is free now.',
                'highlight' => $code,
                'cta' => 'Register with the code',
                'caption' => "Still seats left on the Founding cohort.\n\n"
                    ."Code: {$code}\n"
                    ."Offer: 3 months free for the first 25 registrations.\n\n"
                    ."{$site}/register?promo_code={$code}",
                'hashtags' => '#Founding #PractisBase #MaltaStartups',
                'tip' => 'Post mid-week as a short reminder with the code graphic.',
            ],
            [
                'id' => 'cerulean-labs',
                'label' => 'About Cerulean',
                'channel' => 'LinkedIn',
                'theme' => 'mist',
                'ready' => true,
                'kicker' => 'Cerulean Labs Ltd',
                'headline' => 'We built PractisBase for the way Malta works.',
                'subline' => 'A Maltese product company shipping tools for self-employed professionals — starting with sole traders.',
                'highlight' => null,
                'cta' => 'practisbase.com',
                'caption' => "PractisBase is built by Cerulean Labs Ltd in Malta.\n\n"
                    ."Our focus: self-employed professionals who need books and practice tools that respect local fiscal reality — not a US template with a euro sign.\n\n"
                    ."{$site}",
                'hashtags' => '#CeruleanLabs #MaltaTech #BuiltInMalta #PractisBase',
                'tip' => 'Good company-page intro post; attach logo + launch graphic.',
            ],
        ];
    }
}

<?php

namespace App\Support;

use App\Models\User;

/**
 * Entitlements:
 * - Free financial: clients + invoices (lifetime client cap)
 * - Standard financial: Tax & VAT, expenses, accountant, unlimited clients, branding
 * - Practice tools: med/arch/eng profession packages
 *
 * Tiers:
 * - free → free financial only
 * - standard → Standard financial only
 * - practice-{med|arch|eng} → Practice tools + Free financial (land-and-expand)
 * - pro-{med|arch|eng} → Practice tools + Standard financial (full)
 */
class TierPolicy
{
    public const FREE_CLIENT_LIFETIME_CAP = 5;

    public const PRICE_STANDARD = '15.99';

    public const PRICE_PRACTICE = '24.99';

    public const PRICE_PRO = '34.99';

    /** Maltese standard VAT rate shown on public pricing (ex-VAT list prices). */
    public const VAT_RATE_PERCENT = '18';

    /** Separate Standard + Practice list price minus Full Pro (marketing). */
    public static function bundleSavingsEuro(): string
    {
        $separate = (float) self::PRICE_STANDARD + (float) self::PRICE_PRACTICE;
        $pro = (float) self::PRICE_PRO;

        return number_format(max(0, $separate - $pro), 2);
    }

    /** Short suffix under paid list prices, e.g. "+ 18% VAT". */
    public static function priceVatSuffix(): string
    {
        return '+ '.self::VAT_RATE_PERCENT.'% VAT';
    }

    /** Gross monthly price including Maltese VAT, e.g. "18.87". */
    public static function priceIncludingVat(string $exVat): string
    {
        $gross = (float) $exVat * (1 + ((float) self::VAT_RATE_PERCENT / 100));

        return number_format(round($gross, 2), 2);
    }

    /** One-line public pricing disclaimer. */
    public static function pricingVatDisclaimer(): string
    {
        return 'List prices exclude '.self::VAT_RATE_PERCENT.'% Maltese VAT.';
    }

    public const TIER_FREE = 'free';

    public const TIER_STANDARD = 'standard';

    public const TIER_PRACTICE_MED = 'practice-med';

    public const TIER_PRACTICE_ARCH = 'practice-arch';

    public const TIER_PRACTICE_ENG = 'practice-eng';

    public const TIER_PRO_MED = 'pro-med';

    public const TIER_PRO_ARCH = 'pro-arch';

    public const TIER_PRO_ENG = 'pro-eng';

    /** @return list<string> */
    public static function allTiers(): array
    {
        return [
            self::TIER_FREE,
            self::TIER_STANDARD,
            self::TIER_PRACTICE_MED,
            self::TIER_PRACTICE_ARCH,
            self::TIER_PRACTICE_ENG,
            self::TIER_PRO_MED,
            self::TIER_PRO_ARCH,
            self::TIER_PRO_ENG,
        ];
    }

    public static function normalize(?string $tier): string
    {
        $tier = $tier ?: self::TIER_FREE;

        return in_array($tier, self::allTiers(), true) ? $tier : self::TIER_FREE;
    }

    public static function validationRule(): string
    {
        return 'required|in:'.implode(',', self::allTiers());
    }

    public static function isPaid(User $user): bool
    {
        return self::normalize($user->tier) !== self::TIER_FREE;
    }

    /** Full Pro (Standard financial + practice tools). */
    public static function isPro(User $user): bool
    {
        return str_starts_with(self::normalize($user->tier), 'pro-');
    }

    public static function isPracticeOnly(User $user): bool
    {
        return str_starts_with(self::normalize($user->tier), 'practice-');
    }

    public static function hasPracticeTools(User $user): bool
    {
        $tier = self::normalize($user->tier);

        return str_starts_with($tier, 'practice-') || str_starts_with($tier, 'pro-');
    }

    /** Tax & VAT, expenses, accountant, unlimited clients, invoice logo. */
    public static function hasStandardFinancial(User $user): bool
    {
        $tier = self::normalize($user->tier);

        return $tier === self::TIER_STANDARD || str_starts_with($tier, 'pro-');
    }

    public static function tierHasStandardFinancial(string $tier): bool
    {
        $tier = self::normalize($tier);

        return $tier === self::TIER_STANDARD || str_starts_with($tier, 'pro-');
    }

    public static function tierHasPracticeTools(string $tier): bool
    {
        $tier = self::normalize($tier);

        return str_starts_with($tier, 'practice-') || str_starts_with($tier, 'pro-');
    }

    public static function proPackage(User $user): ?string
    {
        return self::packageForTier(self::normalize($user->tier));
    }

    public static function packageForTier(string $tier): ?string
    {
        return match (self::normalize($tier)) {
            self::TIER_PRACTICE_MED, self::TIER_PRO_MED => 'med',
            self::TIER_PRACTICE_ARCH, self::TIER_PRO_ARCH => 'arch',
            self::TIER_PRACTICE_ENG, self::TIER_PRO_ENG => 'eng',
            default => null,
        };
    }

    public static function canAccessReports(User $user): bool
    {
        return self::hasStandardFinancial($user);
    }

    /** PDF Document Stamper: Standard accounts, Practice, or Full Pro. */
    public static function canAccessDocumentStamper(User $user): bool
    {
        return self::hasStandardFinancial($user) || self::hasPracticeTools($user);
    }

    public static function canAccessStandardTools(User $user): bool
    {
        return self::hasStandardFinancial($user);
    }

    public static function canAccessProPackage(User $user, string $package): bool
    {
        if (! self::hasPracticeTools($user)) {
            return false;
        }

        $package = strtolower($package);
        $expectedProfession = match ($package) {
            'med' => 'Medical Professional',
            'arch' => 'Architect / Perit',
            'eng' => 'Engineer',
            default => null,
        };

        if ($expectedProfession === null) {
            return false;
        }

        return self::proPackage($user) === $package
            && ($user->profession ?: '') === $expectedProfession;
    }

    public static function lifetimeClientCount(User $user): int
    {
        return (int) ($user->clients_created_count ?? 0);
    }

    public static function hasUnlimitedClients(User $user): bool
    {
        return self::hasStandardFinancial($user);
    }

    public static function canAddClient(User $user): bool
    {
        if (self::hasUnlimitedClients($user)) {
            return true;
        }

        return self::lifetimeClientCount($user) < self::FREE_CLIENT_LIFETIME_CAP;
    }

    public static function allowedTiersForProfession(?string $profession): array
    {
        $tiers = [self::TIER_FREE, self::TIER_STANDARD];

        return match ($profession) {
            'Medical Professional' => array_merge($tiers, [
                self::TIER_PRACTICE_MED,
                self::TIER_PRO_MED,
            ]),
            'Architect / Perit' => array_merge($tiers, [
                self::TIER_PRACTICE_ARCH,
                self::TIER_PRO_ARCH,
            ]),
            'Engineer' => array_merge($tiers, [
                self::TIER_PRACTICE_ENG,
                self::TIER_PRO_ENG,
            ]),
            default => $tiers,
        };
    }

    public static function assertTierAllowedForProfession(User $user, string $tier): void
    {
        $tier = self::normalize($tier);
        $allowed = self::allowedTiersForProfession($user->profession);

        if (! in_array($tier, $allowed, true)) {
            abort(403, 'That plan is not available for your registered profession. Contact support if your profession needs updating.');
        }
    }

    public static function meetsMinimumTier(User $user, array $allowedTiers): bool
    {
        $tier = self::normalize($user->tier);

        // "standard" means Standard financial access (Standard or Full Pro — not Practice-only).
        if (in_array('standard', $allowedTiers, true) && self::hasStandardFinancial($user)) {
            return true;
        }

        return in_array($tier, $allowedTiers, true);
    }

    /**
     * Capability score for upgrade/downgrade UI.
     * free=0, practice=1, standard=2, full pro=3.
     */
    public static function tierRank(string $tier): int
    {
        $tier = self::normalize($tier);

        if (str_starts_with($tier, 'pro-')) {
            return 3;
        }
        if ($tier === self::TIER_STANDARD) {
            return 2;
        }
        if (str_starts_with($tier, 'practice-')) {
            return 1;
        }

        return 0;
    }

    public static function isDowngrade(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        $losesFinancial = self::tierHasStandardFinancial($from) && ! self::tierHasStandardFinancial($to);
        $losesPractice = self::tierHasPracticeTools($from) && ! self::tierHasPracticeTools($to);

        return $losesFinancial || $losesPractice;
    }

    public static function label(string $tier): string
    {
        return match (self::normalize($tier)) {
            self::TIER_FREE => 'Free',
            self::TIER_STANDARD => 'Standard',
            self::TIER_PRACTICE_MED => 'Practice Medical',
            self::TIER_PRACTICE_ARCH => 'Practice Architect',
            self::TIER_PRACTICE_ENG => 'Practice Engineer',
            self::TIER_PRO_MED => 'Pro Medical',
            self::TIER_PRO_ARCH => 'Pro Architect',
            self::TIER_PRO_ENG => 'Pro Engineer',
            default => ucwords(str_replace('-', ' ', $tier)),
        };
    }

    public static function priceLabel(string $tier): string
    {
        return match (self::normalize($tier)) {
            self::TIER_FREE => '€0',
            self::TIER_STANDARD => '€'.self::PRICE_STANDARD.' + VAT',
            self::TIER_PRACTICE_MED, self::TIER_PRACTICE_ARCH, self::TIER_PRACTICE_ENG => '€'.self::PRICE_PRACTICE.' + VAT',
            self::TIER_PRO_MED, self::TIER_PRO_ARCH, self::TIER_PRO_ENG => '€'.self::PRICE_PRO.' + VAT',
            default => '',
        };
    }

    /**
     * @return list<string>
     */
    public static function changeConsequences(string $from, string $to): array
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return [];
        }

        $notes = [];

        if (self::isDowngrade($from, $to)) {
            $notes[] = 'Access changes from '.self::label($from).' to '.self::label($to).'. Existing data is not deleted, but some tools stop immediately.';
        }

        $fromPractice = self::tierHasPracticeTools($from);
        $toPractice = self::tierHasPracticeTools($to);
        $fromFinancial = self::tierHasStandardFinancial($from);
        $toFinancial = self::tierHasStandardFinancial($to);

        if ($fromPractice && ! $toPractice && ! $fromFinancial && $toFinancial) {
            $notes[] = 'You are swapping Practice tools for Standard accounts (Tax & VAT). Clinical/project tools will lock until you return to Practice or Full Pro.';
        } elseif (! $fromPractice && $toPractice && $fromFinancial && ! $toFinancial) {
            $notes[] = 'You are swapping Standard accounts for Practice tools. Tax & VAT / unlimited clients stop; Free invoicing (5 lifetime clients) remains.';
        }

        if ($fromPractice && ! $toPractice) {
            $notes[] = 'Practice tools become inaccessible (patients, stampables, DMS, stamper, certificates). Medical vault ciphertext stays retained and locked — re-upgrade later still needs your recovery code.';
            $notes[] = 'Vault unlock and medical backup stay unavailable until you return to a Medical practice or Pro plan.';
        }

        if ($fromFinancial && ! $toFinancial) {
            $notes[] = 'Tax & VAT, Expenses, Accountant download, and custom invoice branding become inaccessible.';
            $notes[] = 'You keep Overview + invoices with a lifetime cap of '.self::FREE_CLIENT_LIFETIME_CAP.' clients. Existing clients stay visible; deletes do not free slots.';
        }

        if (! $fromFinancial && $toFinancial) {
            $notes[] = 'Unlocks Tax & VAT, Expenses, Accountant pack, custom branding, and unlimited clients.';
        }

        if (! $fromPractice && $toPractice) {
            $notes[] = 'Unlocks your profession practice tools (clinical / projects / certificates).';
        }

        if (str_starts_with($to, 'practice-') && ! $toFinancial) {
            $notes[] = 'Practice keeps the Free financial layer (5 lifetime clients + invoices). Add Full Pro later for Tax & VAT.';
        }

        if (str_starts_with($to, 'pro-')) {
            $notes[] = 'Full Pro includes Standard financial tools plus your profession package (save €'.self::bundleSavingsEuro().'/mo vs buying both).';
        }

        if (! self::isDowngrade($from, $to) && self::tierRank($to) >= self::tierRank($from)) {
            $notes[] = 'Upgrade to '.self::label($to).'. No card charge during Founding access.';
        }

        return array_values(array_unique($notes));
    }
}

{{-- Legacy alias: type-specific templates live under pro/medical/pdf/. Kept so old references do not break. --}}
@include(match($entry->entry_type ?? '') {
    'referral' => 'pro.medical.pdf.referral',
    'certificate' => 'pro.medical.pdf.certificate',
    default => 'pro.medical.pdf.prescription',
})

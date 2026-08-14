<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\MedicalSpecialty;
use DateTimeImmutable;
use Tests\TestCase;

class MedicalSpecialtyTest extends TestCase
{
    public function test_unknown_specialty_falls_back_to_general(): void
    {
        $this->assertSame('', MedicalSpecialty::normalize('cardiology'));
        $this->assertSame('', MedicalSpecialty::normalize(null));
        $this->assertSame('og', MedicalSpecialty::normalize('og'));
        $this->assertFalse(MedicalSpecialty::isOg(''));
        $this->assertTrue(MedicalSpecialty::isOg('og'));
        $this->assertSame('Obstetrics & Gynaecology', MedicalSpecialty::label('og'));
        $this->assertSame('General (any specialty)', MedicalSpecialty::label(''));
    }

    public function test_only_medical_professionals_with_og_see_proforma(): void
    {
        $ogDoctor = new User([
            'profession' => 'Medical Professional',
            'medical_specialty' => 'og',
        ]);
        $gp = new User([
            'profession' => 'Medical Professional',
            'medical_specialty' => '',
        ]);
        $architect = new User([
            'profession' => 'Architect / Perit',
            'medical_specialty' => 'og',
        ]);

        $this->assertTrue(MedicalSpecialty::userIsOg($ogDoctor));
        $this->assertTrue($ogDoctor->isOgClinician());
        $this->assertFalse(MedicalSpecialty::userIsOg($gp));
        $this->assertFalse(MedicalSpecialty::userIsOg($architect));
        $this->assertFalse(MedicalSpecialty::userIsOg(null));
    }

    public function test_general_patient_update_does_not_wipe_og_history(): void
    {
        $existing = [
            'display_name' => 'Jane Debono',
            'gynae_hx' => 'PCOS',
            'obs_hx' => 'G2P1',
            'pmhx' => 'Asthma',
            'secret_key' => 'keep-me',
        ];

        $merged = MedicalSpecialty::mergePatientPayload($existing, [
            'display_name' => 'Jane Debono',
            'pmhx' => 'Asthma, hypothyroidism',
            'phone' => '79201234',
        ], false);

        $this->assertSame('PCOS', $merged['gynae_hx']);
        $this->assertSame('G2P1', $merged['obs_hx']);
        $this->assertSame('Asthma, hypothyroidism', $merged['pmhx']);
        $this->assertSame('79201234', $merged['phone']);
        $this->assertSame('keep-me', $merged['secret_key']);
    }

    public function test_og_merge_writes_gynae_and_obs_history(): void
    {
        $merged = MedicalSpecialty::mergePatientPayload([
            'display_name' => 'Jane',
        ], [
            'display_name' => 'Jane Debono',
            'gynae_hx' => 'Fibroids',
            'obs_hx' => 'NVD 2021',
            'lmp' => '01/08/2026',
            'id_number' => '123456M',
        ], true);

        $this->assertSame('Jane Debono', $merged['display_name']);
        $this->assertSame('Fibroids', $merged['gynae_hx']);
        $this->assertSame('NVD 2021', $merged['obs_hx']);
        $this->assertSame('01/08/2026', $merged['lmp']);
        $this->assertSame('123456M', $merged['id_number']);
    }

    public function test_og_consult_composes_searchable_body_and_defaults_title(): void
    {
        $payload = MedicalSpecialty::buildJournalPayload([
            'consult_kind' => 'clerking',
            'lmp' => '12/03/2026',
            'presenting_complaint' => 'Pelvic pain for 3 weeks',
            'exam' => 'Soft abdomen',
            'ultrasound' => 'Left ovarian cyst 4cm',
            'plan' => 'Review in 6 weeks',
            'consult_notes' => '',
        ], true);

        $this->assertSame('clerking', $payload['consult_kind']);
        $this->assertSame('Pelvic pain for 3 weeks', $payload['title']);
        $this->assertStringContainsString('LMP:', $payload['body']);
        $this->assertStringContainsString('US:', $payload['body']);
        $this->assertStringContainsString('Left ovarian cyst 4cm', $payload['body']);
        $this->assertTrue(MedicalSpecialty::hasStructuredConsult($payload));
        $this->assertSame('Full clerking', MedicalSpecialty::consultKindLabel($payload['consult_kind']));
    }

    public function test_follow_up_defaults_title_when_no_complaint(): void
    {
        $payload = MedicalSpecialty::buildJournalPayload([
            'consult_kind' => 'follow_up',
            'consult_notes' => 'Pain settled. Scan NAD.',
        ], true);

        $this->assertSame('Follow-up', $payload['title']);
        $this->assertStringContainsString('Pain settled', $payload['body']);
        $this->assertTrue(MedicalSpecialty::journalHasContent([
            'consult_notes' => 'Pain settled. Scan NAD.',
        ], true));
        $this->assertFalse(MedicalSpecialty::journalHasContent([], true));
    }

    public function test_general_journal_payload_stays_title_and_body(): void
    {
        $payload = MedicalSpecialty::buildJournalPayload([
            'title' => 'Review',
            'body' => 'BP 120/80',
            'lmp' => 'should-not-store',
        ], false);

        $this->assertSame(['title' => 'Review', 'body' => 'BP 120/80'], $payload);
        $this->assertFalse(MedicalSpecialty::hasStructuredConsult($payload));
    }

    public function test_filled_history_order_matches_proforma_for_og(): void
    {
        $rows = MedicalSpecialty::filledHistoryRows([
            'pmhx' => 'HTN',
            'gynae_hx' => 'Menorrhagia',
            'obs_hx' => 'G1P1',
            'dhx' => 'Nil',
        ], true);

        $this->assertSame(['Gynae Hx', 'Obs Hx', 'PMHx', 'DHx'], array_column($rows, 'label'));
        $this->assertSame(['Menorrhagia', 'G1P1', 'HTN', 'Nil'], array_column($rows, 'value'));

        $generic = MedicalSpecialty::filledHistoryRows([
            'pmhx' => 'HTN',
            'gynae_hx' => 'hidden-from-gp',
        ], false);
        $this->assertSame(['PMHx'], array_column($generic, 'label'));
    }

    public function test_age_prefers_dob_and_falls_back_to_approx(): void
    {
        $asOf = new DateTimeImmutable('2026-08-14');
        $this->assertSame('34', MedicalSpecialty::ageLabel('1992-03-01', '99', $asOf));
        $this->assertSame('41', MedicalSpecialty::ageLabel(null, '41', $asOf));
        $this->assertNull(MedicalSpecialty::ageLabel(null, null, $asOf));
        $this->assertNull(MedicalSpecialty::ageLabel('2099-01-01', null, $asOf));
    }

    public function test_consult_field_labels_put_lmp_and_us_only_for_og(): void
    {
        $og = array_keys(MedicalSpecialty::consultFieldLabels(true));
        $this->assertSame(['lmp', 'presenting_complaint', 'exam', 'ultrasound', 'plan'], $og);

        $general = array_keys(MedicalSpecialty::consultFieldLabels(false));
        $this->assertSame(['presenting_complaint', 'exam', 'plan'], $general);
    }
}

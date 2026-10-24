<?php

namespace App\Support;

/**
 * Plain-language blocks for an editable Word patient report.
 *
 * @phpstan-type Block array{kind: string, text: string}
 * @phpstan-type HistoryEntry array{
 *   date: string,
 *   type_label: string,
 *   title?: string,
 *   body?: string,
 *   medicines?: list<array{name: string, strength?: string, dose?: string, quantity?: string, instructions?: string}>,
 *   referred_to?: ?string,
 *   certificate_kind_label?: ?string,
 *   issue_code?: ?string,
 *   archived?: bool
 * }
 */
class PatientReport
{
    /**
     * @param  array{name?: string, postnominals?: string, profession?: string, registration?: string, email?: string, phone?: string, address?: string}  $doctor
     * @param  array{display_name?: string, date_of_birth?: string, age?: string, id_card?: string, tel?: string, email?: string, address?: string}  $patient
     * @param  list<HistoryEntry>  $history
     * @return list<Block>
     */
    public static function blocks(array $doctor, array $patient, array $history): array
    {
        $blocks = [
            ['kind' => 'h1', 'text' => 'Patient report'],
            ['kind' => 'p', 'text' => 'Draft. Edit this in Word before sending it to the patient, and rewrite shorthand into everyday language.'],
            ['kind' => 'h2', 'text' => 'Doctor'],
        ];

        foreach (self::filledLines(self::doctorLines($doctor)) as $line) {
            $blocks[] = ['kind' => 'p', 'text' => $line];
        }

        $blocks[] = ['kind' => 'h2', 'text' => 'Patient'];
        foreach (self::filledLines(self::patientLines($patient)) as $line) {
            $blocks[] = ['kind' => 'p', 'text' => $line];
        }

        $blocks[] = ['kind' => 'h2', 'text' => 'History'];
        $entries = array_values(array_filter($history, fn (array $entry) => empty($entry['archived'])));

        if ($entries === []) {
            $blocks[] = ['kind' => 'p', 'text' => 'No clinical entries on this chart yet.'];

            return $blocks;
        }

        foreach ($entries as $entry) {
            $heading = trim((string) ($entry['date'] ?? ''));
            $label = trim((string) ($entry['type_label'] ?? 'Entry'));
            $heading = $heading !== '' ? $heading.' — '.$label : $label;
            $kind = trim((string) ($entry['certificate_kind_label'] ?? ''));
            if ($kind !== '' && $kind !== $label) {
                $heading .= ' ('.$kind.')';
            }
            $issued = trim((string) ($entry['issue_code'] ?? ''));
            if ($issued !== '') {
                $heading .= ' · Issued '.$issued;
            }
            $blocks[] = ['kind' => 'h2', 'text' => $heading];

            $referred = trim((string) ($entry['referred_to'] ?? ''));
            if ($referred !== '') {
                $blocks[] = ['kind' => 'p', 'text' => 'Referred to: '.$referred];
            }

            $medicineText = self::medicineText($entry['medicines'] ?? []);
            if ($medicineText !== '') {
                $blocks[] = ['kind' => 'p', 'text' => $medicineText];
            }

            $body = trim((string) ($entry['body'] ?? ''));
            if ($body !== '') {
                $blocks[] = ['kind' => 'p', 'text' => $body];
            } elseif ($medicineText === '' && $referred === '') {
                $blocks[] = ['kind' => 'p', 'text' => '(No text recorded.)'];
            }
        }

        return $blocks;
    }

    /**
     * @param  array{name?: string, postnominals?: string, profession?: string, registration?: string, email?: string, phone?: string, address?: string}  $doctor
     * @return list<string>
     */
    public static function doctorLines(array $doctor): array
    {
        $lines = [];
        foreach (['name', 'postnominals', 'profession', 'registration', 'email', 'phone', 'address'] as $key) {
            $value = trim((string) ($doctor[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            if ($key === 'address') {
                foreach (preg_split("/\r\n|\n|\r/", $value) ?: [] as $part) {
                    $part = trim((string) $part);
                    if ($part !== '') {
                        $lines[] = $part;
                    }
                }
                continue;
            }
            $lines[] = match ($key) {
                'registration' => 'Medical Reg Nº: '.$value,
                'phone' => 'Tel: '.$value,
                default => $value,
            };
        }

        return $lines;
    }

    /**
     * @param  array{display_name?: string, date_of_birth?: string, age?: string, id_card?: string, tel?: string, email?: string, address?: string}  $patient
     * @return list<string>
     */
    public static function patientLines(array $patient): array
    {
        $lines = [];
        $name = trim((string) ($patient['display_name'] ?? ''));
        if ($name !== '') {
            $lines[] = $name;
        }
        $dob = trim((string) ($patient['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $lines[] = 'Date of birth: '.$dob;
        }
        $age = trim((string) ($patient['age'] ?? ''));
        if ($age !== '') {
            $lines[] = 'Age: '.$age;
        }
        $id = trim((string) ($patient['id_card'] ?? ''));
        if ($id !== '') {
            $lines[] = 'ID card: '.$id;
        }
        $tel = trim((string) ($patient['tel'] ?? ''));
        if ($tel !== '') {
            $lines[] = 'Tel: '.$tel;
        }
        $email = trim((string) ($patient['email'] ?? ''));
        if ($email !== '') {
            $lines[] = 'Email: '.$email;
        }
        $address = trim((string) ($patient['address'] ?? ''));
        if ($address !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $address) ?: [] as $part) {
                $part = trim((string) $part);
                if ($part !== '') {
                    $lines[] = $part;
                }
            }
        }

        return $lines;
    }

    /**
     * @param  list<array{name?: string, strength?: string, dose?: string, quantity?: string, instructions?: string}>  $medicines
     */
    public static function medicineText(array $medicines): string
    {
        $chunks = [];
        foreach ($medicines as $medicine) {
            if (! is_array($medicine)) {
                continue;
            }
            $name = trim((string) ($medicine['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $strength = trim((string) ($medicine['strength'] ?? ''));
            $lines = [trim($name.($strength !== '' ? ' '.$strength : ''))];
            $dose = trim((string) ($medicine['dose'] ?? ''));
            if ($dose !== '') {
                $lines[] = 'Dose: '.$dose;
            }
            $quantity = trim((string) ($medicine['quantity'] ?? ''));
            if ($quantity !== '') {
                $lines[] = 'Quantity: '.$quantity;
            }
            $instructions = trim((string) ($medicine['instructions'] ?? ''));
            if ($instructions !== '') {
                $lines[] = $instructions;
            }
            $chunks[] = implode("\n", $lines);
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function filledLines(array $lines): array
    {
        return $lines === [] ? [''] : $lines;
    }
}

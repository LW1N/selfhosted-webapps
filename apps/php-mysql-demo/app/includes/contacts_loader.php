<?php
declare(strict_types=1);

/**
 * Load contacts from CSV file(s) in data/contacts/.
 * Expected CSV header: name,role,email,phone,discord
 * All output must be escaped by the caller (e.g. htmlspecialchars) when rendering.
 *
 * @return array<int, array{name: string, role: string, email: string, phone: string, discord: string}>
 */
function load_contacts_from_files(): array
{
    $contactsDir = __DIR__ . '/../data/contacts';
    $contacts = [];

    if (!is_dir($contactsDir)) {
        return $contacts;
    }

    $expectedHeader = ['name', 'role', 'email', 'phone', 'discord'];
    $files = glob($contactsDir . '/*.csv');

    if ($files === false) {
        return $contacts;
    }

    foreach ($files as $file) {
        if (!is_readable($file)) {
            continue;
        }
        $handle = fopen($file, 'r');
        if ($handle === false) {
            continue;
        }
        try {
            $header = fgetcsv($handle);
            if ($header === false || array_map('strtolower', array_map('trim', $header)) !== $expectedHeader) {
                continue; // skip files with wrong or missing header
            }
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 5) {
                    continue;
                }
                $name   = trim($row[0] ?? '');
                $role   = trim($row[1] ?? '');
                $email  = trim($row[2] ?? '');
                $phone  = trim($row[3] ?? '');
                $discord = trim($row[4] ?? '');
                if ($name === '' && $email === '') {
                    continue; // skip empty rows
                }
                $contacts[] = [
                    'name'   => $name,
                    'role'   => $role,
                    'email'  => $email,
                    'phone'  => $phone,
                    'discord' => $discord,
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    return $contacts;
}

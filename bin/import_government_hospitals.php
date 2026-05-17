<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Ambulance;
use App\Models\Hospital;
use App\Models\HospitalStaff;
use App\Models\User;

require __DIR__ . '/../app/bootstrap.php';

final class GovernmentHospitalImporter
{
    private const MINISTRY_BASE_URL = 'https://previousmoh.health.gov.lk/moh_final/english/';
    private const CATEGORY_PAGES = [
        57 => 'National Hospital',
        58 => 'Teaching Hospital',
        59 => 'Provincial General Hospital',
        60 => 'District General Hospital',
        61 => 'Base Hospital Type A',
        62 => 'Base Hospital Type B',
        63 => 'Divisional Hospital Type A',
        64 => 'Divisional Hospital Type B',
        65 => 'Divisional Hospital Type C',
        66 => 'Primary Medical Care Unit',
    ];

    private string $apiKey;
    private Hospital $hospitalModel;
    private Ambulance $ambulanceModel;
    private User $userModel;
    private HospitalStaff $hospitalStaffModel;
    private \PDO $db;
    private array $createdCredentials = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->hospitalModel = new Hospital();
        $this->ambulanceModel = new Ambulance();
        $this->userModel = new User();
        $this->hospitalStaffModel = new HospitalStaff();
        $this->db = Database::connection();
    }

    public function run(): void
    {
        $this->ensureSchema();
        $this->ensureHospitalRoles();

        $imported = 0;
        $updated = 0;
        $skipped = [];

        foreach ($this->fetchOfficialHospitalNames() as $entry) {
            $place = $this->resolvePlace($entry['hospital_name'], $entry['hospital_type']);
            if ($place === null) {
                $skipped[] = $entry;
                continue;
            }

            $payload = [
                'hospital_name' => $entry['hospital_name'],
                'address' => $place['formattedAddress'] ?? ($entry['hospital_name'] . ', Sri Lanka'),
                'latitude' => (float) ($place['location']['latitude'] ?? 0),
                'longitude' => (float) ($place['location']['longitude'] ?? 0),
                'contact_number' => trim((string) ($place['nationalPhoneNumber'] ?? 'N/A')),
                'ownership' => 'government',
                'hospital_type' => $entry['hospital_type'],
                'google_place_id' => $place['id'] ?? null,
                'source_url' => $entry['source_url'],
                'business_status' => $place['businessStatus'] ?? null,
            ];

            if ($payload['latitude'] === 0.0 && $payload['longitude'] === 0.0) {
                $skipped[] = $entry;
                continue;
            }

            $existing = null;
            if (!empty($payload['google_place_id'])) {
                $existing = $this->hospitalModel->findByGooglePlaceId((string) $payload['google_place_id']);
            }
            if ($existing === null) {
                $existing = $this->hospitalModel->findByName($payload['hospital_name']);
            }

            if ($existing === null) {
                $hospitalId = $this->hospitalModel->create($payload);
                $imported++;
            } else {
                $hospitalId = (int) $existing['hospital_id'];
                $this->hospitalModel->updateImported($hospitalId, $payload);
                $updated++;
            }

            $this->ensureHospitalAccounts($hospitalId, $payload['hospital_name']);
            $this->ensureDefaultAmbulance($hospitalId, $payload);
            $this->ensureDefaultAmbulanceCrews($hospitalId, $payload['hospital_name']);
        }

        $reportPath = $this->writeCredentialReport();

        echo "Government hospital import completed." . PHP_EOL;
        echo "Imported: {$imported}" . PHP_EOL;
        echo "Updated: {$updated}" . PHP_EOL;
        echo "Skipped: " . count($skipped) . PHP_EOL;
        if ($skipped !== []) {
            echo "Skipped hospitals:" . PHP_EOL;
            foreach ($skipped as $entry) {
                echo '- ' . $entry['hospital_name'] . ' [' . $entry['hospital_type'] . ']' . PHP_EOL;
            }
        }
        echo "Credential report: {$reportPath}" . PHP_EOL;
    }

    private function ensureSchema(): void
    {
        $statements = [
            "ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS ownership VARCHAR(30) NOT NULL DEFAULT 'government' AFTER contact_number",
            "ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS hospital_type VARCHAR(120) NULL AFTER ownership",
            "ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS google_place_id VARCHAR(191) NULL AFTER hospital_type",
            "ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS source_url VARCHAR(255) NULL AFTER google_place_id",
            "ALTER TABLE hospitals ADD COLUMN IF NOT EXISTS business_status VARCHAR(50) NULL AFTER source_url",
            "CREATE UNIQUE INDEX IF NOT EXISTS uq_hospitals_google_place_id ON hospitals (google_place_id)",
        ];

        foreach ($statements as $statement) {
            $this->db->exec($statement);
        }
    }

    private function ensureHospitalRoles(): void
    {
        $roles = [
            'hospital_admin' => ['Hospital Admin', 'Hospital operations administrator'],
            'hospital_staff' => ['Hospital Dashboard Staff', 'Front desk and emergency board staff'],
        ];

        foreach ($roles as $slug => [$roleName, $description]) {
            $stmt = $this->db->prepare('
                INSERT INTO roles (slug, role_name, description)
                SELECT :slug, :role_name, :description
                FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = :slug_check)
            ');
            $stmt->execute([
                'slug' => $slug,
                'role_name' => $roleName,
                'description' => $description,
                'slug_check' => $slug,
            ]);
        }
    }

    private function fetchOfficialHospitalNames(): array
    {
        $entries = [];

        foreach (self::CATEGORY_PAGES as $pageId => $typeName) {
            $url = self::MINISTRY_BASE_URL . 'g_hospital.php?id=' . $pageId;
            $html = $this->get($url, false);
            if ($html === '') {
                continue;
            }

            preg_match_all('/<p class="small"><a [^>]*>(.*?)<\/a><\/p>/si', $html, $matches);
            foreach ($matches[1] ?? [] as $rawName) {
                $hospitalName = trim(strip_tags(html_entity_decode($rawName, ENT_QUOTES | ENT_HTML5)));
                if ($hospitalName === '') {
                    continue;
                }
                $entries[$hospitalName] = [
                    'hospital_name' => $hospitalName,
                    'hospital_type' => $typeName,
                    'source_url' => $url,
                ];
            }
        }

        ksort($entries);
        return array_values($entries);
    }

    private function resolvePlace(string $hospitalName, string $hospitalType): ?array
    {
        $queries = [$hospitalName . ', Sri Lanka'];
        if (str_contains($hospitalName, '-')) {
            $parts = array_map('trim', explode('-', $hospitalName));
            $lastPart = end($parts);
            if ($lastPart !== false && $lastPart !== '') {
                $queries[] = $lastPart . ' hospital, Sri Lanka';
                $queries[] = $hospitalType . ' ' . $lastPart . ', Sri Lanka';
            }
        }

        foreach (array_unique($queries) as $query) {
            $response = $this->postJson(
                'https://places.googleapis.com/v1/places:searchText',
                [
                    'textQuery' => $query,
                    'regionCode' => 'LK',
                    'languageCode' => 'en',
                    'maxResultCount' => 5,
                ],
                [
                    'X-Goog-Api-Key: ' . $this->apiKey,
                    'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.location,places.nationalPhoneNumber,places.businessStatus,places.primaryType',
                ]
            );

            if (($response['error']['status'] ?? '') === 'PERMISSION_DENIED') {
                $reason = (string) ($response['error']['details'][0]['reason'] ?? '');
                if ($reason === 'API_KEY_HTTP_REFERRER_BLOCKED') {
                    throw new RuntimeException('Google Places API calls are blocked because this API key is restricted to HTTP referrers. Use a server-side key with IP restrictions or no application restriction for the importer.');
                }

                if ($reason === 'SERVICE_DISABLED') {
                    throw new RuntimeException('Google Places API (New) is disabled for the current API key. Enable places.googleapis.com in Google Cloud and run the importer again.');
                }

                $message = (string) ($response['error']['message'] ?? 'Google Places API request failed.');
                throw new RuntimeException($message);
            }

            foreach ($response['places'] ?? [] as $place) {
                $candidateName = strtolower((string) ($place['displayName']['text'] ?? ''));
                $targetName = strtolower($hospitalName);
                if ($candidateName === '') {
                    continue;
                }

                if (str_contains($candidateName, $targetName) || str_contains($targetName, $candidateName)) {
                    return $place;
                }

                if (($place['primaryType'] ?? '') === 'hospital') {
                    return $place;
                }
            }
        }

        return null;
    }

    private function ensureHospitalAccounts(int $hospitalId, string $hospitalName): void
    {
        $accounts = [
            [
                'role_slug' => 'hospital_admin',
                'email' => sprintf('hospital%d.admin@healthfirst.lk', $hospitalId),
                'nic_number' => sprintf('GHA%06d', $hospitalId),
                'full_name' => $hospitalName . ' Admin',
                'designation' => 'Primary Hospital Administrator',
            ],
            [
                'role_slug' => 'hospital_staff',
                'email' => sprintf('hospital%d.desk@healthfirst.lk', $hospitalId),
                'nic_number' => sprintf('GHD%06d', $hospitalId),
                'full_name' => $hospitalName . ' Desk',
                'designation' => 'Emergency Front Desk',
            ],
        ];

        foreach ($accounts as $account) {
            $user = $this->userModel->findByEmail($account['email']);
            if ($user === null) {
                $userId = $this->userModel->createStaff([
                    'role_slug' => $account['role_slug'],
                    'full_name' => $account['full_name'],
                    'nic_number' => $account['nic_number'],
                    'email' => $account['email'],
                    'phone' => 'N/A',
                    'password' => 'Password@123',
                    'date_of_birth' => null,
                    'gender' => null,
                    'address' => $hospitalName,
                ]);
            } else {
                $userId = (int) $user['user_id'];
            }

            $stmt = $this->db->prepare('SELECT 1 FROM hospital_staff WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            if (!$stmt->fetchColumn()) {
                $this->hospitalStaffModel->create($userId, $hospitalId, $account['designation']);
            }

            $this->createdCredentials[] = [
                'hospital_id' => $hospitalId,
                'hospital_name' => $hospitalName,
                'role' => $account['role_slug'],
                'email' => $account['email'],
                'password' => 'Password@123',
            ];
        }
    }

    private function ensureDefaultAmbulance(int $hospitalId, array $hospital): void
    {
        $existing = $this->ambulanceModel->listByHospital($hospitalId);
        $minimumPerHospital = 2;

        for ($index = count($existing) + 1; $index <= $minimumPerHospital; $index++) {
            $this->ambulanceModel->create([
                'hospital_id' => $hospitalId,
                'ambulance_number' => $index === 1
                    ? sprintf('HF-AMB-%04d', $hospitalId)
                    : sprintf('HF-AMB-%04d-%d', $hospitalId, $index),
                'status' => 'available',
                'capacity_stretchers' => 1,
                'current_latitude' => $hospital['latitude'] ?? null,
                'current_longitude' => $hospital['longitude'] ?? null,
            ]);
        }
    }

    private function ensureDefaultAmbulanceCrews(int $hospitalId, string $hospitalName): void
    {
        foreach ($this->ambulanceModel->listByHospital($hospitalId) as $ambulance) {
            $ambulanceId = (int) $ambulance['ambulance_id'];
            if (!empty($ambulance['assigned_paramedic'])) {
                continue;
            }

            $email = sprintf('ambulance%d.paramedic@healthfirst.lk', $ambulanceId);
            $user = $this->userModel->findByEmail($email);
            if ($user === null) {
                $userId = $this->userModel->createStaff([
                    'role_slug' => 'paramedic',
                    'full_name' => $ambulance['ambulance_number'] . ' Paramedic',
                    'nic_number' => sprintf('PAR%06d', $ambulanceId),
                    'email' => $email,
                    'phone' => 'N/A',
                    'password' => 'Password@123',
                    'date_of_birth' => null,
                    'gender' => null,
                    'address' => $hospitalName,
                ]);
            } else {
                $userId = (int) $user['user_id'];
            }

            $stmt = $this->db->prepare('SELECT 1 FROM hospital_staff WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            if (!$stmt->fetchColumn()) {
                $this->hospitalStaffModel->create($userId, $hospitalId, 'Ambulance Crew');
            }

            $this->ambulanceModel->assignStaff($ambulanceId, $userId);
            $this->createdCredentials[] = [
                'hospital_id' => $hospitalId,
                'hospital_name' => $hospitalName,
                'role' => 'paramedic',
                'email' => $email,
                'password' => 'Password@123',
            ];
        }
    }

    private function writeCredentialReport(): string
    {
        $path = BASE_PATH . '/storage/generated/imports/government-hospital-logins.csv';
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to write credential report.');
        }

        fputcsv($handle, ['hospital_id', 'hospital_name', 'role', 'email', 'password']);
        foreach ($this->createdCredentials as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }

    private function get(string $url, bool $verifyPeer = true): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $verifyPeer ? 2 : 0,
            CURLOPT_USERAGENT => 'HealthFirst Importer/1.0',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return is_string($response) ? $response : '';
    }

    private function postJson(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : [];
    }
}

$apiKey = (string) config_value('services.google_maps_api_key', '');
if ($apiKey === '') {
    fwrite(STDERR, 'GOOGLE_MAPS_API_KEY is empty. Add your key to .env before running this importer.' . PHP_EOL);
    exit(1);
}

try {
    (new GovernmentHospitalImporter($apiKey))->run();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Hospital;

final class MapsService
{
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $apiKey = (string) config_value('services.google_maps_api_key', '');
        if ($apiKey === '') {
            return null;
        }

        $response = $this->getJson('https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $query,
            'components' => 'country:LK',
            'key' => $apiKey,
        ]));

        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (($decoded['status'] ?? '') === 'OK' && !empty($decoded['results'][0]['geometry']['location'])) {
            $result = $decoded['results'][0];
            return [
                'latitude' => (float) $result['geometry']['location']['lat'],
                'longitude' => (float) $result['geometry']['location']['lng'],
                'formatted_address' => (string) ($result['formatted_address'] ?? $query),
            ];
        }

        return $this->placeTextSearch($query, $apiKey);
    }

    private function placeTextSearch(string $query, string $apiKey): ?array
    {
        $response = $this->getJson('https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
            'query' => $query . ' Sri Lanka',
            'key' => $apiKey,
        ]));

        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (($decoded['status'] ?? '') !== 'OK' || empty($decoded['results'][0]['geometry']['location'])) {
            return null;
        }

        $result = $decoded['results'][0];
        return [
            'latitude' => (float) $result['geometry']['location']['lat'],
            'longitude' => (float) $result['geometry']['location']['lng'],
            'formatted_address' => (string) ($result['formatted_address'] ?? $result['name'] ?? $query),
        ];
    }

    public function rankHospitals(float $latitude, float $longitude, int $limit = 3): array
    {
        $hospitals = (new Hospital())->shortlistByDistance($latitude, $longitude, $limit, false);
        if ($hospitals === []) {
            return [];
        }

        $apiKey = (string) config_value('services.google_routes_api_key', '');
        if ($apiKey === '') {
            return array_map(fn (array $hospital): array => $this->appendFallbackEta($latitude, $longitude, $hospital), $hospitals);
        }

        $destinations = array_map(static fn (array $hospital): array => [
            'waypoint' => [
                'location' => [
                    'latLng' => [
                        'latitude' => (float) $hospital['latitude'],
                        'longitude' => (float) $hospital['longitude'],
                    ],
                ],
            ],
        ], $hospitals);

        $response = $this->postJson(
            'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix',
            [
                'origins' => [[
                    'waypoint' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                            ],
                        ],
                    ],
                ]],
                'destinations' => $destinations,
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
            ],
            [
                'X-Goog-Api-Key: ' . $apiKey,
                'X-Goog-FieldMask: originIndex,destinationIndex,status,condition,distanceMeters,duration',
            ]
        );

        if ($response === null) {
            return array_map(fn (array $hospital): array => $this->appendFallbackEta($latitude, $longitude, $hospital), $hospitals);
        }

        $matrix = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($response)) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded) && isset($decoded['destinationIndex'])) {
                $matrix[(int) $decoded['destinationIndex']] = $decoded;
            }
        }

        foreach ($hospitals as $index => &$hospital) {
            $element = $matrix[$index] ?? null;
            if ($element && (($element['status']['code'] ?? 0) === 0 || ($element['status'] ?? '') === 'OK')) {
                $hospital['eta_seconds'] = (int) rtrim((string) ($element['duration'] ?? '0s'), 's');
                $hospital['route_distance_meters'] = (int) ($element['distanceMeters'] ?? 0);
            } else {
                $hospital = $this->appendFallbackEta($latitude, $longitude, $hospital);
            }
        }
        unset($hospital);

        usort($hospitals, static fn (array $a, array $b): int => ($a['eta_seconds'] ?? PHP_INT_MAX) <=> ($b['eta_seconds'] ?? PHP_INT_MAX));
        return $hospitals;
    }

    public function computeRoute(float $originLat, float $originLng, float $destinationLat, float $destinationLng): array
    {
        $apiKey = (string) config_value('services.google_routes_api_key', '');
        if ($apiKey === '') {
            return $this->fallbackRoute($originLat, $originLng, $destinationLat, $destinationLng);
        }

        $response = $this->postJson(
            'https://routes.googleapis.com/directions/v2:computeRoutes',
            [
                'origin' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $originLat,
                            'longitude' => $originLng,
                        ],
                    ],
                ],
                'destination' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $destinationLat,
                            'longitude' => $destinationLng,
                        ],
                    ],
                ],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
                'polylineQuality' => 'HIGH_QUALITY',
            ],
            [
                'X-Goog-Api-Key: ' . $apiKey,
                'X-Goog-FieldMask: routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline',
            ]
        );

        if ($response === null) {
            return $this->fallbackRoute($originLat, $originLng, $destinationLat, $destinationLng);
        }

        $decoded = json_decode($response, true);
        $route = $decoded['routes'][0] ?? null;
        if (!$route) {
            return $this->fallbackRoute($originLat, $originLng, $destinationLat, $destinationLng);
        }

        return [
            'eta_seconds' => (int) rtrim((string) ($route['duration'] ?? '0s'), 's'),
            'distance_meters' => (int) ($route['distanceMeters'] ?? 0),
            'encoded_polyline' => $route['polyline']['encodedPolyline'] ?? null,
        ];
    }

    public function fallbackRoute(float $originLat, float $originLng, float $destinationLat, float $destinationLng): array
    {
        $distanceKm = $this->haversine($originLat, $originLng, $destinationLat, $destinationLng);
        $etaSeconds = (int) round(($distanceKm / 35) * 3600);

        return [
            'eta_seconds' => max($etaSeconds, 60),
            'distance_meters' => (int) round($distanceKm * 1000),
            'encoded_polyline' => null,
        ];
    }

    private function appendFallbackEta(float $latitude, float $longitude, array $hospital): array
    {
        $route = $this->fallbackRoute($latitude, $longitude, (float) $hospital['latitude'], (float) $hospital['longitude']);
        $hospital['eta_seconds'] = $route['eta_seconds'];
        $hospital['route_distance_meters'] = $route['distance_meters'];
        return $hospital;
    }

    private function postJson(string $url, array $payload, array $headers): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $statusCode >= 400) {
            return null;
        }

        return $response;
    }

    private function getJson(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $statusCode >= 400) {
            return null;
        }

        return $response;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}

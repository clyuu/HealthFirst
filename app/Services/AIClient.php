<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class AIClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config_value('services.ai_service_url', 'http://127.0.0.1:5001'), '/');
    }

    public function verifyAccident(string $filePath): array
    {
        return $this->multipart('/accident/verify', ['image' => $filePath]);
    }

    public function startInjurySession(int $incidentId, int $userId): array
    {
        return $this->json('/injury/sessions', [
            'incident_id' => $incidentId,
            'started_by_user_id' => $userId,
        ]);
    }

    public function analyzeInjury(int $sessionId, string $filePath): array
    {
        return $this->multipart("/injury/sessions/{$sessionId}/analyze", ['image' => $filePath]);
    }

    public function finalizeInjurySession(int $sessionId, array $context): array
    {
        return $this->json("/injury/sessions/{$sessionId}/finalize", $context);
    }

    private function json(string $path, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $response, true);
        if ($response === false || $statusCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException('AI service request failed.');
        }

        return $decoded;
    }

    private function multipart(string $path, array $files): array
    {
        $payload = [];
        foreach ($files as $key => $filePath) {
            $payload[$key] = new \CURLFile($filePath);
        }

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $response, true);
        if ($response === false || $statusCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException('AI service upload failed.');
        }

        return $decoded;
    }
}


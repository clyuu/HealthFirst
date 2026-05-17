<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use RuntimeException;

final class QrCodeService
{
    public function ensureForUser(int $userId): array
    {
        $qrModel = new QrCode();
        $existing = $qrModel->findByUserId($userId);
        if ($existing) {
            $desiredValue = url('/qr/' . $existing['public_token']);
            $existingPath = storage_path($existing['image_path']);
            if (!is_file($existingPath) || (string) $existing['qr_value'] !== $desiredValue) {
                $this->generateImage($desiredValue, $existingPath);
                $qrModel->refreshGeneratedCode((int) $existing['qr_id'], $desiredValue, (string) $existing['image_path']);
                $existing = $qrModel->findByUserId($userId) ?? $existing;
            }
            return $existing;
        }

        $publicToken = bin2hex(random_bytes(16));
        $qrValue = url('/qr/' . $publicToken);
        $filename = 'patient-' . $userId . '.png';
        $relativePath = 'generated/qrcodes/' . $filename;
        $absolutePath = storage_path($relativePath);

        $this->generateImage($qrValue, $absolutePath);

        $qrModel->create($userId, $publicToken, $qrValue, $relativePath);
        return $qrModel->findByUserId($userId) ?? throw new RuntimeException('Unable to load generated QR code.');
    }

    private function generateImage(string $value, string $outputPath): void
    {
        $script = base_path('bin/generate_qr.py');
        $python = config_value('services.python_bin', 'python');

        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create QR output directory.');
        }

        $command = sprintf(
            '%s %s --text %s --output %s 2>&1',
            escapeshellarg($python),
            escapeshellarg($script),
            escapeshellarg($value),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !is_file($outputPath)) {
            throw new RuntimeException('QR generation failed: ' . implode(PHP_EOL, $output));
        }
    }
}

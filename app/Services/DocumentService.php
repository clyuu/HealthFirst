<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class DocumentService
{
    public function storeUploadedFile(array $file, string $folder): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = strtolower($folder) . '-' . time() . '-' . bin2hex(random_bytes(4)) . '-' . $safeName . ($extension ? '.' . $extension : '');
        $directory = storage_path('uploads/' . trim($folder, '/'));

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to move the uploaded file.');
        }

        return [
            'file_path' => 'uploads/' . trim($folder, '/') . '/' . $filename,
            'mime_type' => mime_content_type($target) ?: 'application/octet-stream',
            'file_size' => filesize($target) ?: 0,
            'absolute_path' => $target,
            'original_name' => $file['name'],
        ];
    }
}


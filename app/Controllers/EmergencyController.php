<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\QrCode;
use App\Services\EmergencyWorkflowService;
use Throwable;

final class EmergencyController extends Controller
{
    public function landing(string $token): void
    {
        $qr = (new QrCode())->findByToken($token);
        if ($qr === null) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'QR Not Found']);
            return;
        }

        $this->render('emergency/landing', [
            'title' => 'Emergency QR Access',
            'publicToken' => $token,
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function submit(): void
    {
        $this->validateCsrf();

        try {
            $result = (new EmergencyWorkflowService())->submit($_POST, $_FILES['scene_photo']);
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }
}


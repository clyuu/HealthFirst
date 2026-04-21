<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccidentVerification;
use App\Models\Dispatch;
use App\Models\Incident;
use App\Models\QrCode;
use RuntimeException;

final class EmergencyWorkflowService
{
    public function submit(array $data, array $sceneFile): array
    {
        $qr = (new QrCode())->findByToken($data['public_token']);
        if ($qr === null) {
            throw new RuntimeException('QR code could not be resolved.');
        }

        $upload = (new DocumentService())->storeUploadedFile($sceneFile, 'scene');
        $incidentModel = new Incident();

        $incidentId = $incidentModel->create([
            'user_id' => $qr['user_id'],
            'qr_id' => $qr['qr_id'],
            'incident_latitude' => (float) $data['incident_latitude'],
            'incident_longitude' => (float) $data['incident_longitude'],
            'injured_count' => (int) ($data['injured_count'] ?? 1),
            'status' => 'reported',
            'public_message' => trim((string) ($data['public_message'] ?? '')),
        ]);

        $incidentModel->attachScenePhoto($incidentId, $upload['file_path']);

        $verification = (new AIClient())->verifyAccident($upload['absolute_path']);
        $isRealAccident = (bool) ($verification['is_real_accident'] ?? false);

        (new AccidentVerification())->upsert($incidentId, [
            'result' => $isRealAccident ? 'real_accident' : 'non_accident',
            'confidence_score' => (float) ($verification['confidence'] ?? 0),
            'raw_prediction' => (float) ($verification['raw_prediction'] ?? 0),
            'model_version' => $verification['model_version'] ?? 'accident-v1',
        ]);

        if (!$isRealAccident) {
            $incidentModel->updateStatus($incidentId, 'rejected', null, 'Accident verification model rejected the public submission.');
            return [
                'status' => 'rejected',
                'incident_id' => $incidentId,
                'message' => 'The uploaded scene photo was not verified as a real accident. No hospital was alerted.',
                'verification' => $verification,
            ];
        }

        $rankedHospitals = (new MapsService())->rankHospitals(
            (float) $data['incident_latitude'],
            (float) $data['incident_longitude']
        );

        if ($rankedHospitals === []) {
            $incidentModel->updateStatus($incidentId, 'verified_unassigned', null, 'No hospital with an available ambulance was found.');
            return [
                'status' => 'verified_unassigned',
                'incident_id' => $incidentId,
                'message' => 'Accident verified, but no registered hospital ambulance is currently available.',
                'verification' => $verification,
            ];
        }

        $selectedHospital = $rankedHospitals[0];
        $incidentModel->setHospital($incidentId, (int) $selectedHospital['hospital_id']);
        $incidentModel->updateStatus($incidentId, 'verified_unassigned', null, 'Accident verified and queued for hospital assignment.');
        (new Dispatch())->createInitial(
            $incidentId,
            (int) $selectedHospital['hospital_id'],
            (int) ($selectedHospital['eta_seconds'] ?? 0)
        );

        return [
            'status' => 'verified_unassigned',
            'incident_id' => $incidentId,
            'message' => 'Accident verified and the nearest hospital has been alerted.',
            'hospital' => $selectedHospital,
            'verification' => $verification,
        ];
    }
}


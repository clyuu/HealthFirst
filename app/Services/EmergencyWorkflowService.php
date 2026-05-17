<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccidentVerification;
use App\Models\Dispatch;
use App\Models\Incident;
use App\Models\QrCode;
use App\Models\User;
use RuntimeException;

final class EmergencyWorkflowService
{
    public function submit(array $data, array $sceneFile): array
    {
        $latitude = isset($data['incident_latitude']) ? (float) $data['incident_latitude'] : 0.0;
        $longitude = isset($data['incident_longitude']) ? (float) $data['incident_longitude'] : 0.0;
        if ($latitude === 0.0 && $longitude === 0.0) {
            throw new RuntimeException('Location was not captured. Allow location access before submitting the emergency report.');
        }

        $publicToken = trim((string) ($data['public_token'] ?? ''));
        $patientNic = trim((string) ($data['patient_nic'] ?? ''));
        $patientPhone = trim((string) ($data['patient_phone'] ?? ''));
        $patientName = trim((string) ($data['patient_name'] ?? ''));
        $vehicleNumber = trim((string) ($data['vehicle_number'] ?? ''));

        $qr = null;
        $patient = null;
        if ($publicToken !== '') {
            $qr = (new QrCode())->findByToken($publicToken);
            if ($qr === null) {
                throw new RuntimeException('QR code could not be resolved.');
            }
            $patient = [
                'user_id' => $qr['user_id'],
                'full_name' => $qr['full_name'] ?? '',
                'nic_number' => $qr['nic_number'] ?? '',
                'phone' => $qr['phone'] ?? '',
            ];
        } else {
            $userModel = new User();
            if ($patientNic !== '') {
                $patient = $userModel->findByNic($patientNic);
            } elseif ($patientPhone !== '') {
                $patient = $userModel->findByPhone($patientPhone);
            }

            if ($patient !== null) {
                $qr = (new QrCode())->findByUserId((int) $patient['user_id']);
            }
        }

        if ($qr === null && $patient === null && $patientNic === '' && $patientPhone === '' && $patientName === '' && $vehicleNumber === '') {
            throw new RuntimeException('Scan the QR code or enter at least one patient detail such as NIC, phone, name, or vehicle number.');
        }

        $incidentModel = new Incident();
        $reportedPersonName = $patient['full_name'] ?? ($patientName !== '' ? $patientName : null);
        $reportedPersonNic = $patient['nic_number'] ?? ($patientNic !== '' ? $patientNic : null);
        $reportedPersonPhone = $patient['phone'] ?? ($patientPhone !== '' ? $patientPhone : null);

        $existingIncident = $incidentModel->findRecentActiveDuplicate([
            'user_id' => $patient['user_id'] ?? null,
            'qr_id' => $qr['qr_id'] ?? null,
            'incident_latitude' => $latitude,
            'incident_longitude' => $longitude,
            'reported_person_name' => $reportedPersonName,
            'reported_person_nic' => $reportedPersonNic,
            'reported_person_phone' => $reportedPersonPhone,
            'reported_vehicle_number' => $vehicleNumber !== '' ? $vehicleNumber : null,
        ]);

        $upload = (new DocumentService())->storeUploadedFile($sceneFile, 'scene');

        if ($existingIncident !== null) {
            $incidentId = (int) $existingIncident['incident_id'];
            $incidentModel->attachScenePhoto($incidentId, $upload['file_path']);
            $incidentModel->addStatusHistory(
                $incidentId,
                (string) ($existingIncident['status'] ?? 'reported'),
                null,
                'Additional public emergency report matched this active incident.'
            );

            return [
                'status' => 'submitted',
                'incident_id' => $incidentId,
                'message' => 'Emergency report received. This accident was already in the live queue, so the existing case has been updated instead of creating a duplicate incident.',
            ];
        }

        $messageParts = [];
        $publicMessage = trim((string) ($data['public_message'] ?? ''));
        if ($publicMessage !== '') {
            $messageParts[] = $publicMessage;
        }
        if ($vehicleNumber !== '') {
            $messageParts[] = 'Vehicle: ' . $vehicleNumber;
        }
        if ($patientName !== '' && $patient === null) {
            $messageParts[] = 'Reported name: ' . $patientName;
        }

        $incidentId = $incidentModel->create([
            'user_id' => $patient['user_id'] ?? null,
            'qr_id' => $qr['qr_id'] ?? null,
            'incident_latitude' => $latitude,
            'incident_longitude' => $longitude,
            'injured_count' => (int) ($data['injured_count'] ?? 1),
            'status' => 'reported',
            'public_message' => implode(' | ', $messageParts),
            'reported_person_name' => $reportedPersonName,
            'reported_person_nic' => $reportedPersonNic,
            'reported_person_phone' => $reportedPersonPhone,
            'reported_vehicle_number' => $vehicleNumber !== '' ? $vehicleNumber : null,
            'source_note' => $publicToken !== '' ? 'Incident created from public QR scan.' : 'Incident created from manual public emergency report.',
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
                'status' => 'submitted',
                'incident_id' => $incidentId,
                'message' => 'Emergency report received. Our emergency system is reviewing the submission and will notify the response team if action is required.',
            ];
        }

        $rankedHospitals = (new MapsService())->rankHospitals(
            $latitude,
            $longitude
        );

        if ($rankedHospitals === []) {
            $incidentModel->updateStatus($incidentId, 'verified_unassigned', null, 'No hospital with an available ambulance was found.');
            return [
                'status' => 'submitted',
                'incident_id' => $incidentId,
                'message' => 'Emergency report received. The coordination team has been notified and is trying to route the case to the nearest available hospital.',
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
            'status' => 'submitted',
            'incident_id' => $incidentId,
            'message' => 'Emergency report received. The nearest response team has been notified and the case is now being handled.',
        ];
    }
}

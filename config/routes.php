<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', 'HomeController@index');

    $router->match(['GET', 'POST'], '/login', 'AuthController@login');
    $router->match(['GET', 'POST'], '/register', 'AuthController@register');
    $router->post('/logout', 'AuthController@logout');
    $router->get('/api/location/search', 'LocationController@search');
    $router->get('/documents/{documentId:\d+}/download', 'DocumentController@download', ['roles' => ['patient', 'doctor', 'hospital_staff', 'hospital_admin', 'paramedic', 'system_admin']]);
    $router->get('/reports/injury/{incidentId:\d+}', 'DocumentController@injuryReport', ['roles' => ['patient', 'doctor', 'hospital_staff', 'hospital_admin', 'paramedic', 'system_admin']]);

    $router->get('/patient/dashboard', 'PatientController@dashboard', ['roles' => ['patient']]);
    $router->post('/patient/profile', 'PatientController@saveProfile', ['roles' => ['patient']]);
    $router->post('/patient/contacts', 'PatientController@saveContact', ['roles' => ['patient']]);
    $router->post('/patient/documents', 'PatientController@uploadDocument', ['roles' => ['patient']]);
    $router->get('/patient/qr/download', 'PatientController@downloadQr', ['roles' => ['patient']]);
    $router->get('/patient/qr/print', 'PatientController@printQr', ['roles' => ['patient']]);
    $router->get('/api/patient/nearby-hospitals', 'PatientController@nearbyHospitals', ['roles' => ['patient']]);

    $router->get('/qr/{token}', 'EmergencyController@landing');
    $router->get('/emergency/report', 'EmergencyController@manualLanding');
    $router->post('/api/emergency/report', 'EmergencyController@submit');

    $router->get('/hospital/dashboard', 'HospitalController@dashboard', ['roles' => ['hospital_staff', 'hospital_admin']]);
    $router->get('/api/hospital/incidents', 'HospitalController@incidents', ['roles' => ['hospital_staff', 'hospital_admin']]);
    $router->post('/hospital/incidents/{incidentId:\d+}/assign', 'HospitalController@assignAmbulance', ['roles' => ['hospital_staff', 'hospital_admin']]);
    $router->post('/hospital/incidents/{incidentId:\d+}/documents', 'HospitalController@uploadDischargeDocument', ['roles' => ['hospital_staff', 'hospital_admin']]);

    $router->get('/paramedic/dashboard', 'AmbulanceController@paramedicDashboard', ['roles' => ['paramedic']]);
    $router->get('/ambulance/dashboard', 'AmbulanceController@ambulanceDashboard', ['roles' => ['paramedic']]);
    $router->get('/api/ambulance/incidents', 'AmbulanceController@incidents', ['roles' => ['paramedic']]);
    $router->get('/api/ambulance/incidents/{incidentId:\d+}/navigation', 'AmbulanceController@navigation', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/lookup-patient', 'AmbulanceController@lookupPatient', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/location', 'AmbulanceController@updateLocation', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/pickup', 'AmbulanceController@pickupPatient', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/arrive-hospital', 'AmbulanceController@arriveAtHospital', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/vitals', 'AmbulanceController@saveVitals', ['roles' => ['paramedic']]);
    $router->post('/ambulance/incidents/{incidentId:\d+}/injury-session', 'AmbulanceController@startInjurySession', ['roles' => ['paramedic']]);
    $router->post('/ambulance/injury-sessions/{sessionId:\d+}/images', 'AmbulanceController@analyzeImage', ['roles' => ['paramedic']]);
    $router->post('/ambulance/injury-sessions/{sessionId:\d+}/finalize', 'AmbulanceController@finalizeSession', ['roles' => ['paramedic']]);

    $router->get('/doctor/dashboard', 'DoctorController@dashboard', ['roles' => ['doctor']]);
    $router->post('/doctor/incidents/{incidentId:\d+}/admit', 'DoctorController@admit', ['roles' => ['doctor']]);
    $router->get('/doctor/patients', 'DoctorController@myPatients', ['roles' => ['doctor']]);
    $router->post('/doctor/cases/{caseId:\d+}/discharge', 'DoctorController@discharge', ['roles' => ['doctor']]);

    $router->get('/admin/hospital', 'HospitalAdminController@dashboard', ['roles' => ['hospital_admin']]);
    $router->post('/admin/hospital/staff', 'HospitalAdminController@createStaff', ['roles' => ['hospital_admin']]);
    $router->post('/admin/hospital/ambulances', 'HospitalAdminController@createAmbulance', ['roles' => ['hospital_admin']]);

    $router->get('/admin/system', 'SystemAdminController@dashboard', ['roles' => ['system_admin']]);
    $router->post('/admin/system/hospitals', 'SystemAdminController@createHospital', ['roles' => ['system_admin']]);
    $router->post('/admin/system/hospital-admins', 'SystemAdminController@createHospitalAdmin', ['roles' => ['system_admin']]);
};

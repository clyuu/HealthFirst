USE healthfirst;

SET @pwd := '$2y$10$Ryk.0gJyiPNRJbSYjr3PC.WETGqce4dJs1kchdwFLfG01gCKUo7g6';

INSERT INTO roles (slug, role_name, description) VALUES
('system_admin', 'System Admin', 'Platform-wide administrator'),
('hospital_admin', 'Hospital Admin', 'Hospital operations administrator'),
('hospital_staff', 'Hospital Dashboard Staff', 'Front desk and emergency board staff'),
('doctor', 'Doctor', 'Assigned medical doctor'),
('paramedic', 'Paramedic', 'Ambulance and pre-hospital staff'),
('patient', 'Patient', 'Public patient account');

INSERT INTO hospitals (hospital_name, address, latitude, longitude, contact_number) VALUES
('National Hospital of Sri Lanka', 'Regent Street, Colombo 10', 6.91865000, 79.86545000, '0112691111'),
('Nawaloka Hospital', 'Deshamanya H. K. Dharmadasa Mawatha, Colombo 02', 6.91737000, 79.85486000, '0115577111'),
('Asiri Surgical Hospital', 'No. 21, Kirimandala Mawatha, Colombo 05', 6.89242000, 79.86909000, '0114523300');

INSERT INTO users (role_id, full_name, nic_number, email, phone, address, password_hash, date_of_birth, gender, profile_latitude, profile_longitude) VALUES
((SELECT role_id FROM roles WHERE slug = 'system_admin'), 'Super Admin', '850000000V', 'admin@healthfirst.lk', '0770000001', 'Colombo', @pwd, '1985-01-10', 'Male', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'hospital_admin'), 'Hospital Admin One', '860000001V', 'hadmin1@healthfirst.lk', '0771000001', 'National Hospital', @pwd, '1986-03-15', 'Female', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'hospital_admin'), 'Hospital Admin Two', '860000002V', 'hadmin2@healthfirst.lk', '0771000002', 'Nawaloka Hospital', @pwd, '1987-07-12', 'Female', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'hospital_staff'), 'Hospital Desk One', '860000009V', 'hdesk1@healthfirst.lk', '0771000009', 'National Hospital', @pwd, '1990-02-10', 'Female', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'hospital_staff'), 'Hospital Desk Two', '860000010V', 'hdesk2@healthfirst.lk', '0771000010', 'Nawaloka Hospital', @pwd, '1991-11-03', 'Male', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'doctor'), 'Dr. Amila Perera', '820000003V', 'doctor1@healthfirst.lk', '0772000001', 'Colombo', @pwd, '1982-04-04', 'Male', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'doctor'), 'Dr. Nethmi Silva', '830000004V', 'doctor2@healthfirst.lk', '0772000002', 'Colombo', @pwd, '1983-09-20', 'Female', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'paramedic'), 'Kamal Perera', '920000005V', 'paramedic1@healthfirst.lk', '0773000001', 'Colombo', @pwd, '1992-05-11', 'Male', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'paramedic'), 'Nuwan Fernando', '930000006V', 'paramedic2@healthfirst.lk', '0773000002', 'Colombo', @pwd, '1993-12-09', 'Male', NULL, NULL),
((SELECT role_id FROM roles WHERE slug = 'patient'), 'Kasun Jayasinghe', '960000007V', 'patient1@healthfirst.lk', '0774000001', 'Maharagama', @pwd, '1996-02-22', 'Male', 6.84930000, 79.92510000),
((SELECT role_id FROM roles WHERE slug = 'patient'), 'Sanduni Wijesinghe', '970000008V', 'patient2@healthfirst.lk', '0774000002', 'Rajagiriya', @pwd, '1997-08-18', 'Female', 6.90560000, 79.89780000);

INSERT INTO hospital_staff (user_id, hospital_id, designation) VALUES
((SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 1, 'Primary Hospital Administrator'),
((SELECT user_id FROM users WHERE email = 'hadmin2@healthfirst.lk'), 2, 'Primary Hospital Administrator'),
((SELECT user_id FROM users WHERE email = 'hdesk1@healthfirst.lk'), 1, 'Emergency Front Desk'),
((SELECT user_id FROM users WHERE email = 'hdesk2@healthfirst.lk'), 2, 'Emergency Front Desk'),
((SELECT user_id FROM users WHERE email = 'doctor1@healthfirst.lk'), 1, 'Emergency Physician'),
((SELECT user_id FROM users WHERE email = 'doctor2@healthfirst.lk'), 2, 'Trauma Surgeon'),
((SELECT user_id FROM users WHERE email = 'paramedic1@healthfirst.lk'), 1, 'Lead Paramedic'),
((SELECT user_id FROM users WHERE email = 'paramedic2@healthfirst.lk'), 1, 'Field Paramedic');

INSERT INTO ambulances (hospital_id, ambulance_number, status, capacity_stretchers, current_latitude, current_longitude) VALUES
(1, 'WP-AMB-001', 'assigned', 1, 6.91865000, 79.86545000),
(1, 'WP-AMB-002', 'en_route_hospital', 1, 6.90412000, 79.87321000),
(1, 'WP-AMB-003', 'available', 1, 6.91865000, 79.86545000),
(2, 'WP-AMB-004', 'available', 1, 6.91737000, 79.85486000);

INSERT INTO ambulance_staff_assignments (ambulance_id, user_id, is_active) VALUES
((SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), (SELECT user_id FROM users WHERE email = 'paramedic1@healthfirst.lk'), 1),
((SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-002'), (SELECT user_id FROM users WHERE email = 'paramedic2@healthfirst.lk'), 1);

INSERT INTO qr_codes (user_id, public_token, qr_value, image_path, status) VALUES
((SELECT user_id FROM users WHERE email = 'patient1@healthfirst.lk'), 'seedtoken-patient1', 'http://localhost/HealthFirst/public/qr/seedtoken-patient1', 'generated/qrcodes/patient-8.png', 'active'),
((SELECT user_id FROM users WHERE email = 'patient2@healthfirst.lk'), 'seedtoken-patient2', 'http://localhost/HealthFirst/public/qr/seedtoken-patient2', 'generated/qrcodes/patient-9.png', 'active');

INSERT INTO medical_profiles (user_id, blood_group, allergies, chronic_conditions, notes, emergency_phone) VALUES
((SELECT user_id FROM users WHERE email = 'patient1@healthfirst.lk'), 'O+', 'Penicillin', 'Asthma', 'Carries rescue inhaler. Previous minor fracture.', '0711111111'),
((SELECT user_id FROM users WHERE email = 'patient2@healthfirst.lk'), 'A+', 'Shellfish', 'Diabetes', 'Requires insulin support if unconscious.', '0722222222');

INSERT INTO emergency_contacts (user_id, contact_name, relationship, phone_number, is_primary) VALUES
((SELECT user_id FROM users WHERE email = 'patient1@healthfirst.lk'), 'Mother', 'Mother', '0761234567', 1),
((SELECT user_id FROM users WHERE email = 'patient2@healthfirst.lk'), 'Brother', 'Brother', '0779876543', 1);

INSERT INTO accident_incidents (user_id, qr_id, incident_latitude, incident_longitude, injured_count, status, selected_hospital_id, selected_ambulance_id, public_message, reported_at) VALUES
((SELECT user_id FROM users WHERE email = 'patient1@healthfirst.lk'), (SELECT qr_id FROM qr_codes WHERE public_token = 'seedtoken-patient1'), 6.90751000, 79.85288000, 2, 'verified_unassigned', 1, NULL, 'Vehicle collision near main junction.', NOW() - INTERVAL 18 MINUTE),
((SELECT user_id FROM users WHERE email = 'patient2@healthfirst.lk'), (SELECT qr_id FROM qr_codes WHERE public_token = 'seedtoken-patient2'), 6.91340000, 79.86090000, 1, 'ambulance_assigned', 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), 'Two cars involved, one patient needs help.', NOW() - INTERVAL 12 MINUTE),
((SELECT user_id FROM users WHERE email = 'patient1@healthfirst.lk'), (SELECT qr_id FROM qr_codes WHERE public_token = 'seedtoken-patient1'), 6.89945000, 79.87120000, 1, 'patient_picked_up', 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-002'), 'Motorbike skid accident.', NOW() - INTERVAL 8 MINUTE),
((SELECT user_id FROM users WHERE email = 'patient2@healthfirst.lk'), (SELECT qr_id FROM qr_codes WHERE public_token = 'seedtoken-patient2'), 6.91630000, 79.86470000, 1, 'admitted', 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), 'Historic admitted case.', NOW() - INTERVAL 1 DAY);

INSERT INTO scene_photos (incident_id, image_path) VALUES
(1, 'uploads/scene/demo-scene-1.jpg'),
(2, 'uploads/scene/demo-scene-2.jpg'),
(3, 'uploads/scene/demo-scene-3.jpg'),
(4, 'uploads/scene/demo-scene-4.jpg');

INSERT INTO accident_verifications (incident_id, result, confidence_score, raw_prediction, model_version) VALUES
(1, 'real_accident', 97.40, 0.10250, 'accident-v1'),
(2, 'real_accident', 95.10, 0.13220, 'accident-v1'),
(3, 'real_accident', 91.85, 0.20310, 'accident-v1'),
(4, 'real_accident', 94.35, 0.14400, 'accident-v1');

INSERT INTO dispatch (incident_id, hospital_id, ambulance_id, assigned_by_user_id, dispatch_status, scene_eta_seconds, hospital_eta_seconds, assigned_at, picked_up_at, arrived_hospital_at) VALUES
(1, 1, NULL, NULL, 'unassigned', 720, NULL, NULL, NULL, NULL),
(2, 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), (SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 'ambulance_assigned', 840, NULL, NOW() - INTERVAL 11 MINUTE, NULL, NULL),
(3, 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-002'), (SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 'patient_picked_up', 300, 600, NOW() - INTERVAL 7 MINUTE, NOW() - INTERVAL 3 MINUTE, NULL),
(4, 1, (SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), (SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 'admitted', 420, 540, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 23 HOUR, NOW() - INTERVAL 22 HOUR);

INSERT INTO incident_status_history (incident_id, status, changed_by_user_id, note) VALUES
(1, 'reported', NULL, 'Demo public report created.'),
(1, 'verified_unassigned', NULL, 'Accident verified, waiting for ambulance assignment.'),
(2, 'reported', NULL, 'Demo public report created.'),
(2, 'verified_unassigned', NULL, 'Accident verified.'),
(2, 'ambulance_assigned', (SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 'Hospital admin assigned an ambulance.'),
(3, 'reported', NULL, 'Demo public report created.'),
(3, 'ambulance_assigned', (SELECT user_id FROM users WHERE email = 'hadmin1@healthfirst.lk'), 'Ambulance assigned.'),
(3, 'patient_picked_up', (SELECT user_id FROM users WHERE email = 'paramedic2@healthfirst.lk'), 'Patient loaded into ambulance.'),
(4, 'admitted', (SELECT user_id FROM users WHERE email = 'doctor1@healthfirst.lk'), 'Doctor admitted patient.');

INSERT INTO ambulance_locations (ambulance_id, incident_id, latitude, longitude, speed_kmh, source) VALUES
((SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-001'), 2, 6.91500000, 79.86200000, 42.0, 'gps'),
((SELECT ambulance_id FROM ambulances WHERE ambulance_number = 'WP-AMB-002'), 3, 6.90380000, 79.87380000, 37.0, 'gps');

INSERT INTO patient_vitals (incident_id, recorded_by_user_id, heart_rate, systolic_bp, diastolic_bp, spo2, temperature_c, notes) VALUES
(3, (SELECT user_id FROM users WHERE email = 'paramedic2@healthfirst.lk'), 110, 100, 64, 92, 37.5, 'Patient conscious, bleeding controlled.');

INSERT INTO injury_sessions (incident_id, started_by_user_id, session_token, special_note, status, overall_severity, summary_json, report_file_path, finalized_at) VALUES
(3, (SELECT user_id FROM users WHERE email = 'paramedic2@healthfirst.lk'), 'inj-session-seed-3', 'Possible right arm fracture, patient alert.', 'finalized', 'Moderate', '{"Cuts & Bleeding":1,"Burns":0,"Normal (No Visible Injury)":0}', NULL, NOW() - INTERVAL 2 MINUTE),
(4, (SELECT user_id FROM users WHERE email = 'paramedic1@healthfirst.lk'), 'inj-session-seed-4', 'Older admitted case.', 'finalized', 'Mild', '{"Cuts & Bleeding":1,"Burns":0,"Normal (No Visible Injury)":1}', NULL, NOW() - INTERVAL 23 HOUR);

INSERT INTO injury_image_predictions (session_id, incident_id, image_path, predicted_label, confidence_score, burns_probability, cuts_probability, normal_probability) VALUES
((SELECT session_id FROM injury_sessions WHERE session_token = 'inj-session-seed-3'), 3, 'uploads/injuries/demo-injury-3.jpg', 'Cuts & Bleeding', 86.50, 5.10, 86.50, 8.40),
((SELECT session_id FROM injury_sessions WHERE session_token = 'inj-session-seed-4'), 4, 'uploads/injuries/demo-injury-4.jpg', 'Cuts & Bleeding', 62.40, 11.10, 62.40, 26.50);

INSERT INTO doctor_case_assignments (incident_id, doctor_user_id, hospital_id, status, assigned_at, admitted_at) VALUES
(4, (SELECT user_id FROM users WHERE email = 'doctor1@healthfirst.lk'), 1, 'admitted', NOW() - INTERVAL 23 HOUR, NOW() - INTERVAL 23 HOUR);

DROP DATABASE IF EXISTS healthfirst;
CREATE DATABASE healthfirst CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthfirst;

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    role_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    nic_number VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    profile_latitude DECIMAL(10, 8) NULL,
    profile_longitude DECIMAL(11, 8) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (role_id)
);

CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hospital_staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    hospital_id INT NOT NULL,
    designation VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_staff_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_hospital_staff_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE
);

CREATE TABLE ambulances (
    ambulance_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    ambulance_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('available', 'assigned', 'en_route_scene', 'on_scene', 'en_route_hospital', 'maintenance', 'inactive') NOT NULL DEFAULT 'available',
    capacity_stretchers INT NOT NULL DEFAULT 1,
    current_latitude DECIMAL(10, 8) NULL,
    current_longitude DECIMAL(11, 8) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ambulances_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE
);

CREATE TABLE ambulance_staff_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    ambulance_id INT NOT NULL,
    user_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ambulance_staff_assignment_ambulance FOREIGN KEY (ambulance_id) REFERENCES ambulances (ambulance_id) ON DELETE CASCADE,
    CONSTRAINT fk_ambulance_staff_assignment_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE qr_codes (
    qr_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    public_token VARCHAR(64) NOT NULL UNIQUE,
    qr_value VARCHAR(255) NOT NULL UNIQUE,
    image_path VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qr_codes_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE medical_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    blood_group VARCHAR(10) NOT NULL DEFAULT 'Unknown',
    allergies TEXT NULL,
    chronic_conditions TEXT NULL,
    notes TEXT NULL,
    emergency_phone VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medical_profiles_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE emergency_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_name VARCHAR(150) NOT NULL,
    relationship VARCHAR(100) NULL,
    phone_number VARCHAR(30) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_emergency_contacts_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE medical_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    incident_id INT NULL,
    hospital_id INT NULL,
    uploaded_by_user_id INT NULL,
    document_category VARCHAR(80) NOT NULL,
    source_type ENUM('patient_upload', 'doctor_upload', 'hospital_upload', 'ai_generated') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medical_documents_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_medical_documents_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE SET NULL,
    CONSTRAINT fk_medical_documents_uploaded_by FOREIGN KEY (uploaded_by_user_id) REFERENCES users (user_id) ON DELETE SET NULL
);

CREATE TABLE accident_incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qr_id INT NOT NULL,
    incident_latitude DECIMAL(10, 8) NOT NULL,
    incident_longitude DECIMAL(11, 8) NOT NULL,
    injured_count INT NOT NULL DEFAULT 1,
    status ENUM('reported', 'rejected', 'verified_unassigned', 'ambulance_assigned', 'en_route_scene', 'patient_picked_up', 'en_route_hospital', 'admitted', 'discharged') NOT NULL DEFAULT 'reported',
    selected_hospital_id INT NULL,
    selected_ambulance_id INT NULL,
    public_message TEXT NULL,
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_accident_incidents_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_accident_incidents_qr FOREIGN KEY (qr_id) REFERENCES qr_codes (qr_id) ON DELETE CASCADE,
    CONSTRAINT fk_accident_incidents_hospital FOREIGN KEY (selected_hospital_id) REFERENCES hospitals (hospital_id) ON DELETE SET NULL,
    CONSTRAINT fk_accident_incidents_ambulance FOREIGN KEY (selected_ambulance_id) REFERENCES ambulances (ambulance_id) ON DELETE SET NULL
);

ALTER TABLE medical_documents
ADD CONSTRAINT fk_medical_documents_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE SET NULL;

CREATE TABLE scene_photos (
    scene_photo_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scene_photos_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE
);

CREATE TABLE accident_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL UNIQUE,
    result ENUM('real_accident', 'non_accident') NOT NULL,
    confidence_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    raw_prediction DECIMAL(7, 5) NULL,
    model_version VARCHAR(50) NULL,
    verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_accident_verifications_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE
);

CREATE TABLE dispatch (
    dispatch_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL UNIQUE,
    hospital_id INT NOT NULL,
    ambulance_id INT NULL,
    assigned_by_user_id INT NULL,
    dispatch_status ENUM('unassigned', 'ambulance_assigned', 'en_route_scene', 'on_scene', 'patient_picked_up', 'en_route_hospital', 'admitted', 'discharged') NOT NULL DEFAULT 'unassigned',
    scene_eta_seconds INT NULL,
    hospital_eta_seconds INT NULL,
    assigned_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    left_scene_at TIMESTAMP NULL,
    arrived_hospital_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dispatch_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE,
    CONSTRAINT fk_dispatch_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE,
    CONSTRAINT fk_dispatch_ambulance FOREIGN KEY (ambulance_id) REFERENCES ambulances (ambulance_id) ON DELETE SET NULL,
    CONSTRAINT fk_dispatch_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users (user_id) ON DELETE SET NULL
);

CREATE TABLE incident_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by_user_id INT NULL,
    note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_incident_history_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_history_user FOREIGN KEY (changed_by_user_id) REFERENCES users (user_id) ON DELETE SET NULL
);

CREATE TABLE ambulance_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    ambulance_id INT NOT NULL,
    incident_id INT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    speed_kmh DECIMAL(6, 2) NULL,
    source ENUM('gps', 'manual') NOT NULL DEFAULT 'gps',
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ambulance_locations_ambulance FOREIGN KEY (ambulance_id) REFERENCES ambulances (ambulance_id) ON DELETE CASCADE,
    CONSTRAINT fk_ambulance_locations_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE SET NULL
);

CREATE TABLE patient_vitals (
    vital_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    recorded_by_user_id INT NOT NULL,
    heart_rate INT NULL,
    systolic_bp INT NULL,
    diastolic_bp INT NULL,
    spo2 INT NULL,
    temperature_c DECIMAL(4, 1) NULL,
    notes TEXT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_vitals_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE,
    CONSTRAINT fk_patient_vitals_user FOREIGN KEY (recorded_by_user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE injury_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    started_by_user_id INT NOT NULL,
    session_token VARCHAR(80) NOT NULL UNIQUE,
    special_note TEXT NULL,
    status ENUM('active', 'finalized') NOT NULL DEFAULT 'active',
    overall_severity VARCHAR(50) NULL,
    summary_json LONGTEXT NULL,
    report_file_path VARCHAR(255) NULL,
    finalized_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_injury_sessions_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE,
    CONSTRAINT fk_injury_sessions_user FOREIGN KEY (started_by_user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE injury_image_predictions (
    prediction_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    incident_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    predicted_label VARCHAR(80) NOT NULL,
    confidence_score DECIMAL(5, 2) NOT NULL,
    burns_probability DECIMAL(5, 2) NOT NULL,
    cuts_probability DECIMAL(5, 2) NOT NULL,
    normal_probability DECIMAL(5, 2) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_injury_predictions_session FOREIGN KEY (session_id) REFERENCES injury_sessions (session_id) ON DELETE CASCADE,
    CONSTRAINT fk_injury_predictions_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE
);

CREATE TABLE doctor_case_assignments (
    case_assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    doctor_user_id INT NOT NULL,
    hospital_id INT NOT NULL,
    status ENUM('admitted', 'discharged') NOT NULL DEFAULT 'admitted',
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    admitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discharged_at TIMESTAMP NULL,
    CONSTRAINT fk_doctor_case_incident FOREIGN KEY (incident_id) REFERENCES accident_incidents (incident_id) ON DELETE CASCADE,
    CONSTRAINT fk_doctor_case_doctor FOREIGN KEY (doctor_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_doctor_case_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (hospital_id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_user_created (user_id, created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
);


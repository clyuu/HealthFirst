<?php
$age = !empty($user['date_of_birth']) ? date_diff(date_create($user['date_of_birth']), new DateTime())->y : null;
$todayLabel = date('Y-m-d l');
$conditionItems = array_values(array_filter(array_map(
    static fn (string $item): string => trim($item),
    preg_split('/[\r\n,]+/', (string) ($profile['chronic_conditions'] ?? '')) ?: []
)));
$conditionCount = count($conditionItems);
$profileStatus = !empty($profile) ? 'Complete' : 'Pending';
$profileStatusClass = !empty($profile) ? 'is-complete' : 'is-pending';
$primaryContact = null;
foreach ($contacts as $contact) {
    if (!empty($contact['is_primary'])) {
        $primaryContact = $contact;
        break;
    }
}
if ($primaryContact === null && !empty($contacts)) {
    $primaryContact = $contacts[0];
}
$contactLabel = $primaryContact !== null
    ? trim(($primaryContact['contact_name'] ?? 'Contact') . ' - ' . ($primaryContact['phone_number'] ?? ''))
    : 'No emergency numbers added yet';
$documentPreview = array_slice($documents, 0, 3);
$bloodGroup = (string) ($profile['blood_group'] ?? 'Unknown');
$allergies = trim((string) ($profile['allergies'] ?? ''));
$chronicConditions = trim((string) ($profile['chronic_conditions'] ?? ''));
$notes = trim((string) ($profile['notes'] ?? ''));
$bloodGroups = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$phonePattern = '0[0-9]{9}';
$icon = static function (string $name): string {
    $icons = [
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c1.8-4 5-6 8-6s6.2 2 8 6"></path></svg>',
        'condition' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="3"></rect><path d="M12 8v8M8 12h8"></path></svg>',
        'document' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h6l5 5v13H8z"></path><path d="M14 3v5h5"></path><path d="M12 11v6M9 14h6"></path></svg>',
        'hospital' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14"></path><path d="M14 21V3h6v18"></path><path d="M17 7v6M14 10h6M7 11v6M4 14h6"></path></svg>',
        'qr' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"></path><path d="M15 15h2v2h-2zM18 15h2v5h-5v-2h3z"></path></svg>',
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20l4.5-1 9-9-3.5-3.5-9 9L4 20z"></path><path d="M13.5 6.5l3.5 3.5"></path></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V6"></path><path d="M8.5 9.5L12 6l3.5 3.5"></path><path d="M5 18h14"></path><rect x="4" y="18" width="16" height="2" rx="1"></rect></svg>',
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h4l1 4-2 2c1.4 2.5 3.5 4.6 6 6l2-2 4 1v4c0 1.1-.9 2-2 2A17 17 0 0 1 3 6c0-1.1.9-2 2-2h2z"></path></svg>',
        'download' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v10"></path><path d="M8.5 10.5L12 14l3.5-3.5"></path><path d="M5 18h14"></path></svg>',
        'view' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    ];

    return $icons[$name] ?? '';
};
$documentSourceLabel = static function (array $document): string {
    if (($document['source_type'] ?? '') === 'hospital_upload') {
        return 'Hospital document' . (!empty($document['hospital_name']) ? ' - ' . $document['hospital_name'] : '');
    }

    return 'Patient upload';
};
?>
<section class="patient-dashboard">
    <div class="patient-hero-card">
        <div>
            <h1>Welcome, <?= e($user['full_name']) ?>!</h1>
            <p class="patient-date-line">
                <span class="patient-inline-icon"><?= $icon('calendar') ?></span>
                <span>Today: <?= e($todayLabel) ?><?= $age !== null ? ' | Age: ' . e((string) $age) : '' ?></span>
            </p>
        </div>
        <button class="button patient-hero-qr" type="button" data-modal-open="patientQrModal">
            <span class="button-icon"><?= $icon('qr') ?></span>
            <span>QR Code</span>
        </button>
    </div>

    <section class="patient-stats-grid">
        <article class="patient-stat-card">
            <span class="patient-stat-icon blue"><?= $icon('profile') ?></span>
            <h3>Profile Status</h3>
            <p class="patient-stat-value"><span class="status-badge <?= e($profileStatusClass) ?>"><?= e($profileStatus) ?></span></p>
        </article>
        <article class="patient-stat-card">
            <span class="patient-stat-icon green"><?= $icon('condition') ?></span>
            <h3>Conditions</h3>
            <p class="patient-stat-value"><?= e((string) $conditionCount) ?></p>
        </article>
        <article class="patient-stat-card">
            <span class="patient-stat-icon cyan"><?= $icon('document') ?></span>
            <h3>Documents</h3>
            <p class="patient-stat-value"><?= e((string) count($documents)) ?></p>
        </article>
        <article class="patient-stat-card">
            <span class="patient-stat-icon teal"><?= $icon('hospital') ?></span>
            <h3>Nearby Hospitals</h3>
            <p class="patient-stat-value" id="nearbyHospitalCount">0</p>
        </article>
    </section>

    <section class="patient-primary-grid">
        <article class="panel patient-profile-card" id="profile-summary">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon accent"><?= $icon('profile') ?></span>Medical Profile</h2>
                <button class="button tiny primary" type="button" data-modal-open="editProfileModal">
                    <span class="button-icon"><?= $icon('edit') ?></span>
                    <span>Edit</span>
                </button>
            </div>

            <div class="patient-profile-grid">
                <div class="patient-info-tile">
                    <strong>Blood Group:</strong>
                    <span class="blood-pill"><?= e($bloodGroup) ?></span>
                </div>
                <div class="patient-info-tile">
                    <strong>Allergies:</strong>
                    <span><?= e($allergies !== '' ? $allergies : 'Not provided') ?></span>
                </div>
                <div class="patient-info-tile tall">
                    <strong>Chronic Conditions:</strong>
                    <span><?= e($chronicConditions !== '' ? $chronicConditions : 'None recorded') ?></span>
                </div>
                <div class="patient-info-tile tall">
                    <strong>Notes:</strong>
                    <span><?= e($notes !== '' ? $notes : 'No additional notes yet') ?></span>
                </div>
            </div>

            <div class="patient-documents-summary">
                <div class="patient-subhead">
                    <h3>Medical Documents</h3>
                    <button class="button tiny ghost" type="button" data-modal-open="medicalDocumentsModal">Manage</button>
                </div>
                <?php if ($documentPreview !== []): ?>
                    <div class="patient-doc-preview-list">
                        <?php foreach ($documentPreview as $document): ?>
                            <article class="patient-doc-preview-card">
                                <div class="patient-doc-preview-top">
                                    <span class="doc-chip"><?= e($document['title']) ?></span>
                                    <span class="muted"><?= e(substr((string) $document['uploaded_at'], 0, 10)) ?></span>
                                </div>
                                <p class="muted"><?= e($documentSourceLabel($document)) ?></p>
                                <p class="muted"><?= e($document['description'] ?: 'No description provided.') ?></p>
                                <div class="actions-row">
                                    <a class="button tiny ghost" href="<?= e(url('/documents/' . $document['document_id'] . '/download?inline=1')) ?>" target="_blank">
                                        <span class="button-icon"><?= $icon('view') ?></span>
                                        <span>View</span>
                                    </a>
                                    <a class="button tiny secondary" href="<?= e(url('/documents/' . $document['document_id'] . '/download')) ?>">
                                        <span class="button-icon"><?= $icon('download') ?></span>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="patient-empty-card">No medical documents uploaded yet.</div>
                <?php endif; ?>
            </div>
        </article>

        <aside class="panel patient-sidebar-card">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon amber"><?= $icon('condition') ?></span>Quick Actions</h2>
            </div>
            <div class="patient-quick-actions">
                <button class="patient-action-btn coral" type="button" data-modal-open="patientQrModal">
                    <span class="button-icon"><?= $icon('qr') ?></span>
                    <span>QR Code</span>
                </button>
                <button class="patient-action-btn sky" type="button" data-modal-open="editProfileModal">
                    <span class="button-icon"><?= $icon('edit') ?></span>
                    <span>Edit Profile</span>
                </button>
                <button class="patient-action-btn cyan" type="button" data-modal-open="medicalDocumentsModal">
                    <span class="button-icon"><?= $icon('upload') ?></span>
                    <span>Upload Documents</span>
                </button>
                <button class="patient-action-btn green" type="button" data-modal-open="nearbyHospitalsModal">
                    <span class="button-icon"><?= $icon('hospital') ?></span>
                    <span>Nearby Hospitals</span>
                </button>
            </div>

            <div class="patient-emergency-box">
                <h3>My Emergency Numbers</h3>
                <div class="patient-emergency-callout">
                    <span class="patient-inline-icon coral"><?= $icon('phone') ?></span>
                    <span><?= e($contactLabel) ?></span>
                </div>
                <button class="button tiny ghost wide" type="button" data-modal-open="contactManageModal">Manage Emergency Numbers</button>
            </div>
        </aside>
    </section>
</section>

<div class="app-modal" id="patientQrModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="patientQrTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="patientQrTitle"><span class="patient-inline-icon coral"><?= $icon('qr') ?></span>My QR Code</h2>
                <p class="ops-panel-subtext">Use this QR for emergency profile access.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="patient-qr-panel app-modal-body">
            <img class="qr-preview" src="<?= e(url('/patient/qr/download')) ?>" alt="Patient QR code">
            <div class="actions-row">
                <a class="button tiny primary" href="<?= e(url('/patient/qr/download')) ?>">
                    <span class="button-icon"><?= $icon('download') ?></span>
                    <span>Download QR</span>
                </a>
                <a class="button tiny ghost" href="<?= e(url('/patient/qr/print')) ?>" target="_blank">Print Sticker</a>
            </div>
        </div>
    </section>
</div>

<div class="app-modal app-modal-wide" id="editProfileModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="editProfileTitle"><span class="patient-inline-icon sky"><?= $icon('edit') ?></span>Edit Medical Profile</h2>
                <p class="ops-panel-subtext">NIC is shown for reference and cannot be changed.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <form method="post" action="<?= e(url('/patient/profile')) ?>" class="grid-form compact app-modal-body">
            <?= csrf_field() ?>
            <label>Full name
                <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </label>
            <label>NIC number
                <input type="text" value="<?= e($user['nic_number'] ?? '') ?>" readonly>
            </label>
            <label>Phone
                <input type="tel" name="phone" value="<?= e($user['phone']) ?>" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone" title="Use exactly 10 digits, such as 0771234567." required>
            </label>
            <label>Date of birth
                <input type="date" name="date_of_birth" value="<?= e($user['date_of_birth']) ?>">
            </label>
            <label>Blood group
                <select name="blood_group" required>
                    <?php foreach ($bloodGroups as $group): ?>
                        <option value="<?= e($group) ?>" <?= ($profile['blood_group'] ?? 'Unknown') === $group ? 'selected' : '' ?>><?= e($group) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Emergency phone
                <input type="tel" name="emergency_phone" value="<?= e($profile['emergency_phone'] ?? '') ?>" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone-optional" title="Use exactly 10 digits, such as 0771234567.">
            </label>
            <label class="span-2">Address
                <textarea name="address" rows="2" required><?= e($user['address']) ?></textarea>
            </label>
            <label class="span-2">Allergies
                <textarea name="allergies" rows="2"><?= e($profile['allergies'] ?? '') ?></textarea>
            </label>
            <label class="span-2">Chronic conditions
                <textarea name="chronic_conditions" rows="2"><?= e($profile['chronic_conditions'] ?? '') ?></textarea>
            </label>
            <label class="span-2">Notes
                <textarea name="notes" rows="3"><?= e($profile['notes'] ?? '') ?></textarea>
            </label>
            <div class="span-2 location-picker" data-location-picker data-api-key="<?= e($mapsApiKey) ?>" data-default-lat="6.9271" data-default-lng="79.8612">
                <input type="hidden" name="profile_latitude" value="<?= e((string) ($user['profile_latitude'] ?? '')) ?>" data-location-lat>
                <input type="hidden" name="profile_longitude" value="<?= e((string) ($user['profile_longitude'] ?? '')) ?>" data-location-lng>
                <div class="location-picker-head">
                    <div>
                        <h3>Home Location</h3>
                        <p class="muted">Search with Google Maps or use current location to change your home location.</p>
                    </div>
                    <button class="button tiny ghost" type="button" data-location-current>Use current location</button>
                </div>
                <div class="location-search-row">
                    <input type="search" class="location-search" placeholder="Search home location" data-location-search>
                    <button class="button tiny primary" type="button" data-location-search-button>Search</button>
                    <button class="button tiny ghost location-drop-toggle" type="button" data-location-drop-toggle aria-pressed="false">Drop pin</button>
                </div>
                <div class="location-map" data-location-map data-location-preview></div>
                <div class="notice-area" data-location-notice hidden></div>
                <p class="location-selected" data-location-selected>No home location selected yet.</p>
            </div>
            <div class="span-2 actions-row">
                <button class="button primary" type="submit">Save Profile</button>
                <button class="button ghost" type="button" data-modal-close>Cancel</button>
            </div>
        </form>
    </section>
</div>

<div class="app-modal" id="contactManageModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="contactManageTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="contactManageTitle"><span class="patient-inline-icon coral"><?= $icon('phone') ?></span>Manage Emergency Numbers</h2>
                <p class="ops-panel-subtext">Add a trusted number for emergency contact.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="app-modal-body">
            <form method="post" action="<?= e(url('/patient/contacts')) ?>" class="stack-form patient-contact-form">
                <?= csrf_field() ?>
                <label>Name
                    <input type="text" name="contact_name" required>
                </label>
                <label>Relationship
                    <input type="text" name="relationship">
                </label>
                <label>Phone number
                    <input type="tel" name="phone_number" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone" title="Use exactly 10 digits, such as 0771234567." required>
                </label>
                <label class="inline-check">
                    <input type="checkbox" name="is_primary" value="1">
                    <span>Primary contact</span>
                </label>
                <button class="button secondary" type="submit">Save Contact</button>
            </form>
            <div class="patient-contact-list">
                <?php foreach ($contacts as $contact): ?>
                    <div class="patient-contact-row">
                        <strong><?= e($contact['contact_name']) ?></strong>
                        <span><?= e($contact['phone_number']) ?><?= !empty($contact['relationship']) ? ' | ' . e($contact['relationship']) : '' ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($contacts === []): ?>
                    <div class="patient-empty-card">No emergency contacts added yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<div class="app-modal app-modal-wide" id="medicalDocumentsModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="medicalDocumentsTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="medicalDocumentsTitle"><span class="patient-inline-icon cyan"><?= $icon('document') ?></span>Medical Documents</h2>
                <p class="ops-panel-subtext">Patient uploads and hospital discharge documents are listed here.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="app-modal-body">
            <form method="post" action="<?= e(url('/patient/documents')) ?>" enctype="multipart/form-data" class="grid-form compact">
                <?= csrf_field() ?>
                <label>Title
                    <input type="text" name="title" required>
                </label>
                <label>Category
                    <select name="document_category">
                        <option value="medical_history">Medical History</option>
                        <option value="scan">Scan / Image</option>
                        <option value="prescription">Prescription</option>
                    </select>
                </label>
                <label class="span-2">Description
                    <textarea name="description" rows="2"></textarea>
                </label>
                <label class="span-2">Choose file
                    <input type="file" name="document" required>
                </label>
                <div class="span-2">
                    <button class="button primary" type="submit">Upload Document</button>
                </div>
            </form>
            <div class="card-list">
                <?php foreach ($documents as $document): ?>
                    <article class="doc-card">
                        <div>
                            <h3><?= e($document['title']) ?></h3>
                            <p class="muted"><?= e($document['document_category']) ?> | <?= e($documentSourceLabel($document)) ?> | <?= e(format_datetime($document['uploaded_at'])) ?></p>
                            <p><?= e($document['description'] ?? 'No description provided.') ?></p>
                        </div>
                        <div class="actions-row">
                            <a class="button tiny ghost" href="<?= e(url('/documents/' . $document['document_id'] . '/download?inline=1')) ?>" target="_blank">View</a>
                            <a class="button tiny secondary" href="<?= e(url('/documents/' . $document['document_id'] . '/download')) ?>">Download</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($documents === []): ?>
                    <div class="patient-empty-card">No medical documents uploaded yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<div class="app-modal app-modal-wide" id="nearbyHospitalsModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="nearbyHospitalsTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="nearbyHospitalsTitle"><span class="patient-inline-icon teal"><?= $icon('hospital') ?></span>Nearby Registered Hospitals</h2>
                <p class="ops-panel-subtext">Only hospitals stored in the HealthFirst database are ranked here.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="app-modal-body patient-hospital-modal">
            <div id="patientHospitalMap" class="map-box" data-api-key="<?= e($mapsApiKey) ?>"></div>
            <div id="patientHospitalNotice" class="notice-area">Open this panel to search your nearest hospitals.</div>
            <div id="patientHospitalList" class="patient-hospital-list"></div>
        </div>
    </section>
</div>

<script src="<?= e(asset('js/modules/patient.js')) ?>" defer></script>

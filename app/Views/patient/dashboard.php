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
        <a class="button patient-hero-qr" href="#patient-qr-section" data-open-panel="patient-qr-section">
            <span class="button-icon"><?= $icon('qr') ?></span>
            <span>QR Code</span>
        </a>
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
                <button class="button tiny primary" type="button" data-open-panel="profile-manage-section">
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
                    <h3>Uploaded Medical Documents</h3>
                    <button class="button tiny ghost" type="button" data-open-panel="document-manage-section">Manage</button>
                </div>
                <?php if ($documentPreview !== []): ?>
                    <div class="patient-doc-preview-list">
                        <?php foreach ($documentPreview as $document): ?>
                            <article class="patient-doc-preview-card">
                                <div class="patient-doc-preview-top">
                                    <span class="doc-chip"><?= e($document['title']) ?></span>
                                    <span class="muted"><?= e(substr((string) $document['uploaded_at'], 0, 10)) ?></span>
                                </div>
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
                <a class="patient-action-btn coral" href="#patient-qr-section" data-open-panel="patient-qr-section">
                    <span class="button-icon"><?= $icon('qr') ?></span>
                    <span>QR Code</span>
                </a>
                <button class="patient-action-btn sky" type="button" data-open-panel="profile-manage-section">
                    <span class="button-icon"><?= $icon('edit') ?></span>
                    <span>Edit Profile</span>
                </button>
                <button class="patient-action-btn cyan" type="button" data-open-panel="document-manage-section">
                    <span class="button-icon"><?= $icon('upload') ?></span>
                    <span>Upload Documents</span>
                </button>
                <a class="patient-action-btn green" href="#patient-hospitals-section">
                    <span class="button-icon"><?= $icon('hospital') ?></span>
                    <span>Nearby Hospitals</span>
                </a>
            </div>

            <div class="patient-emergency-box">
                <h3>My Emergency Numbers</h3>
                <div class="patient-emergency-callout">
                    <span class="patient-inline-icon coral"><?= $icon('phone') ?></span>
                    <span><?= e($contactLabel) ?></span>
                </div>
                <button class="button tiny ghost wide" type="button" data-open-panel="contact-manage-section">Manage Emergency Numbers</button>
            </div>
        </aside>
    </section>

    <section class="patient-management-stack">
        <article class="panel patient-manage-panel hidden" id="patient-qr-section">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon coral"><?= $icon('qr') ?></span>My QR Code</h2>
                <div class="actions-row">
                    <a class="button tiny primary" href="<?= e(url('/patient/qr/download')) ?>">
                        <span class="button-icon"><?= $icon('download') ?></span>
                        <span>Download QR</span>
                    </a>
                    <a class="button tiny ghost" href="<?= e(url('/patient/qr/print')) ?>" target="_blank">Print Sticker</a>
                </div>
            </div>
            <div class="patient-qr-panel">
                <img class="qr-preview" src="<?= e(url('/patient/qr/download')) ?>" alt="Patient QR code">
                <p class="muted">This QR code opens your secure emergency profile page for first responders.</p>
            </div>
        </article>

        <article class="panel patient-manage-panel hidden" id="profile-manage-section">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon sky"><?= $icon('edit') ?></span>Edit Medical Profile</h2>
            </div>
            <form method="post" action="<?= e(url('/patient/profile')) ?>" class="grid-form compact">
                <?= csrf_field() ?>
                <label>Full name
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </label>
                <label>Phone
                    <input type="text" name="phone" value="<?= e($user['phone']) ?>" required>
                </label>
                <label class="span-2">Address
                    <textarea name="address" rows="2" required><?= e($user['address']) ?></textarea>
                </label>
                <label>Date of birth
                    <input type="date" name="date_of_birth" value="<?= e($user['date_of_birth']) ?>">
                </label>
                <label>Blood group
                    <input type="text" name="blood_group" value="<?= e($profile['blood_group'] ?? '') ?>">
                </label>
                <label>Emergency phone
                    <input type="text" name="emergency_phone" value="<?= e($profile['emergency_phone'] ?? '') ?>">
                </label>
                <label>Profile latitude
                    <input type="text" name="profile_latitude" value="<?= e($user['profile_latitude'] ?? '') ?>">
                </label>
                <label>Profile longitude
                    <input type="text" name="profile_longitude" value="<?= e($user['profile_longitude'] ?? '') ?>">
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
                <div class="span-2">
                    <button class="button primary" type="submit">Save Profile</button>
                </div>
            </form>
        </article>

        <article class="panel patient-manage-panel hidden" id="contact-manage-section">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon coral"><?= $icon('phone') ?></span>Manage Emergency Numbers</h2>
            </div>
            <form method="post" action="<?= e(url('/patient/contacts')) ?>" class="stack-form patient-contact-form">
                <?= csrf_field() ?>
                <label>Name
                    <input type="text" name="contact_name" required>
                </label>
                <label>Relationship
                    <input type="text" name="relationship">
                </label>
                <label>Phone number
                    <input type="text" name="phone_number" required>
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
        </article>

        <article class="panel patient-manage-panel hidden" id="document-manage-section">
            <div class="patient-panel-head">
                <h2><span class="patient-inline-icon cyan"><?= $icon('document') ?></span>Medical Documents</h2>
            </div>
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
                            <p class="muted"><?= e($document['document_category']) ?> | <?= e(format_datetime($document['uploaded_at'])) ?></p>
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
        </article>
    </section>

    <section class="panel patient-hospital-panel" id="patient-hospitals-section">
        <div class="patient-panel-head">
            <div>
                <h2><span class="patient-inline-icon teal"><?= $icon('hospital') ?></span>Nearby Registered Hospitals</h2>
                <p class="muted">Only hospitals stored in the HealthFirst database are ranked here.</p>
            </div>
        </div>
        <div id="patientHospitalMap" class="map-box" data-api-key="<?= e($mapsApiKey) ?>"></div>
        <div id="patientHospitalNotice" class="notice-area">Searching your nearest hospitals...</div>
        <div id="patientHospitalList" class="patient-hospital-list"></div>
    </section>
</section>
<script src="<?= e(asset('js/modules/patient.js')) ?>" defer></script>

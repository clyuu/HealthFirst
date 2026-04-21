<?php
$age = !empty($user['date_of_birth']) ? date_diff(date_create($user['date_of_birth']), new DateTime())->y : null;
?>
<section class="dashboard-header">
    <div>
        <h1>Welcome, <?= e($user['full_name']) ?></h1>
        <p class="muted">Today: <?= e(date('Y-m-d l')) ?><?= $age !== null ? ' | Age: ' . e((string) $age) : '' ?></p>
    </div>
    <div class="header-actions">
        <a class="button primary" href="<?= e(url('/patient/qr/download')) ?>">Download QR</a>
        <a class="button ghost" href="<?= e(url('/patient/qr/print')) ?>" target="_blank">Print Sticker</a>
    </div>
</section>
<script src="<?= e(asset('js/modules/patient.js')) ?>" defer></script>

<section class="stats-grid">
    <article class="stat-card">
        <h3>Profile Status</h3>
        <p><?= !empty($profile) ? 'Complete' : 'Pending' ?></p>
    </article>
    <article class="stat-card">
        <h3>Emergency Contacts</h3>
        <p><?= e((string) count($contacts)) ?></p>
    </article>
    <article class="stat-card">
        <h3>Medical Documents</h3>
        <p><?= e((string) count($documents)) ?></p>
    </article>
    <article class="stat-card highlighted">
        <h3>Nearby Hospitals</h3>
        <p id="nearbyHospitalCount">Live</p>
    </article>
</section>

<section class="two-col">
    <div class="panel">
        <h2>Medical Profile</h2>
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
    </div>

    <div class="stack">
        <div class="panel">
            <h2>My QR</h2>
            <img class="qr-preview" src="<?= e(url('/patient/qr/download')) ?>" alt="Patient QR code">
            <p class="muted">This QR encodes only your secure public emergency URL.</p>
        </div>
        <div class="panel">
            <h2>Add Emergency Contact</h2>
            <form method="post" action="<?= e(url('/patient/contacts')) ?>" class="stack-form">
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
            <div class="list-space">
                <?php foreach ($contacts as $contact): ?>
                    <div class="item-row">
                        <strong><?= e($contact['contact_name']) ?></strong>
                        <span><?= e($contact['phone_number']) ?><?= !empty($contact['relationship']) ? ' | ' . e($contact['relationship']) : '' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="two-col">
    <div class="panel">
        <h2>Medical Documents</h2>
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
                        <a class="button tiny" href="<?= e(url('/documents/' . $document['document_id'] . '/download?inline=1')) ?>" target="_blank">View</a>
                        <a class="button tiny ghost" href="<?= e(url('/documents/' . $document['document_id'] . '/download')) ?>">Download</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Nearby Registered Hospitals</h2>
        <p class="muted">Only hospitals stored in the HealthFirst database are ranked here.</p>
        <div id="patientHospitalMap" class="map-box" data-api-key="<?= e($mapsApiKey) ?>"></div>
        <div id="patientHospitalList" class="card-list"></div>
    </div>
</section>

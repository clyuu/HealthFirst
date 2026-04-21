<section class="dashboard-header">
    <div>
        <h1><?= e($hospital['hospital_name'] ?? 'Hospital Dashboard') ?></h1>
        <p class="muted">Red = waiting assignment, Yellow = ambulance on the way, Green = patient picked up.</p>
    </div>
    <div class="header-actions">
        <a class="button ghost" href="<?= e(url('/doctor/dashboard')) ?>">Doctor View</a>
    </div>
</section>

<section class="tile-grid" id="hospitalTileGrid" data-hospital-dashboard data-feed-url="<?= e(url('/api/hospital/incidents')) ?>">
    <?php foreach ($incidents as $incident): ?>
        <article class="incident-tile <?= e(incident_tile_class($incident['status'])) ?>" data-incident-id="<?= e((string) $incident['incident_id']) ?>" data-status="<?= e($incident['status']) ?>">
            <div class="tile-head">
                <h3><?= e($incident['patient_name']) ?></h3>
                <span class="badge"><?= e($incident['status']) ?></span>
            </div>
            <p><strong>Blood:</strong> <?= e($incident['blood_group'] ?? 'Unknown') ?></p>
            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None recorded') ?></p>
            <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
            <p><strong>Accident AI:</strong> <?= e($incident['verification_result'] ?? '-') ?> (<?= e((string) ($incident['verification_confidence'] ?? 0)) ?>%)</p>
            <p><strong>ETA:</strong> <span data-eta-seconds="<?= e((string) ($incident['scene_eta_seconds'] ?? 0)) ?>"><?= e((string) round(((int) ($incident['scene_eta_seconds'] ?? 0)) / 60)) ?> min</span></p>

            <?php if (!empty($incident['report_file_path'])): ?>
                <div class="report-links">
                    <a class="button tiny" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">View Injury Report</a>
                </div>
            <?php endif; ?>

            <?php if ($incident['status'] === 'verified_unassigned' && !empty($ambulances)): ?>
                <form method="post" class="assign-form" data-assign-form action="<?= e(url('/hospital/incidents/' . $incident['incident_id'] . '/assign')) ?>">
                    <?= csrf_field() ?>
                    <label>Assign ambulance
                        <select name="ambulance_id" required>
                            <option value="">Select</option>
                            <?php foreach ($ambulances as $ambulance): ?>
                                <option value="<?= e((string) $ambulance['ambulance_id']) ?>"><?= e($ambulance['ambulance_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button warning" type="submit">Assign Ambulance</button>
                </form>
            <?php endif; ?>

            <?php if (!empty($incident['special_note'])): ?>
                <p><strong>Special Note:</strong> <?= e($incident['special_note']) ?></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2>Discharged Patients</h2>
    <div class="card-list">
        <?php foreach ($discharged as $incident): ?>
            <article class="doc-card">
                <h3><?= e($incident['patient_name']) ?></h3>
                <p><?= e($incident['doctor_name'] ?? 'Doctor pending') ?></p>
                <p class="muted"><?= e(format_datetime($incident['reported_at'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<script src="<?= e(asset('js/modules/hospital.js')) ?>" defer></script>

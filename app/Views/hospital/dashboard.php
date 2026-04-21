<?php
$unassignedCount = count(array_filter($incidents, static fn (array $incident): bool => ($incident['status'] ?? '') === 'verified_unassigned'));
$assignedCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['status'] ?? ''), ['ambulance_assigned', 'en_route_scene'], true)));
$pickupCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true)));
$dischargedCount = count($discharged);
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div>
            <span class="ops-eyebrow">Emergency Board</span>
            <h1><?= e($hospital['hospital_name'] ?? 'Hospital Dashboard') ?></h1>
            <p>Red = waiting assignment, Yellow = ambulance on the way, Green = patient picked up.</p>
        </div>
        <div class="ops-actions">
            <a class="button ghost" href="<?= e(url('/doctor/dashboard')) ?>">Doctor View</a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <p class="ops-metric-label">Waiting Assignment</p>
            <p class="ops-metric-value"><?= e((string) $unassignedCount) ?></p>
            <div class="ops-metric-note">Verified incidents that still need an ambulance.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">En Route</p>
            <p class="ops-metric-value"><?= e((string) $assignedCount) ?></p>
            <div class="ops-metric-note">Ambulances already dispatched and moving to the scene.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Picked Up</p>
            <p class="ops-metric-value"><?= e((string) $pickupCount) ?></p>
            <div class="ops-metric-note">Patients already collected from the scene.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Discharged</p>
            <p class="ops-metric-value"><?= e((string) $dischargedCount) ?></p>
            <div class="ops-metric-note">Completed cases available in the historical list below.</div>
        </article>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2>Live Incident Board</h2>
                <p class="ops-panel-subtext">Assign ambulances, watch ETA updates, and inspect AI verification results from one board.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-feed-grid" id="hospitalTileGrid" data-hospital-dashboard data-feed-url="<?= e(url('/api/hospital/incidents')) ?>">
                <?php foreach ($incidents as $incident): ?>
                    <article class="incident-tile ops-incident-card <?= e(incident_tile_class($incident['status'])) ?>" data-incident-id="<?= e((string) $incident['incident_id']) ?>" data-status="<?= e($incident['status']) ?>">
                        <div class="tile-head">
                            <h3><?= e($incident['patient_name']) ?></h3>
                            <span class="badge"><?= e($incident['status']) ?></span>
                        </div>
                        <div class="ops-inline-stats">
                            <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                            <span class="ops-kicker warning"><?= e((string) round(((int) ($incident['scene_eta_seconds'] ?? 0)) / 60)) ?> min ETA</span>
                        </div>
                        <div class="ops-incident-meta">
                            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None recorded') ?></p>
                            <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
                            <p><strong>Accident AI:</strong> <?= e($incident['verification_result'] ?? '-') ?> (<?= e((string) ($incident['verification_confidence'] ?? 0)) ?>%)</p>
                            <p><strong>ETA:</strong> <span data-eta-seconds="<?= e((string) ($incident['scene_eta_seconds'] ?? 0)) ?>"><?= e((string) round(((int) ($incident['scene_eta_seconds'] ?? 0)) / 60)) ?> min</span></p>
                        </div>

                        <?php if (!empty($incident['report_file_path'])): ?>
                            <div class="ops-inline-actions">
                                <a class="button tiny ghost" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">View Injury Report</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($incident['status'] === 'verified_unassigned' && !empty($ambulances)): ?>
                            <div class="ops-form-card">
                                <h3>Assign Ambulance</h3>
                                <form method="post" class="assign-form stack-form" data-assign-form action="<?= e(url('/hospital/incidents/' . $incident['incident_id'] . '/assign')) ?>">
                                    <?= csrf_field() ?>
                                    <label>Available ambulance
                                        <select name="ambulance_id" required>
                                            <option value="">Select</option>
                                            <?php foreach ($ambulances as $ambulance): ?>
                                                <option value="<?= e((string) $ambulance['ambulance_id']) ?>"><?= e($ambulance['ambulance_number']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button class="button warning" type="submit">Assign Ambulance</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($incident['special_note'])): ?>
                            <div class="ops-note"><strong>Special Note:</strong> <?= e($incident['special_note']) ?></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if ($incidents === []): ?>
                    <div class="ops-empty">No live incidents are currently active for this hospital.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2>Discharged Patients</h2>
                <p class="ops-panel-subtext">Completed cases and their associated doctor activity.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-list">
                <?php foreach ($discharged as $incident): ?>
                    <article class="ops-mini-card">
                        <strong><?= e($incident['patient_name']) ?></strong>
                        <span><?= e($incident['doctor_name'] ?? 'Doctor pending') ?></span>
                        <p class="muted"><?= e(format_datetime($incident['reported_at'])) ?></p>
                    </article>
                <?php endforeach; ?>
                <?php if ($discharged === []): ?>
                    <div class="ops-empty">No discharged patients recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/hospital.js')) ?>" defer></script>

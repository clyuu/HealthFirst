<?php
$unassignedCount = count(array_filter($incidents, static fn (array $incident): bool => ($incident['status'] ?? '') === 'verified_unassigned'));
$assignedCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['status'] ?? ''), ['ambulance_assigned', 'en_route_scene'], true)));
$pickupCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true)));
$arrivalCount = count($arrivals ?? []);
$dischargedCount = count($discharged);
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol blue"><?= ui_icon('hospital') ?></span>
            <div>
                <span class="ops-eyebrow">Hospital Front Desk</span>
                <h1><?= e($hospital['hospital_name'] ?? 'Hospital Dashboard') ?></h1>
                <p>Front-line nurses and desk staff can watch incoming emergencies, assign ambulances, and track ETA changes here.</p>
            </div>
        </div>
        <div class="ops-actions">
            <?php if (\App\Core\Auth::hasRole('hospital_admin')): ?>
                <a class="button ghost" href="<?= e(url('/admin/hospital')) ?>">
                    <span class="button-icon"><?= ui_icon('shield') ?></span>
                    <span>Hospital Admin</span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon red"><?= ui_icon('timer') ?></span>
            <p class="ops-metric-label">Waiting Assignment</p>
            <p class="ops-metric-value"><?= e((string) $unassignedCount) ?></p>
            <div class="ops-metric-note">Verified incidents that still need an ambulance.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon amber"><?= ui_icon('ambulance') ?></span>
            <p class="ops-metric-label">En Route</p>
            <p class="ops-metric-value"><?= e((string) $assignedCount) ?></p>
            <div class="ops-metric-note">Ambulances already dispatched and moving to the scene.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon green"><?= ui_icon('user-check') ?></span>
            <p class="ops-metric-label">Picked Up</p>
            <p class="ops-metric-value"><?= e((string) $pickupCount) ?></p>
            <div class="ops-metric-note">Patients already collected from the scene.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon sky"><?= ui_icon('hospital') ?></span>
            <p class="ops-metric-label">Arrived</p>
            <p class="ops-metric-value"><?= e((string) $arrivalCount) ?></p>
            <div class="ops-metric-note">Patients handed over at the hospital and waiting for doctor flow.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon teal"><?= ui_icon('check') ?></span>
            <p class="ops-metric-label">Discharged</p>
            <p class="ops-metric-value"><?= e((string) $dischargedCount) ?></p>
            <div class="ops-metric-note">Completed cases available in the historical list below.</div>
        </article>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2><span class="ops-inline-icon accent"><?= ui_icon('activity') ?></span>Live Incident Board</h2>
                <p class="ops-panel-subtext">Dedicated hospital dashboard for the reception and emergency coordination team.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-banner" id="hospitalAssignNotice" hidden></div>
            <div class="ops-feed-grid" id="hospitalTileGrid" data-hospital-dashboard data-feed-url="<?= e(url('/api/hospital/incidents')) ?>" data-maps-api-key="<?= e($mapsApiKey) ?>">
                <?php foreach ($incidents as $incident): ?>
                    <?php
                    $etaRemaining = (int) ($incident['display_eta_seconds'] ?? 0);
                    $distanceMeters = $incident['display_distance_meters'] ?? null;
                    $displayDestinationLat = $incident['display_destination_latitude'] ?? $incident['incident_latitude'];
                    $displayDestinationLng = $incident['display_destination_longitude'] ?? $incident['incident_longitude'];
                    $displayDestinationLabel = $incident['display_destination_label'] ?? 'accident scene';
                    ?>
                    <article class="incident-tile ops-incident-card <?= e(incident_tile_class($incident['status'])) ?>" id="incident-<?= e((string) $incident['incident_id']) ?>" data-incident-id="<?= e((string) $incident['incident_id']) ?>" data-status="<?= e($incident['status']) ?>" data-map-card data-origin-lat="<?= e((string) ($incident['display_origin_latitude'] ?? $incident['hospital_latitude'] ?? '')) ?>" data-origin-lng="<?= e((string) ($incident['display_origin_longitude'] ?? $incident['hospital_longitude'] ?? '')) ?>" data-dest-lat="<?= e((string) $displayDestinationLat) ?>" data-dest-lng="<?= e((string) $displayDestinationLng) ?>" data-dest-label="<?= e($displayDestinationLabel) ?>" data-route-polyline="<?= e((string) ($incident['display_route_polyline'] ?? '')) ?>">
                        <div class="tile-head">
                            <div>
                                <h3><?= e($incident['patient_name']) ?></h3>
                                <p class="ops-incident-ref">Incident #<?= e((string) $incident['incident_id']) ?> · <?= e(format_datetime($incident['reported_at'] ?? null)) ?></p>
                            </div>
                            <span class="badge"><?= e($incident['status']) ?></span>
                        </div>
                        <div class="ops-inline-stats">
                            <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                            <span class="ops-kicker warning" data-eta-badge><?= e((string) max((int) ceil($etaRemaining / 60), 0)) ?> min ETA</span>
                        </div>
                        <div class="ops-incident-meta">
                            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None recorded') ?></p>
                            <p><strong>Identifier:</strong> <?= e($incident['nic_number'] ?? $incident['patient_phone'] ?? 'Waiting for patient confirmation') ?></p>
                            <?php if (!empty($incident['ambulance_number'])): ?>
                                <p><strong>Ambulance:</strong> <?= e($incident['ambulance_number']) ?></p>
                            <?php endif; ?>
                            <?php if ($distanceMeters !== null): ?>
                                <p><strong>Distance:</strong> <span data-distance-label><?= e(number_format(((int) $distanceMeters) / 1000, 1)) ?> km</span></p>
                            <?php endif; ?>
                            <p><strong>Accident AI:</strong> <?= e($incident['verification_result'] ?? '-') ?> (<?= e((string) ($incident['verification_confidence'] ?? 0)) ?>%)</p>
                            <p><strong>ETA:</strong> <span data-eta-seconds="<?= e((string) $etaRemaining) ?>" data-eta-live="<?= !empty($incident['eta_live']) ? '1' : '0' ?>" data-eta-label><?= e((string) max((int) ceil($etaRemaining / 60), 0)) ?> min</span></p>
                        </div>

                        <div class="map-box mini hospital-incident-map" data-map-canvas></div>
                        <div class="ops-note">Blue marker = route origin, red marker = <?= e($displayDestinationLabel) ?>.</div>

                        <?php if (!empty($incident['report_file_path'])): ?>
                            <div class="ops-inline-actions">
                                <a class="button tiny ghost" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">
                                    <span class="button-icon"><?= ui_icon('report') ?></span>
                                    <span>View Injury Report</span>
                                </a>
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
                                    <button class="button warning" type="submit">
                                        <span class="button-icon"><?= ui_icon('ambulance') ?></span>
                                        <span>Assign Ambulance</span>
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($incident['status'] === 'verified_unassigned'): ?>
                            <div class="ops-note">No ambulances are configured as available for this hospital yet.</div>
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
                <h2><span class="ops-inline-icon sky"><?= ui_icon('hospital') ?></span>Arrived Patient History</h2>
                <p class="ops-panel-subtext">Ambulance handovers confirmed at the hospital. These stay here until the doctor discharge flow is completed.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-feed-grid">
                <?php foreach (($arrivals ?? []) as $incident): ?>
                    <article class="incident-tile ops-incident-card tile-green">
                        <div class="tile-head">
                            <div>
                                <h3><?= e($incident['patient_name']) ?></h3>
                                <p class="ops-incident-ref">Incident #<?= e((string) $incident['incident_id']) ?> Â· Arrived <?= e(format_datetime($incident['arrived_hospital_at'] ?? null)) ?></p>
                            </div>
                            <span class="badge">Arrived at hospital</span>
                        </div>
                        <div class="ops-inline-stats">
                            <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                            <span class="ops-kicker success"><?= e($incident['status'] === 'admitted' ? 'Doctor admitted' : 'Waiting doctor admit') ?></span>
                        </div>
                        <div class="ops-incident-meta">
                            <p><strong>Identifier:</strong> <?= e($incident['nic_number'] ?? $incident['patient_phone'] ?? 'Waiting for patient confirmation') ?></p>
                            <p><strong>Ambulance:</strong> <?= e($incident['ambulance_number'] ?? 'Ambulance pending') ?></p>
                            <p><strong>Doctor:</strong> <?= e($incident['doctor_name'] ?? 'Not assigned yet') ?></p>
                            <?php if (!empty($incident['overall_severity'])): ?>
                                <p><strong>AI Severity:</strong> <?= e($incident['overall_severity']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($incident['report_file_path'])): ?>
                            <div class="ops-inline-actions">
                                <a class="button tiny ghost" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">
                                    <span class="button-icon"><?= ui_icon('report') ?></span>
                                    <span>View Injury Report</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (($arrivals ?? []) === []): ?>
                    <div class="ops-empty">No patients have been handed over at the hospital yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="ops-panel" id="discharged-patients">
        <div class="ops-panel-head">
            <div>
                <h2><span class="ops-inline-icon green"><?= ui_icon('check') ?></span>Discharged Patients</h2>
                <p class="ops-panel-subtext">Upload the final hospital-care documents here. Once uploaded, the patient is cleared from this working list.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-list">
                <?php foreach ($discharged as $incident): ?>
                    <article class="ops-mini-card ops-discharge-card">
                        <div class="tile-head">
                            <div>
                                <strong><?= e($incident['patient_name']) ?></strong>
                                <span>Doctor: <?= e($incident['doctor_name'] ?? 'Doctor pending') ?></span>
                                <p class="muted">Incident #<?= e((string) $incident['incident_id']) ?> - <?= e(format_datetime($incident['reported_at'])) ?></p>
                            </div>
                            <span class="ops-kicker success">Ready for documents</span>
                        </div>
                        <form method="post" action="<?= e(url('/hospital/incidents/' . $incident['incident_id'] . '/documents')) ?>" enctype="multipart/form-data" class="grid-form compact">
                            <?= csrf_field() ?>
                            <label>Document title
                                <input type="text" name="title" value="Hospital discharge documents" required>
                            </label>
                            <label>Files
                                <input type="file" name="documents[]" multiple required>
                            </label>
                            <label class="span-2">Description
                                <textarea name="description" rows="2" placeholder="Treatment summary, discharge notes, prescriptions, lab reports, or other hospital-care notes."></textarea>
                            </label>
                            <div class="span-2 actions-row">
                                <button class="button secondary tiny" type="submit">
                                    <span class="button-icon"><?= ui_icon('upload') ?></span>
                                    <span>Upload & Clear</span>
                                </button>
                            </div>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if ($discharged === []): ?>
                    <div class="ops-empty">No discharged patients are waiting for document upload.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/hospital.js')) ?>" defer></script>

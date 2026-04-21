<?php
$activeCount = count($incidents);
$reportReadyCount = count(array_filter($incidents, static fn (array $incident): bool => !empty($incident['report_file_path'])));
$pickupCount = count(array_filter($incidents, static fn (array $incident): bool => ($incident['status'] ?? '') === 'patient_picked_up'));
$assignedHospital = $ambulance['hospital_name'] ?? 'Unassigned';
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div>
            <span class="ops-eyebrow">Ambulance Control</span>
            <h1>Ambulance Dashboard</h1>
            <p><?= e($ambulance['ambulance_number'] ?? 'No ambulance assigned') ?> | <?= e($assignedHospital) ?></p>
        </div>
        <div class="ops-actions">
            <span class="ops-kicker success"><?= e((string) $activeCount) ?> Active Dispatches</span>
            <span class="ops-kicker warning"><?= e((string) $pickupCount) ?> Pickups In Progress</span>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <p class="ops-metric-label">Dispatch Queue</p>
            <p class="ops-metric-value"><?= e((string) $activeCount) ?></p>
            <div class="ops-metric-note">Current incidents assigned to this ambulance crew.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Hospital Ready</p>
            <p class="ops-metric-value"><?= e((string) $reportReadyCount) ?></p>
            <div class="ops-metric-note">Cases with injury reports already generated.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Picked Up</p>
            <p class="ops-metric-value"><?= e((string) $pickupCount) ?></p>
            <div class="ops-metric-note">Patients currently en route to the hospital.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Base Hospital</p>
            <p class="ops-metric-value"><?= e((string) ($ambulance['ambulance_number'] ?? '--')) ?></p>
            <div class="ops-metric-note"><?= e($assignedHospital) ?></div>
        </article>
    </section>

    <section class="ops-split">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Active Dispatches</h2>
                    <p class="ops-panel-subtext">Vitals, pickup flow, injury sessions, and QR-based patient lookup stay on one board.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="ops-feed-grid" id="ambulanceIncidentList" data-ambulance-dashboard data-feed-url="<?= e(url('/api/ambulance/incidents')) ?>" data-location-url-template="<?= e(url('/ambulance/incidents/__ID__/location')) ?>">
                    <?php foreach ($incidents as $incident): ?>
                        <article class="incident-tile ops-incident-card <?= e(incident_tile_class($incident['status'])) ?>" data-incident-card data-incident-id="<?= e((string) $incident['incident_id']) ?>">
                            <div class="tile-head">
                                <h3><?= e($incident['patient_name']) ?></h3>
                                <span class="badge"><?= e($incident['ambulance_number']) ?></span>
                            </div>
                            <div class="ops-inline-stats">
                                <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                                <span class="ops-kicker warning"><?= e((string) ($incident['status'] ?? 'pending')) ?></span>
                            </div>
                            <div class="ops-incident-meta">
                                <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None') ?></p>
                                <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
                                <p><strong>Hospital ETA:</strong> <span data-eta-seconds="<?= e((string) ($incident['hospital_eta_seconds'] ?? $incident['scene_eta_seconds'] ?? 0)) ?>"></span></p>
                            </div>

                            <div class="ops-form-card">
                                <h3>Lookup Patient</h3>
                                <form class="stack-form lookup-form" data-lookup-form action="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/lookup-patient')) ?>">
                                    <?= csrf_field() ?>
                                    <label>Scan QR token or enter NIC
                                        <input type="text" name="public_token" placeholder="QR public token">
                                    </label>
                                    <label>NIC number
                                        <input type="text" name="nic_number" placeholder="Optional NIC fallback">
                                    </label>
                                    <button class="button secondary" type="submit">Load Patient Details</button>
                                </form>
                            </div>

                            <div class="ops-form-card">
                                <h3>Patient Vitals</h3>
                                <form class="grid-form compact" data-vitals-form action="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/vitals')) ?>">
                                    <?= csrf_field() ?>
                                    <label>Heart rate
                                        <input type="number" name="heart_rate">
                                    </label>
                                    <label>SpO2
                                        <input type="number" name="spo2">
                                    </label>
                                    <label>Systolic BP
                                        <input type="number" name="systolic_bp">
                                    </label>
                                    <label>Diastolic BP
                                        <input type="number" name="diastolic_bp">
                                    </label>
                                    <label>Temp (C)
                                        <input type="number" step="0.1" name="temperature_c">
                                    </label>
                                    <label class="span-2">Notes
                                        <textarea name="notes" rows="2"></textarea>
                                    </label>
                                    <div class="span-2">
                                        <button class="button tiny" type="submit">Save Vitals</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ops-form-card">
                                <h3>Incident Route</h3>
                                <div class="map-box mini" data-map-card data-api-key="<?= e($mapsApiKey) ?>" data-origin-lat="<?= e((string) ($incident['ambulance_latitude'] ?? '')) ?>" data-origin-lng="<?= e((string) ($incident['ambulance_longitude'] ?? '')) ?>" data-dest-lat="<?= e((string) $incident['incident_latitude']) ?>" data-dest-lng="<?= e((string) $incident['incident_longitude']) ?>"></div>
                                <div class="ops-inline-actions">
                                    <button class="button warning" type="button" data-pickup-button data-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/pickup')) ?>">Pickup Patient</button>
                                </div>
                            </div>

                            <div class="ops-form-card injury-uploader" data-injury-root data-start-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/injury-session')) ?>">
                                <h3>Injury Session</h3>
                                <?= csrf_field() ?>
                                <label>Special note
                                    <textarea name="special_note" rows="2" placeholder="Add ambulance special note"></textarea>
                                </label>
                                <div class="actions-row">
                                    <button class="button primary" type="button" data-start-injury>Start Injury Session</button>
                                </div>
                                <div class="hidden" data-injury-active>
                                    <label>Upload injury photo
                                        <input type="file" accept="image/*" capture="environment" data-injury-file>
                                    </label>
                                    <div class="actions-row">
                                        <button class="button tiny" type="button" data-attach-photo>Attach</button>
                                        <button class="button tiny ghost" type="button" data-finalize-session>Finalize Report</button>
                                    </div>
                                    <div class="notice-area" data-injury-status></div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($incidents === []): ?>
                        <div class="ops-empty">No active dispatches are assigned to this ambulance right now.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="ops-stack">
            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div>
                        <h2>Route Guidance</h2>
                        <p class="ops-panel-subtext">Live navigation stays inside HealthFirst and syncs location updates automatically.</p>
                    </div>
                </div>
                <div class="ops-map-wrap">
                    <div id="ambulanceMasterMap" class="map-box" data-api-key="<?= e($mapsApiKey) ?>"></div>
                    <div class="ops-note">Keep this board open during field movement so ETA and route markers stay fresh.</div>
                </div>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/ambulance.js')) ?>" defer></script>

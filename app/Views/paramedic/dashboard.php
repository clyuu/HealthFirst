<?php
$activeCount = count($incidents);
$reportReadyCount = count(array_filter($incidents, static fn (array $incident): bool => !empty($incident['report_file_path'])));
$severeCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['overall_severity'] ?? ''), ['Critical', 'Severe'], true)));
$assignedHospital = $ambulance['hospital_name'] ?? 'Unassigned';
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol coral"><?= ui_icon('stethoscope') ?></span>
            <div>
                <span class="ops-eyebrow">Patient Care</span>
                <h1>Paramedic Dashboard</h1>
                <p><?= e($ambulance['ambulance_number'] ?? 'No ambulance assigned') ?> | <?= e($assignedHospital) ?></p>
            </div>
        </div>
        <div class="ops-actions">
            <a class="button ghost" href="<?= e(url('/ambulance/dashboard')) ?>">
                <span class="button-icon"><?= ui_icon('ambulance') ?></span>
                <span>Ambulance Board</span>
            </a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon blue"><?= ui_icon('patient') ?></span>
            <p class="ops-metric-label">Patients In Queue</p>
            <p class="ops-metric-value"><?= e((string) $activeCount) ?></p>
            <div class="ops-metric-note">Assigned incidents needing active field medical work.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon cyan"><?= ui_icon('report') ?></span>
            <p class="ops-metric-label">Reports Ready</p>
            <p class="ops-metric-value"><?= e((string) $reportReadyCount) ?></p>
            <div class="ops-metric-note">Patients with an injury report already generated.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon red"><?= ui_icon('activity') ?></span>
            <p class="ops-metric-label">High Severity</p>
            <p class="ops-metric-value"><?= e((string) $severeCount) ?></p>
            <div class="ops-metric-note">Critical or severe cases currently in the field queue.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon teal"><?= ui_icon('ambulance') ?></span>
            <p class="ops-metric-label">Assigned Vehicle</p>
            <p class="ops-metric-value"><?= e((string) ($ambulance['ambulance_number'] ?? '--')) ?></p>
            <div class="ops-metric-note"><?= e($assignedHospital) ?></div>
        </article>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2><span class="ops-inline-icon coral"><?= ui_icon('clipboard') ?></span>Paramedic Care Board</h2>
                <p class="ops-panel-subtext">Lookup patients, record vitals, and finalize injury reports without the transport controls mixed in.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-feed-grid" id="paramedicCareList">
                <?php foreach ($incidents as $incident): ?>
                    <?php
                    $isHospitalRoute = in_array(($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true);
                    $displayEtaRemaining = (int) ($incident['display_eta_seconds'] ?? 0);
                    ?>
                    <article class="incident-tile ops-incident-card <?= e(incident_tile_class($incident['status'])) ?>" data-incident-card data-incident-id="<?= e((string) $incident['incident_id']) ?>">
                        <div class="tile-head">
                            <h3><?= e($incident['patient_name']) ?></h3>
                            <span class="badge"><?= e($incident['ambulance_number']) ?></span>
                        </div>
                        <div class="ops-inline-stats">
                            <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                            <span class="ops-kicker <?= in_array(($incident['overall_severity'] ?? ''), ['Critical', 'Severe'], true) ? 'danger' : 'warning' ?>">
                                <?= e($incident['overall_severity'] ?? ($incident['status'] ?? 'Pending')) ?>
                            </span>
                        </div>
                        <div class="ops-incident-meta">
                            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None') ?></p>
                            <p><strong>Identifier:</strong> <?= e($incident['nic_number'] ?? $incident['patient_phone'] ?? 'Waiting for confirmation') ?></p>
                            <p><strong>Vehicle:</strong> <?= e($incident['reported_vehicle_number'] ?? 'Not provided') ?></p>
                            <p><strong><?= $isHospitalRoute ? 'Hospital ETA' : 'Scene ETA' ?>:</strong> <span data-eta-seconds="<?= e((string) $displayEtaRemaining) ?>" data-eta-live="<?= !empty($incident['eta_live']) ? '1' : '0' ?>"></span></p>
                            <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
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
                                <button class="button secondary" type="submit">
                                    <span class="button-icon"><?= ui_icon('patient') ?></span>
                                    <span>Load Patient Details</span>
                                </button>
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
                                    <button class="button tiny" type="submit">
                                        <span class="button-icon"><?= ui_icon('check') ?></span>
                                        <span>Save Vitals</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="ops-form-card injury-uploader" data-injury-root data-start-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/injury-session')) ?>">
                            <h3>Injury Session</h3>
                            <?= csrf_field() ?>
                            <label>Special note
                                <textarea name="special_note" rows="2" placeholder="Add ambulance special note"></textarea>
                            </label>
                            <div class="actions-row">
                                <button class="button primary" type="button" data-start-injury>
                                    <span class="button-icon"><?= ui_icon('activity') ?></span>
                                    <span>Start Injury Session</span>
                                </button>
                            </div>
                            <div class="hidden" data-injury-active>
                                <label>Upload injury photo
                                    <input type="file" accept="image/*" capture="environment" data-injury-file>
                                </label>
                                <div class="actions-row">
                                    <button class="button tiny" type="button" data-attach-photo>
                                        <span class="button-icon"><?= ui_icon('upload') ?></span>
                                        <span>Attach</span>
                                    </button>
                                    <button class="button tiny ghost" type="button" data-finalize-session>
                                        <span class="button-icon"><?= ui_icon('report') ?></span>
                                        <span>Finalize Report</span>
                                    </button>
                                </div>
                                <div class="notice-area" data-injury-status></div>
                            </div>
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
                <?php if ($incidents === []): ?>
                    <div class="ops-empty">No active patient-care incidents are assigned right now.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/paramedic.js')) ?>" defer></script>

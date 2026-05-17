<?php
$activeCount = count($incidents);
$sceneEtaMinutes = array_map(static fn (array $incident): int => (int) round(((int) ($incident['display_eta_seconds'] ?? $incident['scene_eta_seconds'] ?? 0)) / 60), $incidents);
$avgSceneEta = $sceneEtaMinutes !== [] ? (int) round(array_sum($sceneEtaMinutes) / count($sceneEtaMinutes)) : 0;
$pickupCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true)));
$assignedHospital = $ambulance['hospital_name'] ?? 'Unassigned';
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol teal"><?= ui_icon('ambulance') ?></span>
            <div>
                <span class="ops-eyebrow">Vehicle Operations</span>
                <h1>Ambulance Dashboard</h1>
                <p><?= e($ambulance['ambulance_number'] ?? 'No ambulance assigned') ?> | <?= e($assignedHospital) ?></p>
            </div>
        </div>
        <div class="ops-actions">
            <a class="button ghost" href="<?= e(url('/paramedic/dashboard')) ?>">
                <span class="button-icon"><?= ui_icon('stethoscope') ?></span>
                <span>Paramedic Board</span>
            </a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon blue"><?= ui_icon('navigation') ?></span>
            <p class="ops-metric-label">Active Runs</p>
            <p class="ops-metric-value"><?= e((string) $activeCount) ?></p>
            <div class="ops-metric-note">Current incidents assigned to this ambulance.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon amber"><?= ui_icon('timer') ?></span>
            <p class="ops-metric-label">Average Scene ETA</p>
            <p class="ops-metric-value"><?= e((string) $avgSceneEta) ?>m</p>
            <div class="ops-metric-note">Average arrival time across current dispatches.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon green"><?= ui_icon('user-check') ?></span>
            <p class="ops-metric-label">Picked Up</p>
            <p class="ops-metric-value"><?= e((string) $pickupCount) ?></p>
            <div class="ops-metric-note">Patients already moving toward the hospital.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon teal"><?= ui_icon('speed') ?></span>
            <p class="ops-metric-label">Status</p>
            <p class="ops-metric-value"><?= e((string) ($ambulance['status'] ?? '--')) ?></p>
            <div class="ops-metric-note">Live vehicle state synced from the dashboard.</div>
        </article>
    </section>

    <section class="ops-split">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2><span class="ops-inline-icon teal"><?= ui_icon('navigation') ?></span>Dispatch Board</h2>
                    <p class="ops-panel-subtext">Track route status, post live location, and confirm pickup once the patient is onboard.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="ops-feed-grid" id="ambulanceOpsList" data-ambulance-ops data-feed-url="<?= e(url('/api/ambulance/incidents')) ?>" data-location-url-template="<?= e(url('/ambulance/incidents/__ID__/location')) ?>" data-navigation-url-template="<?= e(url('/api/ambulance/incidents/__ID__/navigation')) ?>">
                    <?php foreach ($incidents as $incident): ?>
                        <?php
                        $isHospitalRoute = in_array(($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true);
                        $navDestinationLat = $isHospitalRoute ? ($incident['hospital_latitude'] ?? $incident['incident_latitude']) : $incident['incident_latitude'];
                        $navDestinationLng = $isHospitalRoute ? ($incident['hospital_longitude'] ?? $incident['incident_longitude']) : $incident['incident_longitude'];
                        $navTargetLabel = $incident['nav_target_label'] ?? ($isHospitalRoute ? 'Hospital' : 'Accident Scene');
                        $routeOriginLat = $incident['display_origin_latitude'] ?? (($incident['ambulance_latitude'] ?: $incident['hospital_latitude']) ?? '');
                        $routeOriginLng = $incident['display_origin_longitude'] ?? (($incident['ambulance_longitude'] ?: $incident['hospital_longitude']) ?? '');
                        ?>
                        <article class="incident-tile ops-incident-card <?= e(incident_tile_class($incident['status'])) ?>"
                            data-incident-card
                            data-incident-id="<?= e((string) $incident['incident_id']) ?>"
                            data-status="<?= e((string) ($incident['status'] ?? '')) ?>"
                            data-scene-lat="<?= e((string) $incident['incident_latitude']) ?>"
                            data-scene-lng="<?= e((string) $incident['incident_longitude']) ?>"
                            data-hospital-lat="<?= e((string) ($incident['hospital_latitude'] ?? '')) ?>"
                            data-hospital-lng="<?= e((string) ($incident['hospital_longitude'] ?? '')) ?>"
                            data-nav-dest-lat="<?= e((string) $navDestinationLat) ?>"
                            data-nav-dest-lng="<?= e((string) $navDestinationLng) ?>"
                            data-nav-target-label="<?= e($navTargetLabel) ?>"
                            data-patient-name="<?= e($incident['patient_name']) ?>"
                            data-ambulance-number="<?= e((string) ($incident['ambulance_number'] ?? '')) ?>"
                            data-route-polyline="<?= e((string) ($incident['display_route_polyline'] ?? '')) ?>"
                            data-display-distance="<?= e((string) ($incident['display_distance_meters'] ?? 0)) ?>"
                            data-display-eta="<?= e((string) ($incident['display_eta_seconds'] ?? 0)) ?>">
                            <div class="tile-head">
                                <h3><?= e($incident['patient_name']) ?></h3>
                                <span class="badge"><?= e($incident['ambulance_number']) ?></span>
                            </div>
                            <div class="ops-inline-stats">
                                <span class="ops-kicker warning"><?= e((string) ($incident['status'] ?? 'pending')) ?></span>
                                <span class="ops-kicker" data-eta-summary><?= e((string) max((int) ceil(((int) ($incident['display_eta_seconds'] ?? 0)) / 60), 0)) ?> min <?= $isHospitalRoute ? 'to hospital' : 'to scene' ?></span>
                            </div>
                            <div class="ops-incident-meta">
                                <p><strong>Identifier:</strong> <?= e($incident['nic_number'] ?? $incident['patient_phone'] ?? 'Waiting for confirmation') ?></p>
                                <p><strong>Vehicle:</strong> <?= e($incident['reported_vehicle_number'] ?? 'Not provided') ?></p>
                                <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
                                <p><strong>Hospital:</strong> <?= e($incident['hospital_name'] ?? 'Pending hospital') ?></p>
                                <p><strong><?= $isHospitalRoute ? 'Hospital ETA' : 'Scene ETA' ?>:</strong> <span data-eta-seconds="<?= e((string) ($incident['display_eta_seconds'] ?? 0)) ?>" data-eta-live="1"></span></p>
                            </div>

                            <div class="ops-form-card">
                                <h3>Route Guidance</h3>
                                <div class="map-box mini" data-map-card data-api-key="<?= e($mapsApiKey) ?>" data-origin-lat="<?= e((string) $routeOriginLat) ?>" data-origin-lng="<?= e((string) $routeOriginLng) ?>" data-dest-lat="<?= e((string) $navDestinationLat) ?>" data-dest-lng="<?= e((string) $navDestinationLng) ?>" data-route-polyline="<?= e((string) ($incident['display_route_polyline'] ?? '')) ?>"></div>
                                <div class="ops-inline-actions">
                                    <button class="button primary" type="button" data-start-nav-button>
                                        <span class="button-icon"><?= ui_icon('navigation') ?></span>
                                        <span>Start Navigation</span>
                                    </button>
                                    <?php if ($isHospitalRoute): ?>
                                        <button class="button success" type="button" data-arrive-hospital-button data-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/arrive-hospital')) ?>">
                                            <span class="button-icon"><?= ui_icon('hospital') ?></span>
                                            <span>Arrived at Hospital</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="button warning" type="button" data-pickup-button data-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/pickup')) ?>">
                                            <span class="button-icon"><?= ui_icon('user-check') ?></span>
                                            <span>Pickup Patient</span>
                                        </button>
                                    <?php endif; ?>
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
                        <div class="ops-empty">No active dispatches are assigned to this ambulance right now.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="ops-stack">
            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div>
                        <h2><span class="ops-inline-icon sky"><?= ui_icon('map') ?></span>Master Map</h2>
                        <p class="ops-panel-subtext">Live navigation board for the ambulance crew.</p>
                    </div>
                </div>
                <div class="ops-map-wrap">
                    <div class="ambulance-nav-shell">
                        <div class="ambulance-nav-banner" id="ambulanceNavBanner" hidden>
                            <div class="ambulance-nav-turn">
                                <span class="ambulance-nav-arrow" data-nav-arrow>&#8599;</span>
                                <div>
                                    <div class="ambulance-nav-distance" data-nav-step-distance>--</div>
                                    <div class="ambulance-nav-instruction" data-nav-step>Choose a dispatch and press Start Navigation.</div>
                                </div>
                            </div>
                            <button class="button ghost tiny" type="button" id="ambulanceNavExitTop">Exit</button>
                        </div>
                        <div id="ambulanceMasterMap" class="map-box ambulance-master-map" data-api-key="<?= e($mapsApiKey) ?>"></div>
                        <div class="ambulance-nav-summary" id="ambulanceNavSummary" hidden>
                            <div class="ambulance-nav-summary-main">
                                <strong data-nav-duration>--</strong>
                                <span data-nav-distance-total>--</span>
                            </div>
                            <div class="ambulance-nav-summary-side">
                                <strong data-nav-arrival>--</strong>
                                <span data-nav-target>To scene</span>
                            </div>
                            <button class="button danger tiny" type="button" id="ambulanceNavExitBottom">Exit</button>
                        </div>
                    </div>
                    <div class="ops-note" id="ambulanceNavNote">Choose a dispatch and press Start Navigation to open a live route from the current ambulance position.</div>
                </div>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/ambulance-ops.js')) ?>" defer></script>

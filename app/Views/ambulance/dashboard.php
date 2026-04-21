<section class="dashboard-header">
    <div>
        <h1>Ambulance Dashboard</h1>
        <p class="muted"><?= e($ambulance['ambulance_number'] ?? 'No ambulance assigned') ?><?= !empty($ambulance['hospital_name']) ? ' | ' . e($ambulance['hospital_name']) : '' ?></p>
    </div>
</section>

<section class="two-col">
    <div class="panel">
        <h2>Active Dispatches</h2>
        <div class="card-list" id="ambulanceIncidentList" data-ambulance-dashboard data-feed-url="<?= e(url('/api/ambulance/incidents')) ?>" data-location-url-template="<?= e(url('/ambulance/incidents/__ID__/location')) ?>">
            <?php foreach ($incidents as $incident): ?>
                <article class="incident-tile <?= e(incident_tile_class($incident['status'])) ?>" data-incident-card data-incident-id="<?= e((string) $incident['incident_id']) ?>">
                    <div class="tile-head">
                        <h3><?= e($incident['patient_name']) ?></h3>
                        <span class="badge"><?= e($incident['ambulance_number']) ?></span>
                    </div>
                    <p><strong>Blood:</strong> <?= e($incident['blood_group'] ?? 'Unknown') ?></p>
                    <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None') ?></p>
                    <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
                    <p><strong>Hospital ETA:</strong> <span data-eta-seconds="<?= e((string) ($incident['hospital_eta_seconds'] ?? $incident['scene_eta_seconds'] ?? 0)) ?>"></span></p>

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

                    <div class="map-box mini" data-map-card data-api-key="<?= e($mapsApiKey) ?>" data-origin-lat="<?= e((string) ($incident['ambulance_latitude'] ?? '')) ?>" data-origin-lng="<?= e((string) ($incident['ambulance_longitude'] ?? '')) ?>" data-dest-lat="<?= e((string) $incident['incident_latitude']) ?>" data-dest-lng="<?= e((string) $incident['incident_longitude']) ?>"></div>

                    <div class="actions-row">
                        <button class="button warning" type="button" data-pickup-button data-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/pickup')) ?>">Pickup Patient</button>
                    </div>

                    <div class="injury-uploader" data-injury-root data-start-url="<?= e(url('/ambulance/incidents/' . $incident['incident_id'] . '/injury-session')) ?>">
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
        </div>
    </div>
    <div class="panel">
        <h2>Route Guidance</h2>
        <p class="muted">Live navigation stays inside the HealthFirst dashboard. Location updates are posted to the server automatically.</p>
        <div id="ambulanceMasterMap" class="map-box" data-api-key="<?= e($mapsApiKey) ?>"></div>
    </div>
</section>
<script src="<?= e(asset('js/modules/ambulance.js')) ?>" defer></script>


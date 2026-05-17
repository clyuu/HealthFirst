<section class="public-emergency" data-emergency-root data-submit-url="<?= e(url('/api/emergency/report')) ?>">
    <div class="public-card">
        <span class="eyebrow danger">Public Emergency Access</span>
        <h1><?= $publicToken ? 'HealthFirst Emergency QR' : 'HealthFirst Emergency Report' ?></h1>
        <p>This page does not reveal the patient's private medical history. Use it only to alert emergency services after a real accident.</p>
        <button class="button danger large" type="button" data-start-emergency>Inform Emergency</button>
    </div>

    <div class="public-card hidden" data-emergency-form>
        <h2>Scene Capture</h2>
        <p class="muted">Take a clear accident photo, allow location access, and submit the report. If you do not have the QR, enter any patient detail you know.</p>
        <form id="emergencyIncidentForm" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($publicToken): ?>
                <input type="hidden" name="public_token" value="<?= e($publicToken) ?>">
            <?php endif; ?>
            <input type="hidden" name="incident_latitude">
            <input type="hidden" name="incident_longitude">
            <fieldset class="public-identity-block">
                <legend>Patient identification if QR is unavailable</legend>
                <label>Patient name
                    <input type="text" name="patient_name" placeholder="Name if known">
                </label>
                <label>Patient NIC
                    <input type="text" name="patient_nic" placeholder="NIC number if known">
                </label>
                <label>Patient phone
                    <input type="text" name="patient_phone" placeholder="Phone number if known">
                </label>
                <label>Vehicle number
                    <input type="text" name="vehicle_number" placeholder="Vehicle number if known">
                </label>
            </fieldset>
            <label>Estimated injured count
                <input type="number" min="1" max="20" name="injured_count" value="1" required>
            </label>
            <label>Brief note
                <textarea name="public_message" rows="3" placeholder="Describe what is happening at the scene."></textarea>
            </label>
            <label>Accident scene photo
                <input type="file" name="scene_photo" accept="image/*" capture="environment" required>
            </label>
            <div class="actions-row">
                <button class="button primary" type="submit">Submit Emergency Report</button>
            </div>
        </form>
        <div class="public-location-fallback hidden" data-location-fallback>
            <h3>Select accident location on the map</h3>
            <p class="muted">If browser GPS is blocked, tap the accident spot on the map below to continue.</p>
            <div class="public-location-map" data-location-map></div>
            <div class="public-location-selected muted" data-location-selected>No map location selected yet.</div>
        </div>
        <div class="notice-area" data-emergency-status></div>
        <?php if ($publicToken): ?>
            <p class="muted">No QR for the victim? <a href="<?= e(url('/emergency/report')) ?>">Open the manual emergency report form</a>.</p>
        <?php endif; ?>
    </div>
</section>
<script src="<?= e(asset('js/modules/emergency.js')) ?>" defer></script>

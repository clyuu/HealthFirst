<section class="public-emergency" data-emergency-root data-submit-url="<?= e(url('/api/emergency/report')) ?>">
    <div class="public-card">
        <span class="eyebrow danger">Public Emergency Access</span>
        <h1>HealthFirst Emergency QR</h1>
        <p>This page does not reveal the patient's private medical history. Use it only to alert emergency services after a real accident.</p>
        <button class="button danger large" type="button" data-start-emergency>Inform Emergency</button>
    </div>

    <div class="public-card hidden" data-emergency-form>
        <h2>Scene Capture</h2>
        <p class="muted">Take a clear accident photo, allow location access, and submit the report.</p>
        <form id="emergencyIncidentForm" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="public_token" value="<?= e($publicToken) ?>">
            <input type="hidden" name="incident_latitude">
            <input type="hidden" name="incident_longitude">
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
        <div class="notice-area" data-emergency-status></div>
    </div>
</section>
<script src="<?= e(asset('js/modules/emergency.js')) ?>" defer></script>


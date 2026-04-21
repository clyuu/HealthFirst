<section class="dashboard-header">
    <div>
        <h1>Doctor Dashboard</h1>
        <p class="muted"><?= e($hospital['hospital_name'] ?? '') ?></p>
    </div>
    <div class="header-actions">
        <a class="button primary" href="<?= e(url('/doctor/patients')) ?>">My Patients</a>
    </div>
</section>

<section class="tile-grid">
    <?php foreach ($incidents as $incident): ?>
        <article class="incident-tile tile-green">
            <div class="tile-head">
                <h3><?= e($incident['patient_name']) ?></h3>
                <span class="badge"><?= e($incident['overall_severity'] ?? 'Pending') ?></span>
            </div>
            <p><strong>Blood:</strong> <?= e($incident['blood_group'] ?? 'Unknown') ?></p>
            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None') ?></p>
            <p><strong>ETA:</strong> <span data-eta-seconds="<?= e((string) ($incident['hospital_eta_seconds'] ?? 0)) ?>"></span></p>
            <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
            <?php if (!empty($incident['report_file_path'])): ?>
                <p><a class="button tiny" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">View Injury Report</a></p>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/doctor/incidents/' . $incident['incident_id'] . '/admit')) ?>" class="inline-form admit-form">
                <?= csrf_field() ?>
                <button class="button success" type="submit">Admit</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<script src="<?= e(asset('js/modules/doctor.js')) ?>" defer></script>


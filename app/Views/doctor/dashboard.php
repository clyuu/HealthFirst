<?php
$incomingCount = count($incidents);
$reportCount = count(array_filter($incidents, static fn (array $incident): bool => !empty($incident['report_file_path'])));
$severeCount = count(array_filter($incidents, static fn (array $incident): bool => in_array(($incident['overall_severity'] ?? ''), ['Critical', 'Severe'], true)));
$etaMinutes = array_map(static fn (array $incident): int => (int) round(((int) ($incident['display_eta_seconds'] ?? $incident['hospital_eta_seconds'] ?? 0)) / 60), $incidents);
$avgEta = $etaMinutes !== [] ? (int) round(array_sum($etaMinutes) / count($etaMinutes)) : 0;
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol green"><?= ui_icon('doctor') ?></span>
            <div>
                <span class="ops-eyebrow">Clinical Intake</span>
                <h1>Doctor Dashboard</h1>
                <p><?= e($hospital['hospital_name'] ?? 'Hospital not assigned') ?></p>
            </div>
        </div>
        <div class="ops-actions">
            <a class="button primary" href="<?= e(url('/doctor/patients')) ?>">
                <span class="button-icon"><?= ui_icon('patient') ?></span>
                <span>My Patients</span>
            </a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon blue"><?= ui_icon('patient') ?></span>
            <p class="ops-metric-label">Incoming Cases</p>
            <p class="ops-metric-value"><?= e((string) $incomingCount) ?></p>
            <div class="ops-metric-note">Patients awaiting admission decisions.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon cyan"><?= ui_icon('report') ?></span>
            <p class="ops-metric-label">AI Reports</p>
            <p class="ops-metric-value"><?= e((string) $reportCount) ?></p>
            <div class="ops-metric-note">Cases with a generated injury report ready to open.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon red"><?= ui_icon('activity') ?></span>
            <p class="ops-metric-label">High Severity</p>
            <p class="ops-metric-value"><?= e((string) $severeCount) ?></p>
            <div class="ops-metric-note">Critical or severe incoming incidents.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon amber"><?= ui_icon('timer') ?></span>
            <p class="ops-metric-label">Average ETA</p>
            <p class="ops-metric-value"><?= e((string) $avgEta) ?>m</p>
            <div class="ops-metric-note">Mean arrival time of current dashboard patients.</div>
        </article>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2><span class="ops-inline-icon green"><?= ui_icon('stethoscope') ?></span>Incoming Patients</h2>
                <p class="ops-panel-subtext">Review AI assessment details, estimated arrival, and admit with one action.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-feed-grid">
                <?php foreach ($incidents as $incident): ?>
                    <?php
                    $hospitalEtaRemaining = (int) ($incident['display_eta_seconds'] ?? 0);
                    ?>
                    <article class="incident-tile ops-incident-card tile-green">
                        <div class="tile-head">
                            <h3><?= e($incident['patient_name']) ?></h3>
                            <span class="badge"><?= e($incident['overall_severity'] ?? 'Pending') ?></span>
                        </div>
                        <div class="ops-inline-stats">
                            <span class="ops-kicker"><?= e((string) ($incident['blood_group'] ?? 'Unknown')) ?> Blood</span>
                            <span class="ops-kicker <?= in_array(($incident['overall_severity'] ?? ''), ['Critical', 'Severe'], true) ? 'danger' : 'success' ?>">
                                <?= e($incident['overall_severity'] ?? 'Pending') ?>
                            </span>
                        </div>
                        <div class="ops-incident-meta">
                            <p><strong>Allergies:</strong> <?= e($incident['allergies'] ?? 'None') ?></p>
                            <p><strong>ETA:</strong> <span data-eta-seconds="<?= e((string) $hospitalEtaRemaining) ?>" data-eta-live="<?= !empty($incident['eta_live']) ? '1' : '0' ?>"></span></p>
                            <p><strong>Scene:</strong> <?= e($incident['incident_latitude']) ?>, <?= e($incident['incident_longitude']) ?></p>
                        </div>
                        <div class="ops-inline-actions">
                            <?php if (!empty($incident['report_file_path'])): ?>
                                <a class="button tiny ghost" href="<?= e(url('/reports/injury/' . $incident['incident_id'])) ?>" target="_blank">
                                    <span class="button-icon"><?= ui_icon('report') ?></span>
                                    <span>View Injury Report</span>
                                </a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/doctor/incidents/' . $incident['incident_id'] . '/admit')) ?>" class="inline-form admit-form">
                                <?= csrf_field() ?>
                                <button class="button success" type="submit">
                                    <span class="button-icon"><?= ui_icon('check') ?></span>
                                    <span>Admit</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($incidents === []): ?>
                    <div class="ops-empty">No incoming patients are waiting for admission right now.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>
<script src="<?= e(asset('js/modules/doctor.js')) ?>" defer></script>

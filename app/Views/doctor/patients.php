<section class="dashboard-header">
    <div>
        <h1>My Patients</h1>
        <p class="muted">Only admitted patients assigned to you can expose full medical history documents.</p>
    </div>
</section>

<section class="card-list">
    <?php foreach ($patients as $patient): ?>
        <article class="panel">
            <h2><?= e($patient['patient_name']) ?></h2>
            <p><strong>Blood:</strong> <?= e($patient['blood_group'] ?? 'Unknown') ?> | <strong>Allergies:</strong> <?= e($patient['allergies'] ?? 'None') ?></p>
            <p><strong>History:</strong> <?= e($patient['chronic_conditions'] ?? 'None recorded') ?></p>
            <p><strong>Medical notes:</strong> <?= e($patient['medical_notes'] ?? 'None') ?></p>
            <?php if (!empty($patient['report_file_path'])): ?>
                <p><a class="button tiny" href="<?= e(url('/reports/injury/' . $patient['incident_id'])) ?>" target="_blank">Open AI Injury Report</a></p>
            <?php endif; ?>

            <h3>Medical Documents</h3>
            <div class="doc-grid">
                <?php $patientDocuments = $documents[(int) $patient['user_id']] ?? []; ?>
                <?php foreach ($patientDocuments as $document): ?>
                    <a class="doc-chip" href="<?= e(url('/documents/' . $document['document_id'] . '/download?inline=1')) ?>" target="_blank"><?= e($document['title']) ?></a>
                <?php endforeach; ?>
                <?php if ($patientDocuments === []): ?>
                    <p class="muted">No patient-uploaded documents available.</p>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(url('/doctor/cases/' . $patient['case_assignment_id'] . '/discharge')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button class="button warning" type="submit">Discharge</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>

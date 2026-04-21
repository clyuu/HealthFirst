<section class="dashboard-header">
    <div>
        <h1>System Administration</h1>
        <p class="muted">Global hospital onboarding and high-level usage metrics.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><h3>Hospitals</h3><p><?= e((string) count($hospitals)) ?></p></article>
    <article class="stat-card"><h3>Patients</h3><p><?= e((string) $patientCount) ?></p></article>
    <article class="stat-card"><h3>Doctors</h3><p><?= e((string) $doctorCount) ?></p></article>
    <article class="stat-card"><h3>Paramedics</h3><p><?= e((string) $paramedicCount) ?></p></article>
</section>

<section class="two-col">
    <div class="panel">
        <h2>Add Hospital</h2>
        <form method="post" action="<?= e(url('/admin/system/hospitals')) ?>" class="grid-form compact">
            <?= csrf_field() ?>
            <label>Hospital name
                <input type="text" name="hospital_name" required>
            </label>
            <label>Contact number
                <input type="text" name="contact_number" required>
            </label>
            <label class="span-2">Address
                <textarea name="address" rows="2" required></textarea>
            </label>
            <label>Latitude
                <input type="text" name="latitude" required>
            </label>
            <label>Longitude
                <input type="text" name="longitude" required>
            </label>
            <label>Primary admin name
                <input type="text" name="admin_name">
            </label>
            <label>Primary admin NIC
                <input type="text" name="admin_nic">
            </label>
            <label>Primary admin email
                <input type="email" name="admin_email">
            </label>
            <label>Primary admin phone
                <input type="text" name="admin_phone">
            </label>
            <label>Primary admin password
                <input type="text" name="admin_password">
            </label>
            <div class="span-2">
                <button class="button primary" type="submit">Create Hospital</button>
            </div>
        </form>
    </div>
    <div class="panel">
        <h2>Registered Hospitals</h2>
        <div class="card-list">
            <?php foreach ($hospitals as $hospital): ?>
                <article class="doc-card">
                    <strong><?= e($hospital['hospital_name']) ?></strong>
                    <span><?= e($hospital['contact_number']) ?></span>
                    <span class="muted"><?= e($hospital['address']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="ops-dashboard">
    <section class="ops-hero">
        <div>
            <span class="ops-eyebrow">Platform Control</span>
            <h1>System Administration</h1>
            <p>Global hospital onboarding and high-level usage metrics across HealthFirst.</p>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <p class="ops-metric-label">Hospitals</p>
            <p class="ops-metric-value"><?= e((string) count($hospitals)) ?></p>
            <div class="ops-metric-note">Registered institutions currently onboarded to the network.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Patients</p>
            <p class="ops-metric-value"><?= e((string) $patientCount) ?></p>
            <div class="ops-metric-note">Patient accounts stored in the HealthFirst platform.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Doctors</p>
            <p class="ops-metric-value"><?= e((string) $doctorCount) ?></p>
            <div class="ops-metric-note">Clinical users available across connected hospitals.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Paramedics</p>
            <p class="ops-metric-value"><?= e((string) $paramedicCount) ?></p>
            <div class="ops-metric-note">Field emergency responders currently registered.</div>
        </article>
    </section>

    <section class="ops-split">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Add Hospital</h2>
                    <p class="ops-panel-subtext">Provision a new hospital and optionally create its primary admin in one step.</p>
                </div>
            </div>
            <div class="ops-panel-body">
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
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Registered Hospitals</h2>
                    <p class="ops-panel-subtext">Every hospital already available on the system-wide emergency network.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="ops-list">
                    <?php foreach ($hospitals as $hospital): ?>
                        <article class="ops-mini-card">
                            <strong><?= e($hospital['hospital_name']) ?></strong>
                            <span><?= e($hospital['contact_number']) ?></span>
                            <p class="muted"><?= e($hospital['address']) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($hospitals === []): ?>
                        <div class="ops-empty">No hospitals have been registered yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</section>

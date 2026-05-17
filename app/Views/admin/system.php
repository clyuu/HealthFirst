<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol violet"><?= ui_icon('shield') ?></span>
            <div>
                <span class="ops-eyebrow">Platform Control</span>
                <h1>System Administration</h1>
                <p>Global hospital onboarding and high-level usage metrics across HealthFirst.</p>
            </div>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon teal"><?= ui_icon('building') ?></span>
            <p class="ops-metric-label">Hospitals</p>
            <p class="ops-metric-value"><?= e((string) count($hospitals)) ?></p>
            <div class="ops-metric-note">Registered institutions currently onboarded to the network.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon blue"><?= ui_icon('patient') ?></span>
            <p class="ops-metric-label">Patients</p>
            <p class="ops-metric-value"><?= e((string) $patientCount) ?></p>
            <div class="ops-metric-note">Patient accounts stored in the HealthFirst platform.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon green"><?= ui_icon('doctor') ?></span>
            <p class="ops-metric-label">Doctors</p>
            <p class="ops-metric-value"><?= e((string) $doctorCount) ?></p>
            <div class="ops-metric-note">Clinical users available across connected hospitals.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon coral"><?= ui_icon('stethoscope') ?></span>
            <p class="ops-metric-label">Paramedics</p>
            <p class="ops-metric-value"><?= e((string) $paramedicCount) ?></p>
            <div class="ops-metric-note">Field emergency responders currently registered.</div>
        </article>
    </section>

    <section class="ops-split system-admin-actions">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2><span class="ops-inline-icon sky"><?= ui_icon('plus') ?></span>Hospital Setup</h2>
                    <p class="ops-panel-subtext">Create the hospital first, then register the admin login for that hospital.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="system-action-grid">
                    <article class="system-action-card">
                        <span class="ops-stat-icon teal"><?= ui_icon('building') ?></span>
                        <h3>Add Hospital</h3>
                        <p>Register a new hospital in the HealthFirst emergency network.</p>
                        <button class="button primary wide" type="button" data-modal-open="addHospitalModal">
                            <span class="button-icon"><?= ui_icon('plus') ?></span>
                            <span>Add Hospital</span>
                        </button>
                    </article>
                    <article class="system-action-card">
                        <span class="ops-stat-icon violet"><?= ui_icon('shield') ?></span>
                        <h3>Create Hospital Admin</h3>
                        <p>Create the login that lets a hospital admin manage staff, dashboards, and ambulances.</p>
                        <button class="button secondary wide" type="button" data-modal-open="createHospitalAdminModal">
                            <span class="button-icon"><?= ui_icon('user-check') ?></span>
                            <span>Create Hospital Admin</span>
                        </button>
                    </article>
                </div>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2><span class="ops-inline-icon teal"><?= ui_icon('hospital') ?></span>Registered Hospitals</h2>
                    <p class="ops-panel-subtext">Every hospital already available on the system-wide emergency network.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="system-hospital-preview">
                    <span class="ops-stat-icon teal"><?= ui_icon('hospital') ?></span>
                    <strong><?= e((string) count($hospitals)) ?> hospitals registered</strong>
                    <p class="muted">Open the full hospital board to view every hospital as large cards.</p>
                    <button class="button ghost wide" type="button" data-modal-open="registeredHospitalsModal">
                        <span class="button-icon"><?= ui_icon('arrow') ?></span>
                        <span>View</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</section>

<div class="app-modal" id="addHospitalModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="addHospitalTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="addHospitalTitle"><span class="ops-inline-icon teal"><?= ui_icon('building') ?></span>Add Hospital</h2>
                <p class="ops-panel-subtext">Enter the hospital details first. Admin login can be created after this step.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <form method="post" action="<?= e(url('/admin/system/hospitals')) ?>" class="grid-form compact app-modal-body">
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
            <div class="span-2 actions-row">
                <button class="button primary" type="submit">
                    <span class="button-icon"><?= ui_icon('building') ?></span>
                    <span>Create Hospital</span>
                </button>
                <button class="button ghost" type="button" data-modal-close>Cancel</button>
            </div>
        </form>
    </section>
</div>

<div class="app-modal" id="createHospitalAdminModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="createHospitalAdminTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="createHospitalAdminTitle"><span class="ops-inline-icon violet"><?= ui_icon('shield') ?></span>Create Hospital Admin</h2>
                <p class="ops-panel-subtext">This login opens the assigned hospital admin dashboard for staff and ambulance setup.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <form method="post" action="<?= e(url('/admin/system/hospital-admins')) ?>" class="grid-form compact app-modal-body">
            <?= csrf_field() ?>
            <label>Hospital
                <select name="hospital_id" required>
                    <option value="">Select hospital</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= e((string) $hospital['hospital_id']) ?>"><?= e($hospital['hospital_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Designation
                <input type="text" name="designation" value="Primary Hospital Administrator" required>
            </label>
            <label>Full name
                <input type="text" name="full_name" required>
            </label>
            <label>NIC
                <input type="text" name="nic_number" maxlength="12" data-validate="nic" required>
            </label>
            <label>Login email / username
                <input type="email" name="email" data-validate="email" required>
            </label>
            <label>Password
                <input type="text" name="password" data-validate="password" required>
            </label>
            <label>Phone
                <input type="tel" name="phone" maxlength="10" inputmode="numeric" data-validate="phone" required>
            </label>
            <label>Gender
                <select name="gender">
                    <option value="">Not provided</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </label>
            <label>Date of birth
                <input type="date" name="date_of_birth">
            </label>
            <label class="span-2">Address
                <textarea name="address" rows="2"></textarea>
            </label>
            <div class="span-2 actions-row">
                <button class="button secondary" type="submit">
                    <span class="button-icon"><?= ui_icon('user-check') ?></span>
                    <span>Register Admin Login</span>
                </button>
                <button class="button ghost" type="button" data-modal-close>Cancel</button>
            </div>
        </form>
    </section>
</div>

<div class="app-modal app-modal-wide" id="registeredHospitalsModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="registeredHospitalsTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="registeredHospitalsTitle"><span class="ops-inline-icon teal"><?= ui_icon('hospital') ?></span>Registered Hospitals</h2>
                <p class="ops-panel-subtext">Large card view of every hospital available in the HealthFirst network.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="system-hospital-grid app-modal-body">
            <?php foreach ($hospitals as $hospital): ?>
                <article class="system-hospital-card">
                    <span class="ops-stat-icon teal"><?= ui_icon('hospital') ?></span>
                    <h3><?= e($hospital['hospital_name']) ?></h3>
                    <p><?= e($hospital['address']) ?></p>
                    <div class="system-hospital-meta">
                        <span><?= e($hospital['contact_number']) ?></span>
                        <span><?= e((string) $hospital['latitude']) ?>, <?= e((string) $hospital['longitude']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($hospitals === []): ?>
                <div class="ops-empty">No hospitals have been registered yet.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

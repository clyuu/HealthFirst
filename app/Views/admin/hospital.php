<?php
$doctorCount = count(array_filter($staff, static fn (array $member): bool => ($member['role_slug'] ?? '') === 'doctor'));
$paramedicCount = count(array_filter($staff, static fn (array $member): bool => ($member['role_slug'] ?? '') === 'paramedic'));
$availableFleet = count(array_filter($ambulances, static fn (array $ambulance): bool => ($ambulance['status'] ?? '') === 'available'));
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div>
            <span class="ops-eyebrow">Hospital Operations</span>
            <h1>Hospital Administration</h1>
            <p><?= e($hospital['hospital_name'] ?? 'Hospital not assigned') ?></p>
        </div>
        <div class="ops-actions">
            <a class="button ghost" href="<?= e(url('/hospital/dashboard')) ?>">Live Emergency Board</a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <p class="ops-metric-label">Total Staff</p>
            <p class="ops-metric-value"><?= e((string) count($staff)) ?></p>
            <div class="ops-metric-note">Doctors, paramedics, and admins currently onboarded.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Doctors</p>
            <p class="ops-metric-value"><?= e((string) $doctorCount) ?></p>
            <div class="ops-metric-note">Emergency and admitting doctors under this hospital.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Paramedics</p>
            <p class="ops-metric-value"><?= e((string) $paramedicCount) ?></p>
            <div class="ops-metric-note">Field response staff available for ambulance assignment.</div>
        </article>
        <article class="ops-metric-card">
            <p class="ops-metric-label">Fleet Ready</p>
            <p class="ops-metric-value"><?= e((string) $availableFleet) ?></p>
            <div class="ops-metric-note">Ambulances currently marked available.</div>
        </article>
    </section>

    <section class="ops-three-col">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Create Staff</h2>
                    <p class="ops-panel-subtext">Onboard doctors, paramedics, and admins with one form.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <form method="post" action="<?= e(url('/admin/hospital/staff')) ?>" class="stack-form">
                    <?= csrf_field() ?>
                    <label>Role
                        <select name="role_slug" required>
                            <option value="doctor">Doctor</option>
                            <option value="paramedic">Paramedic</option>
                            <option value="hospital_admin">Hospital Admin</option>
                        </select>
                    </label>
                    <label>Full name
                        <input type="text" name="full_name" required>
                    </label>
                    <label>NIC
                        <input type="text" name="nic_number" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                    <label>Phone
                        <input type="text" name="phone" required>
                    </label>
                    <label>Designation
                        <input type="text" name="designation" required>
                    </label>
                    <label>Password
                        <input type="text" name="password" required>
                    </label>
                    <label>Assign ambulance (for paramedics)
                        <select name="ambulance_id">
                            <option value="">Not now</option>
                            <?php foreach ($ambulances as $ambulance): ?>
                                <option value="<?= e((string) $ambulance['ambulance_id']) ?>"><?= e($ambulance['ambulance_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button primary" type="submit">Create Staff Account</button>
                </form>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Create Ambulance</h2>
                    <p class="ops-panel-subtext">Expand the ambulance fleet and connect paramedics faster.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <form method="post" action="<?= e(url('/admin/hospital/ambulances')) ?>" class="stack-form">
                    <?= csrf_field() ?>
                    <label>Ambulance number
                        <input type="text" name="ambulance_number" required>
                    </label>
                    <label>Capacity stretchers
                        <input type="number" min="1" name="capacity_stretchers" value="1">
                    </label>
                    <label>Status
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </label>
                    <label>Assign paramedic
                        <select name="paramedic_user_id">
                            <option value="">Not now</option>
                            <?php foreach ($staff as $member): ?>
                                <?php if ($member['role_slug'] === 'paramedic'): ?>
                                    <option value="<?= e((string) $member['user_id']) ?>"><?= e($member['full_name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button secondary" type="submit">Add Ambulance</button>
                </form>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2>Current Staff</h2>
                    <p class="ops-panel-subtext">Quick view of everyone operating under this hospital.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="ops-list">
                    <?php foreach ($staff as $member): ?>
                        <article class="ops-mini-card">
                            <strong><?= e($member['full_name']) ?></strong>
                            <span><?= e($member['role_name']) ?> | <?= e($member['designation']) ?></span>
                            <p class="muted"><?= e($member['email']) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($staff === []): ?>
                        <div class="ops-empty">No staff members are registered for this hospital yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2>Ambulance Fleet</h2>
                <p class="ops-panel-subtext">Status overview of every ambulance currently linked to this hospital.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ops-list">
                <?php foreach ($ambulances as $ambulance): ?>
                    <article class="ops-mini-card">
                        <strong><?= e($ambulance['ambulance_number']) ?></strong>
                        <span><?= e($ambulance['status']) ?><?= !empty($ambulance['assigned_paramedic']) ? ' | ' . e($ambulance['assigned_paramedic']) : '' ?></span>
                    </article>
                <?php endforeach; ?>
                <?php if ($ambulances === []): ?>
                    <div class="ops-empty">No ambulances are registered for this hospital yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>

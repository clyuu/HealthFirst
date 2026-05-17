<?php
$doctorCount = count(array_filter($staff, static fn (array $member): bool => ($member['role_slug'] ?? '') === 'doctor'));
$hospitalDeskCount = count(array_filter($staff, static fn (array $member): bool => ($member['role_slug'] ?? '') === 'hospital_staff'));
$paramedicCount = count(array_filter($staff, static fn (array $member): bool => ($member['role_slug'] ?? '') === 'paramedic'));
$availableFleet = count(array_filter($ambulances, static fn (array $ambulance): bool => ($ambulance['status'] ?? '') === 'available'));
?>
<section class="ops-dashboard">
    <section class="ops-hero">
        <div class="ops-hero-main">
            <span class="ops-hero-symbol teal"><?= ui_icon('shield') ?></span>
            <div>
                <span class="ops-eyebrow">Hospital Operations</span>
                <h1>Hospital Administration</h1>
                <p><?= e($hospital['hospital_name'] ?? 'Hospital not assigned') ?></p>
            </div>
        </div>
        <div class="ops-actions">
            <a class="button ghost" href="<?= e(url('/hospital/dashboard')) ?>">
                <span class="button-icon"><?= ui_icon('activity') ?></span>
                <span>Live Emergency Board</span>
            </a>
        </div>
    </section>

    <section class="ops-metrics-grid">
        <article class="ops-metric-card">
            <span class="ops-stat-icon blue"><?= ui_icon('staff') ?></span>
            <p class="ops-metric-label">Total Staff</p>
            <p class="ops-metric-value"><?= e((string) count($staff)) ?></p>
            <div class="ops-metric-note">Doctors, paramedics, and admins currently onboarded.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon green"><?= ui_icon('doctor') ?></span>
            <p class="ops-metric-label">Doctors</p>
            <p class="ops-metric-value"><?= e((string) $doctorCount) ?></p>
            <div class="ops-metric-note">Emergency and admitting doctors under this hospital.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon cyan"><?= ui_icon('clipboard') ?></span>
            <p class="ops-metric-label">Hospital Desk</p>
            <p class="ops-metric-value"><?= e((string) $hospitalDeskCount) ?></p>
            <div class="ops-metric-note">Front-desk and live-board staff handling incoming emergencies.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon coral"><?= ui_icon('stethoscope') ?></span>
            <p class="ops-metric-label">Paramedics</p>
            <p class="ops-metric-value"><?= e((string) $paramedicCount) ?></p>
            <div class="ops-metric-note">Field response staff available for ambulance assignment.</div>
        </article>
        <article class="ops-metric-card">
            <span class="ops-stat-icon teal"><?= ui_icon('ambulance') ?></span>
            <p class="ops-metric-label">Fleet Ready</p>
            <p class="ops-metric-value"><?= e((string) $availableFleet) ?></p>
            <div class="ops-metric-note">Ambulances currently marked available.</div>
        </article>
    </section>

    <section class="ops-split hospital-admin-actions">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2><span class="ops-inline-icon sky"><?= ui_icon('plus') ?></span>Hospital Setup</h2>
                    <p class="ops-panel-subtext">Create staff logins and expand the ambulance fleet from focused popup forms.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="system-action-grid">
                    <article class="system-action-card">
                        <span class="ops-stat-icon blue"><?= ui_icon('staff') ?></span>
                        <h3>Create Staff</h3>
                        <p>Create dashboard logins for hospital desk staff, doctors, paramedics, and hospital admins.</p>
                        <button class="button primary wide" type="button" data-modal-open="createStaffModal">
                            <span class="button-icon"><?= ui_icon('user-check') ?></span>
                            <span>Create Staff</span>
                        </button>
                    </article>
                    <article class="system-action-card">
                        <span class="ops-stat-icon teal"><?= ui_icon('ambulance') ?></span>
                        <h3>Create Ambulance</h3>
                        <p>Add a vehicle to this hospital fleet and optionally assign a paramedic immediately.</p>
                        <button class="button secondary wide" type="button" data-modal-open="createAmbulanceModal">
                            <span class="button-icon"><?= ui_icon('plus') ?></span>
                            <span>Create Ambulance</span>
                        </button>
                    </article>
                </div>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div>
                    <h2><span class="ops-inline-icon violet"><?= ui_icon('users') ?></span>Current Staff</h2>
                    <p class="ops-panel-subtext">Quick view of everyone operating under this hospital.</p>
                </div>
            </div>
            <div class="ops-panel-body">
                <div class="system-hospital-preview">
                    <span class="ops-stat-icon violet"><?= ui_icon('users') ?></span>
                    <strong><?= e((string) count($staff)) ?> staff accounts</strong>
                    <p class="muted">Open the staff board to view everyone registered under this hospital.</p>
                    <button class="button ghost wide" type="button" data-modal-open="currentStaffModal">
                        <span class="button-icon"><?= ui_icon('arrow') ?></span>
                        <span>View</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h2><span class="ops-inline-icon teal"><?= ui_icon('truck') ?></span>Ambulance Fleet</h2>
                <p class="ops-panel-subtext">Status overview of every ambulance currently linked to this hospital.</p>
            </div>
        </div>
        <div class="ops-panel-body">
            <div class="ambulance-fleet-grid">
                <?php foreach ($ambulances as $ambulance): ?>
                    <?php $statusClass = strtolower(str_replace('_', '-', (string) ($ambulance['status'] ?? 'unknown'))); ?>
                    <article class="ambulance-fleet-card status-<?= e($statusClass) ?>">
                        <div class="ambulance-fleet-top">
                            <span class="ops-stat-icon teal"><?= ui_icon('ambulance') ?></span>
                            <span class="status-badge fleet-status"><?= e($ambulance['status'] ?? 'unknown') ?></span>
                        </div>
                        <h3><?= e($ambulance['ambulance_number']) ?></h3>
                        <div class="ambulance-fleet-meta">
                            <span>Stretchers: <?= e((string) ($ambulance['capacity_stretchers'] ?? 1)) ?></span>
                            <span><?= !empty($ambulance['assigned_paramedic']) ? 'Paramedic: ' . e($ambulance['assigned_paramedic']) : 'Paramedic not assigned' ?></span>
                            <?php if (!empty($ambulance['current_latitude']) && !empty($ambulance['current_longitude'])): ?>
                                <span>Location: <?= e((string) $ambulance['current_latitude']) ?>, <?= e((string) $ambulance['current_longitude']) ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($ambulances === []): ?>
                    <div class="ops-empty">No ambulances are registered for this hospital yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>

<div class="app-modal" id="createStaffModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="createStaffTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="createStaffTitle"><span class="ops-inline-icon sky"><?= ui_icon('staff') ?></span>Create Staff</h2>
                <p class="ops-panel-subtext">Create a hospital-specific login for this dashboard ecosystem.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <form method="post" action="<?= e(url('/admin/hospital/staff')) ?>" class="grid-form compact app-modal-body">
            <?= csrf_field() ?>
            <label>Role
                <select name="role_slug" required>
                    <option value="hospital_staff">Hospital Dashboard Staff</option>
                    <option value="doctor">Doctor</option>
                    <option value="paramedic">Paramedic</option>
                    <option value="hospital_admin">Hospital Admin</option>
                </select>
            </label>
            <label>Designation
                <input type="text" name="designation" required>
            </label>
            <label>Full name
                <input type="text" name="full_name" required>
            </label>
            <label>NIC
                <input type="text" name="nic_number" maxlength="12" data-validate="nic" required>
            </label>
            <label>Email / username
                <input type="email" name="email" data-validate="email" required>
            </label>
            <label>Password
                <input type="text" name="password" data-validate="password" required>
            </label>
            <label>Phone
                <input type="tel" name="phone" maxlength="10" inputmode="numeric" data-validate="phone" required>
            </label>
            <label>Assign ambulance (for paramedics)
                <select name="ambulance_id">
                    <option value="">Not now</option>
                    <?php foreach ($ambulances as $ambulance): ?>
                        <option value="<?= e((string) $ambulance['ambulance_id']) ?>"><?= e($ambulance['ambulance_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="span-2 actions-row">
                <button class="button primary" type="submit">
                    <span class="button-icon"><?= ui_icon('user-check') ?></span>
                    <span>Create Staff Account</span>
                </button>
                <button class="button ghost" type="button" data-modal-close>Cancel</button>
            </div>
        </form>
    </section>
</div>

<div class="app-modal" id="createAmbulanceModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="createAmbulanceTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="createAmbulanceTitle"><span class="ops-inline-icon teal"><?= ui_icon('ambulance') ?></span>Create Ambulance</h2>
                <p class="ops-panel-subtext">Add a vehicle to <?= e($hospital['hospital_name'] ?? 'this hospital') ?>.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <form method="post" action="<?= e(url('/admin/hospital/ambulances')) ?>" class="grid-form compact app-modal-body">
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
            <div class="span-2 actions-row">
                <button class="button secondary" type="submit">
                    <span class="button-icon"><?= ui_icon('plus') ?></span>
                    <span>Add Ambulance</span>
                </button>
                <button class="button ghost" type="button" data-modal-close>Cancel</button>
            </div>
        </form>
    </section>
</div>

<div class="app-modal app-modal-wide" id="currentStaffModal" hidden>
    <div class="app-modal-backdrop" data-modal-close></div>
    <section class="app-modal-panel" role="dialog" aria-modal="true" aria-labelledby="currentStaffTitle">
        <div class="app-modal-head">
            <div>
                <h2 id="currentStaffTitle"><span class="ops-inline-icon violet"><?= ui_icon('users') ?></span>Current Staff</h2>
                <p class="ops-panel-subtext">Full card view of everyone operating under <?= e($hospital['hospital_name'] ?? 'this hospital') ?>.</p>
            </div>
            <button class="modal-close" type="button" data-modal-close aria-label="Close">x</button>
        </div>
        <div class="staff-card-grid app-modal-body">
            <?php foreach ($staff as $member): ?>
                <article class="staff-profile-card role-<?= e(str_replace('_', '-', (string) $member['role_slug'])) ?>">
                    <span class="ops-stat-icon violet"><?= ui_icon($member['role_slug'] === 'doctor' ? 'doctor' : ($member['role_slug'] === 'paramedic' ? 'stethoscope' : 'staff')) ?></span>
                    <h3><?= e($member['full_name']) ?></h3>
                    <span class="status-badge staff-role"><?= e($member['role_name']) ?></span>
                    <div class="staff-profile-meta">
                        <span><?= e($member['designation']) ?></span>
                        <span><?= e($member['email']) ?></span>
                        <span><?= e($member['phone'] ?? 'No phone') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($staff === []): ?>
                <div class="ops-empty">No staff members are registered for this hospital yet.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

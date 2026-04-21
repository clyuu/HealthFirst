<section class="dashboard-header">
    <div>
        <h1>Hospital Administration</h1>
        <p class="muted"><?= e($hospital['hospital_name'] ?? '') ?></p>
    </div>
    <div class="header-actions">
        <a class="button ghost" href="<?= e(url('/hospital/dashboard')) ?>">Live Emergency Board</a>
    </div>
</section>

<section class="three-col">
    <div class="panel">
        <h2>Create Staff</h2>
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

    <div class="panel">
        <h2>Create Ambulance</h2>
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

    <div class="panel">
        <h2>Current Staff</h2>
        <div class="card-list">
            <?php foreach ($staff as $member): ?>
                <article class="doc-card">
                    <strong><?= e($member['full_name']) ?></strong>
                    <span><?= e($member['role_name']) ?> | <?= e($member['designation']) ?></span>
                    <span class="muted"><?= e($member['email']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Ambulance Fleet</h2>
    <div class="card-list">
        <?php foreach ($ambulances as $ambulance): ?>
            <article class="doc-card">
                <strong><?= e($ambulance['ambulance_number']) ?></strong>
                <span><?= e($ambulance['status']) ?><?= !empty($ambulance['assigned_paramedic']) ? ' | ' . e($ambulance['assigned_paramedic']) : '' ?></span>
            </article>
        <?php endforeach; ?>
    </div>
</section>


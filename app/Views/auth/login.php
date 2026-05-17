<section class="auth-grid">
    <div class="panel">
        <h1>Login</h1>
        <p class="muted">Patients use email + password. Staff accounts are created internally.</p>
        <form method="post" action="<?= e(url('/login')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label>Email
                <input type="email" name="email" value="<?= e(old('email')) ?>" required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <button class="button primary" type="submit">Login</button>
        </form>
    </div>
    <div class="panel accent">
        <h2>Demo accounts from seed</h2>
        <p><strong>Patient:</strong> patient1@healthfirst.lk</p>
        <p><strong>Hospital Desk:</strong> hdesk1@healthfirst.lk</p>
        <p><strong>Doctor:</strong> doctor1@healthfirst.lk</p>
        <p><strong>Paramedic:</strong> paramedic1@healthfirst.lk</p>
        <p><strong>Hospital Admin:</strong> hadmin1@healthfirst.lk</p>
        <p><strong>Super Admin:</strong> admin@healthfirst.lk</p>
        <p class="muted">Password for seeded users: <code>Password@123</code></p>
    </div>
</section>

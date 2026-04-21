<?php

use App\Core\Auth;

$currentUser = Auth::user();
$pageTitle = $title ?? config_value('app.name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\Security::csrfToken()) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <div class="app-shell">
        <header class="site-header">
            <a class="brand" href="<?= e(url('/')) ?>">HealthFirst</a>
            <nav class="site-nav">
                <?php if ($currentUser): ?>
                    <span class="nav-pill"><?= e($currentUser['role_name']) ?></span>
                    <?php if ($currentUser['role_slug'] === 'patient'): ?>
                        <a href="<?= e(url('/patient/dashboard')) ?>">Dashboard</a>
                    <?php elseif ($currentUser['role_slug'] === 'paramedic'): ?>
                        <a href="<?= e(url('/ambulance/dashboard')) ?>">Ambulance</a>
                    <?php elseif ($currentUser['role_slug'] === 'doctor'): ?>
                        <a href="<?= e(url('/doctor/dashboard')) ?>">Doctor</a>
                    <?php elseif ($currentUser['role_slug'] === 'hospital_admin'): ?>
                        <a href="<?= e(url('/admin/hospital')) ?>">Hospital Admin</a>
                    <?php elseif ($currentUser['role_slug'] === 'system_admin'): ?>
                        <a href="<?= e(url('/admin/system')) ?>">System Admin</a>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button class="link-button" type="submit">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>">Login</a>
                    <a href="<?= e(url('/register')) ?>">Register</a>
                <?php endif; ?>
            </nav>
        </header>

        <?php foreach (flash_messages() as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <main class="page-container">
            <?= $content ?>
        </main>
    </div>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>


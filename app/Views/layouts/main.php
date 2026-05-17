<?php

use App\Core\Auth;

$currentUser = Auth::user();
$pageTitle = $title ?? config_value('app.name');
$icon = static function (string $name): string {
    $icons = [
        'pulse' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-4 3 8 2-4h7"></path></svg>',
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c1.8-4 5-6 8-6s6.2 2 8 6"></path></svg>',
        'qr' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"></path><path d="M15 15h2v2h-2zM18 15h2v5h-5v-2h3z"></path></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 16l4-4-4-4"></path><path d="M9 12h10"></path><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"></path></svg>',
    ];

    return $icons[$name] ?? '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\Security::csrfToken()) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
    <div class="app-shell">
        <header class="site-header">
            <div class="site-header-inner">
                <a class="brand" href="<?= e(url('/')) ?>">
                    <span class="brand-mark"><?= $icon('pulse') ?></span>
                    <span>Emergency Medical System</span>
                </a>
                <nav class="site-nav">
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role_slug'] === 'patient'): ?>
                            <a class="nav-link active" href="<?= e(url('/patient/dashboard')) ?>">
                                <span class="nav-icon"><?= $icon('dashboard') ?></span>
                                <span>Dashboard</span>
                            </a>
                            <a class="nav-link" href="<?= e(url('/patient/dashboard#editProfileModal')) ?>" data-modal-open="editProfileModal">
                                <span class="nav-icon"><?= $icon('profile') ?></span>
                                <span>Profile</span>
                            </a>
                            <a class="nav-link" href="<?= e(url('/patient/dashboard#patientQrModal')) ?>" data-modal-open="patientQrModal">
                                <span class="nav-icon"><?= $icon('qr') ?></span>
                                <span>QR Code</span>
                            </a>
                            <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <button class="link-button nav-link logout-link" type="submit">
                                    <span class="nav-icon"><?= $icon('logout') ?></span>
                                    <span>Logout</span>
                                </button>
                            </form>
                            <div class="lang-switch" aria-hidden="true">
                                <span class="lang-badge active">EN</span>
                                <span class="lang-badge">SI</span>
                            </div>
                        <?php else: ?>
                            <span class="nav-pill"><?= e($currentUser['role_name']) ?></span>
                            <?php if ($currentUser['role_slug'] === 'paramedic'): ?>
                                <a class="nav-link" href="<?= e(url('/paramedic/dashboard')) ?>">
                                    <span class="nav-icon"><?= ui_icon('stethoscope') ?></span>
                                    <span>Paramedic</span>
                                </a>
                                <a class="nav-link" href="<?= e(url('/ambulance/dashboard')) ?>">
                                    <span class="nav-icon"><?= ui_icon('ambulance') ?></span>
                                    <span>Ambulance</span>
                                </a>
                            <?php elseif ($currentUser['role_slug'] === 'doctor'): ?>
                                <a class="nav-link" href="<?= e(url('/doctor/dashboard')) ?>">
                                    <span class="nav-icon"><?= ui_icon('doctor') ?></span>
                                    <span>Doctor</span>
                                </a>
                            <?php elseif ($currentUser['role_slug'] === 'hospital_staff'): ?>
                                <a class="nav-link" href="<?= e(url('/hospital/dashboard')) ?>">
                                    <span class="nav-icon"><?= ui_icon('hospital') ?></span>
                                    <span>Hospital Board</span>
                                </a>
                            <?php elseif ($currentUser['role_slug'] === 'hospital_admin'): ?>
                                <a class="nav-link" href="<?= e(url('/admin/hospital')) ?>">
                                    <span class="nav-icon"><?= ui_icon('shield') ?></span>
                                    <span>Hospital Admin</span>
                                </a>
                                <a class="nav-link" href="<?= e(url('/hospital/dashboard')) ?>">
                                    <span class="nav-icon"><?= ui_icon('activity') ?></span>
                                    <span>Live Board</span>
                                </a>
                            <?php elseif ($currentUser['role_slug'] === 'system_admin'): ?>
                                <a class="nav-link" href="<?= e(url('/admin/system')) ?>">
                                    <span class="nav-icon"><?= ui_icon('shield') ?></span>
                                    <span>System Admin</span>
                                </a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <button class="link-button nav-link logout-link" type="submit">
                                    <span class="nav-icon"><?= $icon('logout') ?></span>
                                    <span>Logout</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="nav-link" href="<?= e(url('/login')) ?>">Login</a>
                        <a class="nav-link" href="<?= e(url('/register')) ?>">Register</a>
                        <div class="lang-switch" aria-hidden="true">
                            <span class="lang-badge active">EN</span>
                            <span class="lang-badge">SI</span>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <?php foreach (flash_messages() as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>" role="status">
                <span><?= e($flash['message']) ?></span>
                <button class="flash-dismiss" type="button" data-flash-dismiss aria-label="Remove notification">x</button>
            </div>
        <?php endforeach; ?>

        <main class="page-container">
            <?= $content ?>
        </main>
    </div>
</body>
</html>

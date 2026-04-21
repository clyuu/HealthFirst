<?php
$icon = static function (string $name): string {
    $icons = [
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.6-9.2-9A5.5 5.5 0 0 1 12 5.7 5.5 5.5 0 0 1 21.2 12C19 16.4 12 21 12 21z"></path><path d="M7 12h3l1.3-3 2.4 6 1.3-3H17"></path></svg>',
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c1.8-4 5-6 8-6s6.2 2 8 6"></path><path d="M18 8v6M15 11h6"></path></svg>',
        'login' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 16l4-4-4-4"></path><path d="M9 12h10"></path><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"></path></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c1.8-4 5-6 8-6s6.2 2 8 6"></path></svg>',
        'qr' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"></path><path d="M15 15h2v2h-2zM18 15h2v5h-5v-2h3z"></path></svg>',
        'hospital' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14"></path><path d="M14 21V3h6v18"></path><path d="M17 7v6M14 10h6M7 11v6M4 14h6"></path></svg>',
        'gear' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5A3.5 3.5 0 1 0 12 15.5A3.5 3.5 0 1 0 12 8.5z"></path><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 1 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 1 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 1 1 4 0v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6H20a2 2 0 1 1 0 4h-.2a1 1 0 0 0-.4.1z"></path></svg>',
        'warning' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 16H3L12 3z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>',
    ];

    return $icons[$name] ?? '';
};

$features = [
    [
        'icon' => 'profile',
        'title' => 'Medical Profile',
        'text' => 'Store your complete medical details, blood type, medical history and emergency contacts.',
        'class' => 'blue',
    ],
    [
        'icon' => 'qr',
        'title' => 'QR Code Access',
        'text' => 'Set up your vehicle QR code. Get help in an emergency by letting someone scan.',
        'class' => 'green',
    ],
    [
        'icon' => 'hospital',
        'title' => 'Hospital Alerts',
        'text' => 'Quickly send your condition and location to the nearest hospital in an emergency.',
        'class' => 'red',
    ],
];

$steps = [
    ['number' => '1', 'title' => 'Register', 'text' => 'Create an account and complete your medical profile', 'class' => 'blue'],
    ['number' => '2', 'title' => 'QR Code', 'text' => 'Print your QR code and attach it to your vehicle', 'class' => 'green'],
    ['number' => '3', 'title' => 'Emergency', 'text' => 'Scan the QR code and upload a photo in case of an accident', 'class' => 'yellow'],
    ['number' => '4', 'title' => 'Hospital Alert', 'text' => 'The nearest hospital receives automatic notification', 'class' => 'red'],
];
?>
<section class="landing-page">
    <section class="landing-hero">
        <div class="landing-hero-mark"><?= $icon('heart') ?></div>
        <h1>Emergency Medical System</h1>
        <p>Protect your life. Get immediate help in any emergency.</p>
        <div class="landing-hero-actions">
            <a class="button landing-primary-btn" href="<?= e(url('/register')) ?>">
                <span class="button-icon"><?= $icon('user') ?></span>
                <span>Register Now</span>
            </a>
            <a class="button landing-ghost-btn" href="<?= e(url('/login')) ?>">
                <span class="button-icon"><?= $icon('login') ?></span>
                <span>Login</span>
            </a>
        </div>
    </section>

    <section class="landing-feature-grid">
        <?php foreach ($features as $feature): ?>
            <article class="landing-feature-card">
                <div class="landing-feature-icon <?= e($feature['class']) ?>"><?= $icon($feature['icon']) ?></div>
                <h2><?= e($feature['title']) ?></h2>
                <p><?= e($feature['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="landing-steps-card">
        <div class="landing-steps-title">
            <span class="landing-steps-mark"><?= $icon('gear') ?></span>
            <h2>How it Works?</h2>
        </div>
        <div class="landing-steps-grid">
            <?php foreach ($steps as $step): ?>
                <article class="landing-step">
                    <div class="landing-step-number <?= e($step['class']) ?>"><?= e($step['number']) ?></div>
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e($step['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="landing-emergency-card">
        <div class="landing-emergency-title">
            <span class="landing-steps-mark warning"><?= $icon('warning') ?></span>
            <h2>Emergency Numbers</h2>
        </div>
        <div class="landing-emergency-grid">
            <div>
                <h3>Ambulance</h3>
                <p>1990</p>
            </div>
            <div>
                <h3>Police</h3>
                <p>119</p>
            </div>
            <div>
                <h3>Fire &amp; Rescue</h3>
                <p>110</p>
            </div>
        </div>
    </section>

    <footer class="landing-footer">
        <p>&copy; 2025 Emergency Medical System. All rights reserved.</p>
        <p>Emergency Numbers: 1990 | Police: 119 | Fire &amp; Rescue: 110</p>
    </footer>
</section>

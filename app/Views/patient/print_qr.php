<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print QR Sticker</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        .sticker { width: 420px; border: 3px dashed #d14343; border-radius: 24px; padding: 1.5rem; text-align: center; }
        .sticker img { width: 240px; height: 240px; }
        .sticker h1 { margin-bottom: 0.25rem; }
        .sticker p { margin: 0.35rem 0; }
        .print-button { margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="sticker">
        <h1>HealthFirst Emergency QR</h1>
        <p><strong><?= e($user['full_name']) ?></strong></p>
        <p>Scan only in an emergency situation.</p>
        <img src="<?= e(url('/patient/qr/download')) ?>" alt="QR sticker">
        <p>Vehicle Emergency Medical Access</p>
        <p><?= e($qr['qr_value'] ?? '') ?></p>
        <button class="print-button" onclick="window.print()">Print Sticker</button>
    </div>
</body>
</html>


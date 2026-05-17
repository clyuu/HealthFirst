<?php
$bloodGroups = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$phonePattern = '0[0-9]{9}';
$nicPattern = '(?:[0-9]{9}[VvXx]|[0-9]{12})';
$passwordPattern = '(?=.*[A-Za-z])(?=.*[0-9]).{8,}';
?>
<section class="panel wide">
    <h1>Patient Registration</h1>
    <p class="muted">Every public registration becomes a patient account and immediately receives a unique HealthFirst QR code.</p>

    <form method="post" action="<?= e(url('/register')) ?>" class="grid-form">
        <?= csrf_field() ?>
        <label>Full name
            <input type="text" name="full_name" value="<?= e(old('full_name')) ?>" required>
        </label>
        <label>NIC number
            <input type="text" name="nic_number" value="<?= e(old('nic_number')) ?>" pattern="<?= e($nicPattern) ?>" maxlength="12" data-validate="nic" title="Use 9 digits plus V/X, or exactly 12 digits." required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" data-validate="email" required>
        </label>
        <label>Phone
            <input type="tel" name="phone" value="<?= e(old('phone')) ?>" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone" title="Use exactly 10 digits, such as 0771234567." required>
        </label>
        <label>Date of birth
            <input type="date" name="date_of_birth" value="<?= e(old('date_of_birth')) ?>">
        </label>
        <label>Gender
            <select name="gender">
                <option value="">Select</option>
                <option value="Male" <?= old('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= old('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= old('gender') === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </label>
        <label class="span-2">Address
            <textarea name="address" rows="3" required><?= e(old('address')) ?></textarea>
        </label>
        <label>Password
            <input type="password" name="password" minlength="8" pattern="<?= e($passwordPattern) ?>" data-validate="password" title="Use at least 8 characters with letters and numbers." required>
        </label>
        <label>Emergency phone
            <input type="tel" name="emergency_phone" value="<?= e(old('emergency_phone')) ?>" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone-optional" title="Use exactly 10 digits, such as 0771234567.">
        </label>
        <label>Blood group
            <select name="blood_group" required>
                <?php foreach ($bloodGroups as $group): ?>
                    <option value="<?= e($group) ?>" <?= old('blood_group', 'O+') === $group ? 'selected' : '' ?>><?= e($group) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Allergies
            <input type="text" name="allergies" value="<?= e(old('allergies')) ?>">
        </label>
        <label class="span-2">Chronic conditions
            <textarea name="chronic_conditions" rows="2"><?= e(old('chronic_conditions')) ?></textarea>
        </label>
        <label class="span-2">Notes
            <textarea name="notes" rows="2"><?= e(old('notes')) ?></textarea>
        </label>
        <label>Emergency contact name
            <input type="text" name="contact_name" value="<?= e(old('contact_name')) ?>">
        </label>
        <label>Emergency contact phone
            <input type="tel" name="contact_phone" value="<?= e(old('contact_phone')) ?>" pattern="<?= e($phonePattern) ?>" maxlength="10" inputmode="numeric" data-validate="phone-optional" title="Use exactly 10 digits, such as 0771234567.">
        </label>
        <label>Relationship
            <input type="text" name="contact_relationship" value="<?= e(old('contact_relationship')) ?>">
        </label>

        <div class="span-2 location-picker" data-location-picker data-auto-capture="1" data-require-location="1" data-api-key="<?= e($mapsApiKey ?? '') ?>" data-default-lat="6.9271" data-default-lng="79.8612">
            <input type="hidden" name="profile_latitude" value="<?= e(old('profile_latitude')) ?>" data-location-lat>
            <input type="hidden" name="profile_longitude" value="<?= e(old('profile_longitude')) ?>" data-location-lng>
            <div class="location-picker-head">
                <div>
                    <h3>Select Home Location</h3>
                    <p class="muted">Current location is detected first. You can also search your home location with Google Maps.</p>
                </div>
                <button class="button tiny ghost" type="button" data-location-current>Use current location</button>
            </div>
            <div class="location-search-row">
                <input type="search" class="location-search" placeholder="Search home location" data-location-search>
                <button class="button tiny primary" type="button" data-location-search-button>Search</button>
                <button class="button tiny ghost location-drop-toggle" type="button" data-location-drop-toggle aria-pressed="false">Drop pin</button>
            </div>
            <div class="location-map" data-location-map data-location-preview></div>
            <div class="notice-area" data-location-notice hidden></div>
            <p class="location-selected" data-location-selected>No home location selected yet.</p>
        </div>

        <div class="span-2">
            <button class="button primary" type="submit">Create Patient Account</button>
        </div>
    </form>
</section>
<script src="<?= e(asset('js/modules/patient.js')) ?>" defer></script>

<section class="panel wide">
    <h1>Patient Registration</h1>
    <p class="muted">Every public registration becomes a patient account and immediately receives a unique HealthFirst QR code.</p>

    <form method="post" action="<?= e(url('/register')) ?>" class="grid-form">
        <?= csrf_field() ?>
        <label>Full name
            <input type="text" name="full_name" value="<?= e(old('full_name')) ?>" required>
        </label>
        <label>NIC number
            <input type="text" name="nic_number" value="<?= e(old('nic_number')) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
        </label>
        <label>Phone
            <input type="text" name="phone" value="<?= e(old('phone')) ?>" required>
        </label>
        <label>Date of birth
            <input type="date" name="date_of_birth" value="<?= e(old('date_of_birth')) ?>">
        </label>
        <label>Gender
            <select name="gender">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </label>
        <label class="span-2">Address
            <textarea name="address" rows="3" required><?= e(old('address')) ?></textarea>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <label>Emergency phone
            <input type="text" name="emergency_phone" value="<?= e(old('emergency_phone')) ?>">
        </label>
        <label>Blood group
            <input type="text" name="blood_group" value="<?= e(old('blood_group', 'O+')) ?>">
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
            <input type="text" name="contact_phone" value="<?= e(old('contact_phone')) ?>">
        </label>
        <label>Relationship
            <input type="text" name="contact_relationship" value="<?= e(old('contact_relationship')) ?>">
        </label>
        <label>Home latitude
            <input type="text" name="profile_latitude" value="<?= e(old('profile_latitude')) ?>">
        </label>
        <label>Home longitude
            <input type="text" name="profile_longitude" value="<?= e(old('profile_longitude')) ?>">
        </label>
        <div class="span-2">
            <button class="button primary" type="submit">Create Patient Account</button>
        </div>
    </form>
</section>


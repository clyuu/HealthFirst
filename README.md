# HealthFirst

HealthFirst is a custom PHP MVC emergency medical coordination system with a companion Flask AI service. It supports:

- Patient self-registration and QR generation
- Public QR-triggered emergency reporting with scene photo + GPS capture
- Nearest registered hospital selection based on DB coordinates
- Hospital, ambulance, doctor, hospital-admin, and super-admin dashboards
- Accident verification and injury analysis using the provided `.keras` models

## Stack

- PHP 8.2 + MySQL
- HTML, CSS, vanilla JavaScript
- Python Flask + TensorFlow + ReportLab
- Google Maps JavaScript API + Routes API

## Project Structure

- [public/index.php](C:/Users/user/Desktop/HealthFirst/HealthFirst/public/index.php) front controller
- [app/Controllers](C:/Users/user/Desktop/HealthFirst/HealthFirst/app/Controllers) MVC controllers
- [app/Models](C:/Users/user/Desktop/HealthFirst/HealthFirst/app/Models) PDO-backed models
- [app/Services](C:/Users/user/Desktop/HealthFirst/HealthFirst/app/Services) workflow/services layer
- [database/schema.sql](C:/Users/user/Desktop/HealthFirst/HealthFirst/database/schema.sql) normalized schema
- [database/seed.sql](C:/Users/user/Desktop/HealthFirst/HealthFirst/database/seed.sql) demo accounts and incidents
- [ai_service/app.py](C:/Users/user/Desktop/HealthFirst/HealthFirst/ai_service/app.py) Flask AI wrapper

## Prerequisites

Install these first:

- XAMPP with `Apache` and `MySQL`
- Python 3.10+ with `pip`
- A Google Maps API key
  - `GOOGLE_MAPS_API_KEY` for the map UI
  - `GOOGLE_ROUTES_API_KEY` for ETA and route calculations

## Recommended Folder Location

For the easiest local setup with XAMPP, place the project inside:

```text
C:\xampp\htdocs\HealthFirst
```

If your project is currently somewhere else, either:

1. Copy/move it into `C:\xampp\htdocs\HealthFirst`, or
2. Create an Apache alias/virtual host manually.

The instructions below assume the project is available at:

```text
http://localhost/HealthFirst
```

## Step 1: Create `.env`

Copy [.env.example](C:/Users/user/Desktop/HealthFirst/HealthFirst/.env.example) to `.env`.

Example PowerShell command:

```powershell
Copy-Item .env.example .env
```

Then update `.env` values to match your local machine:

```env
APP_NAME=HealthFirst
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/HealthFirst
APP_TIMEZONE=Asia/Colombo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=healthfirst
DB_USER=root
DB_PASS=

GOOGLE_MAPS_API_KEY=your_maps_key_here
GOOGLE_ROUTES_API_KEY=your_routes_key_here
AI_SERVICE_URL=http://127.0.0.1:5001
PYTHON_BIN=python
```

Notes:

- In default XAMPP, MySQL user is usually `root` and password is empty.
- If you only set `GOOGLE_MAPS_API_KEY` and leave `GOOGLE_ROUTES_API_KEY` empty, the system can still render maps but ETA may fall back to Haversine estimates instead of Google route times.

## Step 2: Create the Database

Start `Apache` and `MySQL` from the XAMPP Control Panel first.

Then import the SQL files in one of these ways.

### Option A: phpMyAdmin

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Go to `Import`
3. Import [database/schema.sql](C:/Users/user/Desktop/HealthFirst/HealthFirst/database/schema.sql)
4. After that finishes, import [database/seed.sql](C:/Users/user/Desktop/HealthFirst/HealthFirst/database/seed.sql)

### Option B: MySQL command line

Run these commands from the project root:

```powershell
mysql -u root < database\schema.sql
mysql -u root healthfirst < database\seed.sql
```

If your MySQL root user has a password:

```powershell
mysql -u root -p < database\schema.sql
mysql -u root -p healthfirst < database\seed.sql
```

## Step 3: Install Python Packages

The AI service and QR generator need Python dependencies.

Run:

```powershell
python -m pip install -r ai_service\requirements.txt
```

This installs:

- Flask
- TensorFlow
- Pillow
- ReportLab
- qrcode

## Step 4: Start the AI Service

Open a new terminal in the project root and run:

```powershell
python ai_service\app.py
```

If everything is working, the Flask AI service should start on:

```text
http://127.0.0.1:5001
```

Quick health check:

[http://127.0.0.1:5001/health](http://127.0.0.1:5001/health)

## Step 5: Run the PHP App

Make sure Apache and MySQL are running in XAMPP.

Then open:

[http://localhost/HealthFirst](http://localhost/HealthFirst)

The root [`.htaccess`](C:/Users/user/Desktop/HealthFirst/HealthFirst/.htaccess) rewrites requests into [public/index.php](C:/Users/user/Desktop/HealthFirst/HealthFirst/public/index.php), so you do not need to manually browse to `/public`.

## Step 6: Login With Seeded Accounts

All seeded users use the same password:

```text
Password@123
```

Available demo users:

- `admin@healthfirst.lk`
- `hadmin1@healthfirst.lk`
- `hadmin2@healthfirst.lk`
- `doctor1@healthfirst.lk`
- `doctor2@healthfirst.lk`
- `paramedic1@healthfirst.lk`
- `paramedic2@healthfirst.lk`
- `patient1@healthfirst.lk`
- `patient2@healthfirst.lk`

## Quick Smoke Test

After startup, test in this order:

1. Open the landing page and confirm it loads.
2. Login as `patient1@healthfirst.lk`.
3. Open the patient dashboard and confirm the QR image appears.
4. Open the QR public page using:

```text
http://localhost/HealthFirst/qr/seedtoken-patient1
```

5. Submit a scene image and location from that page.
6. Login as `hadmin1@healthfirst.lk` and open the hospital dashboard.
7. Assign an ambulance to a red incident tile.
8. Login as `paramedic1@healthfirst.lk` and open the ambulance dashboard.
9. Start an injury session and finalize it.
10. Login as `doctor1@healthfirst.lk`, admit the patient, then open `My Patients`.

## Useful Commands

### PHP syntax check

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

### Python syntax check

```powershell
python -m py_compile ai_service\app.py ai_service\utils\reporting.py bin\generate_qr.py
```

### JS syntax check

```powershell
node --check public\assets\js\app.js
node --check public\assets\js\modules\emergency.js
node --check public\assets\js\modules\patient.js
node --check public\assets\js\modules\hospital.js
node --check public\assets\js\modules\ambulance.js
node --check public\assets\js\modules\doctor.js
```

## Generated and Uploaded Files

- QR images are generated under `storage/generated/qrcodes/`
- AI injury report PDFs are generated under `storage/generated/reports/`
- Scene uploads go to `storage/uploads/scene/`
- Injury uploads go to `storage/uploads/injuries/`
- Medical document uploads go to `storage/uploads/documents/`

## Troubleshooting

### `http://localhost/HealthFirst` shows 404

- Confirm the project folder is inside `C:\xampp\htdocs\HealthFirst`
- Confirm Apache is running
- Confirm `.htaccess` is enabled in Apache

### Database connection error

- Check `.env` values for `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`
- Make sure the `healthfirst` database was created by `database/schema.sql`

### AI features fail

- Confirm the Flask service is running on `http://127.0.0.1:5001`
- Confirm the model files still exist:
  - [custom_car_accident_model.keras](C:/Users/user/Desktop/HealthFirst/HealthFirst/custom_car_accident_model.keras)
  - [custom_injury_model.keras](C:/Users/user/Desktop/HealthFirst/HealthFirst/custom_injury_model.keras)
- Confirm Python packages installed successfully

### QR generation fails

- Confirm `qrcode` installed:

```powershell
python -m pip install qrcode[pil]
```

### Maps are not showing

- Add a valid `GOOGLE_MAPS_API_KEY` in `.env`
- Enable the required Google APIs in your Google Cloud project
- Restrict the API key properly before deployment

## Notes

- QR image generation uses [bin/generate_qr.py](C:/Users/user/Desktop/HealthFirst/HealthFirst/bin/generate_qr.py).
- Generated QR PNGs and injury report PDFs are stored under `storage/generated/`.
- Uploaded scene, injury, and medical files are stored under `storage/uploads/`.
- If the Google Routes API key is missing, the system falls back to Haversine-based ETA estimates.
- Injury report PDFs are labeled as AI preliminary assessments and should not replace clinical judgement.

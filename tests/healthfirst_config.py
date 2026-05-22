from pathlib import Path
import os


PROJECT_ROOT = Path(__file__).resolve().parents[1]

BASE_URL = os.getenv("HEALTHFIRST_BASE_URL", "http://localhost/HealthFirst").rstrip("/")
HEADLESS = os.getenv("HEALTHFIRST_HEADLESS", "1").strip() != "0"
PLAYWRIGHT_BROWSER_CHANNEL = os.getenv("HEALTHFIRST_BROWSER_CHANNEL", "chrome").strip() or None
SLOW_MO_MS = int(os.getenv("HEALTHFIRST_SLOW_MO_MS", "0"))
DEFAULT_TIMEOUT_MS = int(os.getenv("HEALTHFIRST_TIMEOUT_MS", "30000"))

SCREENSHOT_DIR = PROJECT_ROOT / "output" / "playwright"
SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)

DEFAULT_LATITUDE = float(os.getenv("HEALTHFIRST_TEST_LATITUDE", "6.927100"))
DEFAULT_LONGITUDE = float(os.getenv("HEALTHFIRST_TEST_LONGITUDE", "79.861200"))

DEMO_PASSWORD = os.getenv("HEALTHFIRST_DEMO_PASSWORD", "Password@123")

ACCOUNTS = {
    "patient": {
        "email": os.getenv("HEALTHFIRST_PATIENT_EMAIL", "patient1@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "hospital_staff": {
        "email": os.getenv("HEALTHFIRST_HOSPITAL_STAFF_EMAIL", "hdesk1@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "paramedic": {
        "email": os.getenv("HEALTHFIRST_PARAMEDIC_EMAIL", "paramedic1@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "paramedic_alt": {
        "email": os.getenv("HEALTHFIRST_PARAMEDIC_ALT_EMAIL", "paramedic2@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "doctor": {
        "email": os.getenv("HEALTHFIRST_DOCTOR_EMAIL", "doctor1@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "hospital_admin": {
        "email": os.getenv("HEALTHFIRST_HOSPITAL_ADMIN_EMAIL", "hadmin1@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
    "system_admin": {
        "email": os.getenv("HEALTHFIRST_SYSTEM_ADMIN_EMAIL", "admin@healthfirst.lk"),
        "password": DEMO_PASSWORD,
    },
}

SCENE_PHOTO_PATH = Path(
    os.getenv("HEALTHFIRST_SCENE_PHOTO", str(PROJECT_ROOT / "healthfirst-hospital-dashboard.png"))
)
INJURY_PHOTO_PATH = Path(
    os.getenv("HEALTHFIRST_INJURY_PHOTO", str(PROJECT_ROOT / "healthfirst-ambulance-dashboard.png"))
)
DOCUMENT_PATH = Path(os.getenv("HEALTHFIRST_DOCUMENT_FILE", str(PROJECT_ROOT / "README.md")))

from __future__ import annotations

import importlib.util
import subprocess
import sys
from pathlib import Path


TEST_FILES = [
    "test_01_patient_login_and_qr.py",
    "test_02_public_emergency_report.py",
    "test_03_hospital_ambulance_assignment.py",
    "test_04_ambulance_pickup_arrival.py",
    "test_05_paramedic_injury_report.py",
    "test_06_doctor_admission_discharge.py",
    "test_07_admin_setup_workflow.py",
    "test_08_role_access_security.py",
]


def ensure_playwright_installed() -> bool:
    if importlib.util.find_spec("playwright") is not None:
        return True

    print("\nPlaywright is not installed in this Python environment.")
    print("Install it with these commands, then run the tests again:\n")
    print(r"python -m pip install -r tests\requirements.txt")
    print("\nThese tests are configured to use your installed Google Chrome browser.")
    print("If Chrome is not detected, install Google Chrome or set HEALTHFIRST_BROWSER_CHANNEL=chromium.\n")
    return False


def main() -> int:
    if not ensure_playwright_installed():
        return 1

    tests_dir = Path(__file__).resolve().parent
    failed = []

    for test_file in TEST_FILES:
        print(f"\n=== Running {test_file} ===")
        result = subprocess.run([sys.executable, str(tests_dir / test_file)], check=False)
        if result.returncode != 0:
            failed.append(test_file)

    if failed:
        print("\nFAILED TESTS:")
        for test_file in failed:
            print(f"- {test_file}")
        return 1

    print("\nAll HealthFirst automated workflow tests passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

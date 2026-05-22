# HealthFirst Automated Workflow Tests

These Python Playwright scripts test the main HealthFirst browser workflows used in Chapter 7.

## Setup

```powershell
cd C:\xampp\htdocs\HealthFirst
python -m pip install -r tests\requirements.txt
```

These tests use your installed Google Chrome browser by default. Before running the tests, start Apache/MySQL through XAMPP. For AI-related workflows, also start the Flask AI service.

```powershell
.\scripts\start-ai-service.ps1
```

## Run All Tests

```powershell
python tests\run_all_workflow_tests.py
```

## Run With Pytest Output

```powershell
python -m pytest tests\test_workflows_pytest.py -v
```

If you are already inside the `tests` folder:

```powershell
python -m pytest test_workflows_pytest.py -v
```

## Run One Test

```powershell
python tests\test_01_patient_login_and_qr.py
```

Screenshots are saved in:

```text
output\playwright
```

## Optional Environment Variables

```powershell
$env:HEALTHFIRST_BASE_URL = "http://localhost/HealthFirst"
$env:HEALTHFIRST_HEADLESS = "0"
$env:HEALTHFIRST_BROWSER_CHANNEL = "chrome"
$env:HEALTHFIRST_SLOW_MO_MS = "250"
$env:HEALTHFIRST_SCENE_PHOTO = "C:\path\to\accident-image.jpg"
$env:HEALTHFIRST_INJURY_PHOTO = "C:\path\to\injury-image.jpg"
```

If Chrome is not detected on the machine, either install Google Chrome or switch to Playwright Chromium:

```powershell
$env:HEALTHFIRST_BROWSER_CHANNEL = "chromium"
python -m playwright install chromium
```

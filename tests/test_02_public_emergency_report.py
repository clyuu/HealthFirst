import re

from playwright.sync_api import expect

from healthfirst_config import DEFAULT_LATITUDE, DEFAULT_LONGITUDE, SCENE_PHOTO_PATH
from healthfirst_helpers import browser_page, print_pass, save_screenshot, url


def run() -> None:
    if not SCENE_PHOTO_PATH.exists():
        raise FileNotFoundError(f"Scene photo fixture not found: {SCENE_PHOTO_PATH}")

    with browser_page() as page:
        page.goto(url("/emergency/report"), wait_until="networkidle")
        page.get_by_role("button", name=re.compile(r"Inform Emergency", re.I)).click()
        expect(page.get_by_text("Scene Capture")).to_be_visible()

        page.locator('input[name="incident_latitude"]').evaluate(
            "(node, value) => node.value = value", str(DEFAULT_LATITUDE)
        )
        page.locator('input[name="incident_longitude"]').evaluate(
            "(node, value) => node.value = value", str(DEFAULT_LONGITUDE)
        )
        page.locator('input[name="patient_name"]').fill("Automated Test Patient")
        page.locator('input[name="patient_nic"]').fill("991234567V")
        page.locator('input[name="patient_phone"]').fill("0775551234")
        page.locator('input[name="vehicle_number"]').fill("TEST-2026")
        page.locator('input[name="injured_count"]').fill("1")
        page.locator('textarea[name="public_message"]').fill("Automated emergency workflow test.")
        page.locator('input[name="scene_photo"]').set_input_files(str(SCENE_PHOTO_PATH))

        page.get_by_role("button", name=re.compile(r"Submit Emergency Report", re.I)).click()
        expect(page.locator("[data-emergency-status]")).to_contain_text(
            re.compile(r"Emergency report received|submitted|reviewing", re.I),
            timeout=120000,
        )

        screenshot = save_screenshot(page, "AT-002-public-emergency-report")
        print_pass("AT-002 Public emergency report workflow", screenshot)


if __name__ == "__main__":
    run()

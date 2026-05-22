import re

from playwright.sync_api import expect

from healthfirst_config import INJURY_PHOTO_PATH
from healthfirst_helpers import attach_dialog_acceptor, browser_page, login_as, print_pass, save_screenshot, url


def run() -> None:
    if not INJURY_PHOTO_PATH.exists():
        raise FileNotFoundError(f"Injury photo fixture not found: {INJURY_PHOTO_PATH}")

    with browser_page() as page:
        attach_dialog_acceptor(page)
        login_as(page, "paramedic_alt")
        page.goto(url("/paramedic/dashboard"), wait_until="networkidle")

        expect(page.get_by_text("Paramedic Dashboard")).to_be_visible()

        lookup_form = page.locator("[data-lookup-form]").first
        if lookup_form.count() > 0:
            lookup_form.locator('input[name="public_token"]').fill("seedtoken-patient1")
            lookup_form.get_by_role("button", name=re.compile(r"Load Patient Details", re.I)).click()
            page.wait_for_timeout(500)

        vitals_form = page.locator("[data-vitals-form]").first
        if vitals_form.count() > 0:
            vitals_form.locator('input[name="heart_rate"]').fill("98")
            vitals_form.locator('input[name="spo2"]').fill("96")
            vitals_form.locator('input[name="systolic_bp"]').fill("118")
            vitals_form.locator('input[name="diastolic_bp"]').fill("76")
            vitals_form.locator('input[name="temperature_c"]').fill("37.0")
            vitals_form.locator('textarea[name="notes"]').fill("Automated vitals test.")
            vitals_form.get_by_role("button", name=re.compile(r"Save Vitals", re.I)).click()
            page.wait_for_timeout(500)

        injury_root = page.locator("[data-injury-root]").first
        if injury_root.count() > 0:
            injury_root.locator('textarea[name="special_note"]').fill("Automated injury session test.")
            injury_root.locator("[data-start-injury]").click()
            expect(injury_root.locator("[data-injury-status]")).to_contain_text(
                re.compile(r"Injury session started", re.I)
            )
            injury_root.locator("[data-injury-file]").set_input_files(str(INJURY_PHOTO_PATH))
            injury_root.locator("[data-attach-photo]").click()
            expect(injury_root.locator("[data-injury-status]")).to_contain_text(
                re.compile(r"Attached:", re.I),
                timeout=120000,
            )
            injury_root.locator("[data-finalize-session]").click()
            expect(injury_root.locator("[data-injury-status]")).to_contain_text(
                re.compile(r"Injury report ready", re.I),
                timeout=120000,
            )

        screenshot = save_screenshot(page, "AT-005-paramedic-injury-report")
        print_pass("AT-005 Paramedic injury report workflow", screenshot)


if __name__ == "__main__":
    run()

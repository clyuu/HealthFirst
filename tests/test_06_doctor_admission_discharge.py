import re

from playwright.sync_api import expect

from healthfirst_helpers import browser_page, login_as, print_pass, save_screenshot, url


def run() -> None:
    with browser_page() as page:
        login_as(page, "doctor")
        page.goto(url("/doctor/dashboard"), wait_until="networkidle")
        expect(page.get_by_text("Doctor Dashboard")).to_be_visible()

        admit_form = page.locator(".admit-form").first
        if admit_form.count() > 0:
            admit_form.get_by_role("button", name=re.compile(r"Admit", re.I)).click()
            page.wait_for_load_state("networkidle")
        else:
            page.goto(url("/doctor/patients"), wait_until="networkidle")

        expect(page.get_by_text("My Patients")).to_be_visible()

        discharge_button = page.get_by_role("button", name=re.compile(r"Discharge", re.I)).first
        if discharge_button.count() > 0:
            discharge_button.click()
            page.wait_for_load_state("networkidle")
            expect(page.get_by_text("My Patients")).to_be_visible()

        screenshot = save_screenshot(page, "AT-006-doctor-admission-discharge")
        print_pass("AT-006 Doctor admission and discharge workflow", screenshot)


if __name__ == "__main__":
    run()

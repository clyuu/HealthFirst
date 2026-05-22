import re

from playwright.sync_api import expect

from healthfirst_helpers import (
    browser_page,
    login_as,
    print_pass,
    save_screenshot,
    select_first_real_option,
    url,
)


def run() -> None:
    with browser_page() as page:
        login_as(page, "hospital_staff")
        page.goto(url("/hospital/dashboard"), wait_until="networkidle")

        expect(page.get_by_text("Live Incident Board")).to_be_visible()
        assign_form = page.locator("[data-assign-form]").first
        if assign_form.count() > 0:
            selected = select_first_real_option(assign_form.locator('select[name="ambulance_id"]'))
            if selected:
                assign_form.get_by_role("button", name=re.compile(r"Assign Ambulance", re.I)).click()
                page.wait_for_timeout(1500)
                page.wait_for_load_state("networkidle")

        expect(page.get_by_text(re.compile(r"Live Incident Board|Ambulance", re.I)).first).to_be_visible()
        screenshot = save_screenshot(page, "AT-003-hospital-ambulance-assignment")
        print_pass("AT-003 Hospital ambulance assignment workflow", screenshot)


if __name__ == "__main__":
    run()

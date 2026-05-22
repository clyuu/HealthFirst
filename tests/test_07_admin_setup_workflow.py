import re

from playwright.sync_api import expect

from healthfirst_helpers import browser_page, login_as, print_pass, save_screenshot, url


def close_modal(page) -> None:
    close = page.locator(".app-modal:not([hidden]) [data-modal-close]").last
    if close.count() > 0:
        close.click()
        expect(page.locator(".app-modal:not([hidden])")).to_have_count(0)


def run() -> None:
    with browser_page() as page:
        login_as(page, "system_admin")
        page.goto(url("/admin/system"), wait_until="networkidle")
        expect(page.get_by_text("System Administration")).to_be_visible()

        page.get_by_role("button", name=re.compile(r"Add Hospital", re.I)).click()
        expect(page.get_by_text("Enter the hospital details first")).to_be_visible()
        close_modal(page)

        page.get_by_role("button", name=re.compile(r"Create Hospital Admin", re.I)).click()
        expect(page.get_by_text("This login opens the assigned hospital admin dashboard")).to_be_visible()
        close_modal(page)

        page.get_by_role("button", name=re.compile(r"View", re.I)).first.click()
        expect(page.get_by_text("Large card view of every hospital")).to_be_visible()

        screenshot_system = save_screenshot(page, "AT-007-system-admin-setup")

    with browser_page() as page:
        login_as(page, "hospital_admin")
        page.goto(url("/admin/hospital"), wait_until="networkidle")
        expect(page.get_by_text("Hospital Administration")).to_be_visible()

        page.get_by_role("button", name=re.compile(r"Create Staff", re.I)).first.click()
        expect(page.locator(".app-modal:not([hidden]) #createStaffTitle")).to_be_visible()
        close_modal(page)

        page.get_by_role("button", name=re.compile(r"Create Ambulance", re.I)).first.click()
        expect(page.locator(".app-modal:not([hidden]) #createAmbulanceTitle")).to_be_visible()
        screenshot_hospital = save_screenshot(page, "AT-007-hospital-admin-setup")

        print_pass("AT-007 Admin setup workflow", screenshot_hospital)
        print(f"AT-007 system-admin screenshot={screenshot_system}")


if __name__ == "__main__":
    run()

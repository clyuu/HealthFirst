import re

from playwright.sync_api import expect

from healthfirst_helpers import browser_page, login_as, print_pass, save_screenshot, url


def run() -> None:
    with browser_page() as page:
        login_as(page, "patient")
        page.goto(url("/admin/system"), wait_until="networkidle")
        expect(page.get_by_text(re.compile(r"Access denied|permission", re.I)).first).to_be_visible()

        page.goto(url("/doctor/dashboard"), wait_until="networkidle")
        expect(page.get_by_text(re.compile(r"Access denied|permission", re.I)).first).to_be_visible()

        screenshot = save_screenshot(page, "AT-008-role-access-security")
        print_pass("AT-008 Role-based access control", screenshot)


if __name__ == "__main__":
    run()

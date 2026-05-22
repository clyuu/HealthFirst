import re

from playwright.sync_api import expect

from healthfirst_helpers import browser_page, login_as, print_pass, save_screenshot, url


def run() -> None:
    with browser_page() as page:
        login_as(page, "patient")
        page.goto(url("/patient/dashboard"), wait_until="networkidle")

        expect(page.get_by_text(re.compile(r"Welcome", re.I)).first).to_be_visible()
        page.get_by_role("button", name=re.compile(r"QR Code", re.I)).first.click()
        expect(page.get_by_text("My QR Code")).to_be_visible()

        screenshot = save_screenshot(page, "AT-001-patient-login-and-qr")
        print_pass("AT-001 Patient login and QR access", screenshot)


if __name__ == "__main__":
    run()

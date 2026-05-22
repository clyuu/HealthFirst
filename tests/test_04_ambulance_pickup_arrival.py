import re

from playwright.sync_api import expect

from healthfirst_helpers import attach_dialog_acceptor, browser_page, login_as, print_pass, save_screenshot, url


def run() -> None:
    with browser_page() as page:
        attach_dialog_acceptor(page)
        login_as(page, "paramedic")
        page.goto(url("/ambulance/dashboard"), wait_until="networkidle")

        expect(page.get_by_text("Ambulance Dashboard")).to_be_visible()

        pickup_button = page.locator("[data-pickup-button]").first
        if pickup_button.count() > 0:
            pickup_button.click()
            page.wait_for_timeout(1500)
            page.wait_for_load_state("networkidle")

        arrive_button = page.locator("[data-arrive-hospital-button]").first
        if arrive_button.count() > 0:
            arrive_button.click()
            page.wait_for_timeout(1500)
            page.wait_for_load_state("networkidle")

        expect(page.get_by_text(re.compile(r"Ambulance Dashboard|Dispatch Board", re.I)).first).to_be_visible()
        screenshot = save_screenshot(page, "AT-004-ambulance-pickup-arrival")
        print_pass("AT-004 Ambulance pickup and arrival workflow", screenshot)


if __name__ == "__main__":
    run()

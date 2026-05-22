from __future__ import annotations

from contextlib import contextmanager
from pathlib import Path
from urllib.parse import urlparse
import re

from playwright.sync_api import Page, expect, sync_playwright

from healthfirst_config import (
    ACCOUNTS,
    BASE_URL,
    DEFAULT_LATITUDE,
    DEFAULT_LONGITUDE,
    DEFAULT_TIMEOUT_MS,
    HEADLESS,
    PLAYWRIGHT_BROWSER_CHANNEL,
    SCREENSHOT_DIR,
    SLOW_MO_MS,
)


def url(path: str) -> str:
    if path.startswith("http://") or path.startswith("https://"):
        return path
    return f"{BASE_URL}/{path.lstrip('/')}"


def origin() -> str:
    parsed = urlparse(BASE_URL)
    return f"{parsed.scheme}://{parsed.netloc}"


def screenshot_path(name: str) -> Path:
    safe = re.sub(r"[^a-zA-Z0-9_.-]+", "-", name).strip("-")
    return SCREENSHOT_DIR / f"{safe}.png"


def save_screenshot(page: Page, name: str) -> Path:
    path = screenshot_path(name)
    page.screenshot(path=str(path), full_page=True)
    return path


@contextmanager
def browser_page():
    with sync_playwright() as playwright:
        launch_options = {
            "headless": HEADLESS,
            "slow_mo": SLOW_MO_MS,
        }
        if PLAYWRIGHT_BROWSER_CHANNEL:
            launch_options["channel"] = PLAYWRIGHT_BROWSER_CHANNEL
        browser = playwright.chromium.launch(**launch_options)
        context = browser.new_context(
            viewport={"width": 1440, "height": 1000},
            accept_downloads=True,
            ignore_https_errors=True,
            geolocation={"latitude": DEFAULT_LATITUDE, "longitude": DEFAULT_LONGITUDE},
        )
        context.grant_permissions(["geolocation"], origin=origin())
        page = context.new_page()
        page.set_default_timeout(DEFAULT_TIMEOUT_MS)
        try:
            yield page
        finally:
            context.close()
            browser.close()


def login_as(page: Page, role: str) -> None:
    account = ACCOUNTS[role]
    page.goto(url("/login"), wait_until="domcontentloaded")
    page.locator('input[name="email"]').fill(account["email"])
    page.locator('input[name="password"]').fill(account["password"])
    page.get_by_role("button", name=re.compile(r"^Login$", re.I)).click()
    page.wait_for_load_state("networkidle")
    expect(page).not_to_have_url(re.compile(r"/login$"))


def logout(page: Page) -> None:
    logout_button = page.locator('form[action$="/logout"] button[type="submit"]').first
    if logout_button.count() > 0:
        logout_button.click()
        page.wait_for_load_state("networkidle")


def select_first_real_option(select_locator) -> str | None:
    options = select_locator.locator("option")
    for index in range(options.count()):
        value = options.nth(index).get_attribute("value")
        if value:
            select_locator.select_option(value=value)
            return value
    return None


def assert_page_has(page: Page, text: str | re.Pattern) -> None:
    expect(page.get_by_text(text).first).to_be_visible()


def attach_dialog_acceptor(page: Page) -> None:
    page.on("dialog", lambda dialog: dialog.accept())


def print_pass(test_id: str, screenshot: Path | None = None) -> None:
    if screenshot is None:
        print(f"{test_id}: PASS")
    else:
        print(f"{test_id}: PASS | screenshot={screenshot}")

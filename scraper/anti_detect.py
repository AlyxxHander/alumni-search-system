"""
Anti-detection strategies untuk menghindari blokir Google.
"""

import random
import asyncio
import sys

from config import (
    USER_AGENTS,
    VIEWPORT_SIZES,
    MIN_DELAY_BETWEEN_QUERIES,
    MAX_DELAY_BETWEEN_QUERIES,
    MIN_DELAY_BETWEEN_ALUMNI,
    MAX_DELAY_BETWEEN_ALUMNI,
    CAPTCHA_PAUSE_SECONDS,
    PROXY_LIST,
)


def get_random_user_agent() -> str:
    """Pilih User-Agent acak dari pool."""
    return random.choice(USER_AGENTS)


def get_random_viewport() -> dict:
    """Pilih viewport size acak dari pool."""
    return random.choice(VIEWPORT_SIZES)


def get_random_proxy() -> dict | None:
    """Pilih proxy acak dari pool (jika ada)."""
    if not PROXY_LIST:
        return None
    proxy_url = random.choice(PROXY_LIST)
    return {"server": proxy_url}


async def delay_between_queries():
    """Delay acak antar query Google."""
    delay = random.uniform(MIN_DELAY_BETWEEN_QUERIES, MAX_DELAY_BETWEEN_QUERIES)
    await asyncio.sleep(delay)


async def delay_between_alumni():
    """Delay lebih panjang antar alumni."""
    delay = random.uniform(MIN_DELAY_BETWEEN_ALUMNI, MAX_DELAY_BETWEEN_ALUMNI)
    await asyncio.sleep(delay)


async def handle_captcha_detected():
    """
    Ketika CAPTCHA terdeteksi, pause sejenak lalu lanjutkan.
    Return True jika harus retry, False jika harus skip.
    """
    print(
        f"[WARN] CAPTCHA terdeteksi! Pause {CAPTCHA_PAUSE_SECONDS} detik...",
        file=sys.stderr,
    )
    await asyncio.sleep(CAPTCHA_PAUSE_SECONDS)
    return True  # retry setelah pause


def get_browser_launch_args() -> list:
    """Argumen peluncuran browser untuk menghindari deteksi otomasi."""
    return [
        "--disable-blink-features=AutomationControlled",
        "--disable-features=IsolateOrigins,site-per-process",
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
        "--disable-accelerated-2d-canvas",
        "--disable-gpu",
    ]


def configure_context_options(user_agent: str = None, viewport: dict = None) -> dict:
    """Buat opsi context browser dengan anti-detection settings."""
    ua = user_agent or get_random_user_agent()
    vp = viewport or get_random_viewport()
    proxy = get_random_proxy()

    options = {
        "user_agent": ua,
        "viewport": vp,
        "locale": "id-ID",
        "timezone_id": "Asia/Jakarta",
        "geolocation": {"latitude": -7.9666, "longitude": 112.6326},  # Malang
        "permissions": ["geolocation"],
        "java_script_enabled": True,
    }

    if proxy:
        options["proxy"] = proxy

    return options

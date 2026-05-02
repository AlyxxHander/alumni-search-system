"""
Core Google Search scraping logic menggunakan Playwright (Async version).
Membangun query per platform dan melakukan pencarian di Google/Yahoo secara paralel.
"""

import sys
import asyncio
from playwright.async_api import async_playwright, Browser, BrowserContext, Page

from config import (
    SITE_FILTERS,
    GOOGLE_SEARCH_URL,
    SESSION_ROTATION_INTERVAL,
    MAX_RESULTS_PER_QUERY,
)
from anti_detect import (
    get_random_user_agent,
    get_random_viewport,
    get_browser_launch_args,
    configure_context_options,
    delay_between_queries,
    delay_between_alumni,
    handle_captcha_detected,
)
from result_parser import parse_search_results
from playwright_stealth import Stealth


def build_queries(alumni: dict, platform: str) -> list[str]:
    """
    Bangun variasi query pencarian Google untuk alumni pada platform tertentu.
    """
    nama = alumni.get("nama_lengkap", "")
    nama_panggilan = alumni.get("nama_panggilan", "")
    kampus = alumni.get("nama_kampus", "UMM")
    prodi = alumni.get("prodi", "")
    fakultas = alumni.get("fakultas", "")
    site_filter = SITE_FILTERS.get(platform, "")

    queries = []

    if platform == "LINKEDIN":
        queries.append(f'"{nama}" {kampus} {site_filter}')
        if prodi:
            queries.append(f'"{nama}" "{prodi}" {site_filter}')
        if fakultas:
            queries.append(f'"{nama}" "{fakultas}" {kampus} {site_filter}')
        if nama_panggilan and nama_panggilan != nama:
            queries.append(f'"{nama_panggilan}" {kampus} {site_filter}')

    elif platform in ("INSTAGRAM", "FACEBOOK", "TIKTOK"):
        queries.append(f'"{nama}" {site_filter}')
        if nama_panggilan and nama_panggilan != nama:
            queries.append(f'"{nama_panggilan}" {site_filter}')
        queries.append(f'"{nama}" {kampus} {site_filter}')

    else:
        queries.append(f'"{nama}" {kampus} {prodi} {site_filter}')
        if nama_panggilan and nama_panggilan != nama:
            queries.append(f'"{nama_panggilan}" {kampus} {site_filter}')

    # Deduplikasi sambil menjaga urutan
    seen = set()
    unique_queries = []
    for q in queries:
        q_clean = " ".join(q.split())  # Normalize whitespace
        if q_clean not in seen:
            seen.add(q_clean)
            unique_queries.append(q_clean)

    return unique_queries


async def search_google(page: Page, query: str, max_retries: int = 2) -> list[dict]:
    """
    Lakukan pencarian Google untuk satu query dan return hasil organik (Async).
    """
    for attempt in range(max_retries + 1):
        try:
            # Navigate ke Yahoo Search
            url = f"{GOOGLE_SEARCH_URL}?p={query}"
            await page.goto(url, wait_until="domcontentloaded", timeout=30000)

            # Smart Wait: Tunggu hingga elemen hasil pencarian muncul (maks 5 detik)
            try:
                await page.wait_for_selector("div.algo-sr, div.algo, #results", timeout=5000)
            except Exception:
                pass

            # Cek CAPTCHA
            if await _is_captcha_page(page):
                if attempt < max_retries:
                    should_retry = await handle_captcha_detected()
                    if should_retry:
                        continue
                print(
                    f"[ERROR] CAPTCHA masih ada setelah {max_retries} retry untuk query: {query}",
                    file=sys.stderr,
                )
                return []

            # Handle consent page (jika muncul)
            await _handle_consent(page)

            # Parse hasil
            results = await parse_search_results(page, MAX_RESULTS_PER_QUERY)
            return results

        except Exception as e:
            print(f"[ERROR] Search error untuk '{query}': {e}", file=sys.stderr)
            if attempt < max_retries:
                await delay_between_queries()
                continue
            return []

    return []


async def _is_captcha_page(page: Page) -> bool:
    """Deteksi apakah halaman saat ini adalah CAPTCHA Google."""
    try:
        content = await page.content()
        captcha_indicators = [
            "unusual traffic",
            "captcha",
            "recaptcha",
            "sorry/index",
            "detected unusual traffic",
            "lalu lintas tidak biasa",
            "security check",
            "redirecting",
        ]
        content_lower = content.lower()
        return any(indicator in content_lower for indicator in captcha_indicators)
    except Exception:
        return False


async def _handle_consent(page: Page):
    """Handle Google consent dialog jika muncul."""
    try:
        consent_btn = await page.query_selector(
            'button[id="L2AGLb"], button[aria-label="Setuju"], '
            'button[aria-label="Accept all"], form[action*="consent"] button'
        )
        if consent_btn:
            await consent_btn.click()
            await asyncio.sleep(1.5)
    except Exception:
        pass


async def scrape_alumni_platforms(
    alumni: dict,
    platforms: list[str],
    page: Page,
    max_results_per_platform: int = 3,
) -> dict:
    """
    Scrape semua platform yang diminta untuk satu alumni (Async).
    """
    platform_results = {}

    for platform in platforms:
        if platform not in SITE_FILTERS:
            continue

        queries = build_queries(alumni, platform)
        all_results = []
        seen_urls = set()

        for query in queries:
            results = await search_google(page, query)

            for r in results:
                url = r.get("url_profil", "")
                if url and url not in seen_urls:
                    seen_urls.add(url)
                    all_results.append(r)

            # Fase 3: Early Exit
            if len(all_results) >= max_results_per_platform:
                break

            await delay_between_queries()

        # Simpan query yang digunakan
        query_str = " | ".join(queries)
        for r in all_results:
            r["query_digunakan"] = query_str

        platform_results[platform] = all_results[:max_results_per_platform]

    return platform_results


class GoogleScraper:
    """
    Manager class untuk Playwright browser lifecycle (Async).
    """

    def __init__(self):
        self.playwright = None
        self.browser: Browser | None = None
        self.search_count = 0
        self.concurrency_limit = 2  # Dikurangi ke 2 agar lebih aman dari deteksi Yahoo

    async def start(self):
        """Mulai Playwright dan buka browser."""
        self.playwright = await async_playwright().start()
        self.browser = await self.playwright.chromium.launch(
            headless=True,
            args=get_browser_launch_args(),
        )
        print(f"[INFO] Browser Playwright (Async) dimulai. Concurrency: {self.concurrency_limit}", file=sys.stderr)

    async def _create_context_with_stealth(self) -> BrowserContext:
        """Buat context baru dengan setting stealth."""
        options = configure_context_options()
        context = await self.browser.new_context(**options)
        
        # Resource Blocking di level context (opsional, bisa di level page)
        return context

    async def scrape_single_alumni(self, alumni: dict, platforms: list[str], max_results: int, semaphore: asyncio.Semaphore):
        """Worker function untuk satu alumni."""
        async with semaphore:
            context = await self._create_context_with_stealth()
            page = await context.new_page()
            
            # Resource Blocking
            await page.route("**/*", lambda route: route.abort() if route.request.resource_type in ["image", "stylesheet", "media", "font"] else route.continue_())
            
            # Stealth
            await Stealth().apply_stealth_async(page)
            
            try:
                results = await scrape_alumni_platforms(alumni, platforms, page, max_results)
                return str(alumni.get("id")), results
            except Exception as e:
                print(f"[ERROR] Gagal scrape alumni {alumni.get('nama_lengkap')}: {e}", file=sys.stderr)
                return str(alumni.get("id")), {}
            finally:
                await context.close()

    async def scrape_batch(
        self,
        alumni_list: list[dict],
        platforms: list[str],
        max_results_per_platform: int = 3,
    ) -> dict:
        """
        Scrape Google untuk batch alumni secara PARALEL.
        """
        semaphore = asyncio.Semaphore(self.concurrency_limit)
        tasks = []

        for alumni in alumni_list:
            tasks.append(self.scrape_single_alumni(alumni, platforms, max_results_per_platform, semaphore))

        print(f"[INFO] Menjalankan {len(tasks)} tasks secara paralel...", file=sys.stderr)
        
        # Jalankan semua task
        task_results = await asyncio.gather(*tasks)
        
        # Gabungkan hasil
        final_results = {}
        for alumni_id, platform_results in task_results:
            final_results[alumni_id] = platform_results

        return final_results

    async def stop(self):
        """Tutup browser dan Playwright."""
        try:
            if self.browser:
                await self.browser.close()
            if self.playwright:
                await self.playwright.stop()
        except Exception:
            pass
        print("[INFO] Browser Playwright ditutup.", file=sys.stderr)

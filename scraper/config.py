"""
Konfigurasi untuk Google Scraper menggunakan Playwright.
"""

# Site filters per platform (sama seperti SumberPelacakan::siteFilter() di PHP)
SITE_FILTERS = {
    "LINKEDIN": "site:linkedin.com/in",
    "INSTAGRAM": "site:instagram.com",
    "FACEBOOK": "site:facebook.com",
    "TIKTOK": "site:tiktok.com",
}

# Delay settings (dalam detik)
MIN_DELAY_BETWEEN_QUERIES = 3      # Minimum delay antar query Google
MAX_DELAY_BETWEEN_QUERIES = 7      # Maximum delay antar query Google
MIN_DELAY_BETWEEN_ALUMNI = 10      # Minimum delay antar alumni
MAX_DELAY_BETWEEN_ALUMNI = 20      # Maximum delay antar alumni
CAPTCHA_PAUSE_SECONDS = 300        # Pause 5 menit jika kena CAPTCHA

# Browser settings
SESSION_ROTATION_INTERVAL = 25     # Buat context baru setiap N search
MAX_RESULTS_PER_QUERY = 3          # Ambil maksimal N hasil per query

# Yahoo search URL (Bypassing Google CAPTCHA)
GOOGLE_SEARCH_URL = "https://search.yahoo.com/search"

# User-Agent pool (real browser UA strings)
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
    "Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
]

# Viewport sizes pool
VIEWPORT_SIZES = [
    {"width": 1366, "height": 768},
    {"width": 1440, "height": 900},
    {"width": 1536, "height": 864},
    {"width": 1920, "height": 1080},
    {"width": 1280, "height": 720},
    {"width": 1600, "height": 900},
    {"width": 1680, "height": 1050},
]

# Proxy settings (Opsional)
# Format: "http://user:pass@host:port" atau "http://host:port"
PROXY_LIST = [
    # "http://proxy1.example.com:8080",
    # "http://user:pass@proxy2.example.com:8080",
]

#!/usr/bin/env python3
"""
Entry point untuk Google Scraper Service (Async version).
"""

import json
import sys
import os
import asyncio

# Tambahkan direktori scraper ke path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from google_search import GoogleScraper

# Force stdout to be utf-8 on Windows
if sys.stdout.encoding.lower() != 'utf-8':
    sys.stdout = open(sys.stdout.fileno(), mode='w', encoding='utf-8', buffering=1)

async def run_scraper():
    """Fungsi utama asinkron."""
    errors = []

    # Baca input dari stdin
    try:
        # Gunakan sys.stdin.read() secara sinkron karena data kecil biasanya sudah ada di buffer
        input_str = sys.stdin.read()
        if not input_str:
            return
        input_data = json.loads(input_str)
    except json.JSONDecodeError as e:
        output = {"results": {}, "errors": [f"Invalid JSON input: {str(e)}"]}
        print(json.dumps(output, ensure_ascii=False))
        return

    alumni_list = input_data.get("alumni_list", [])
    platforms = input_data.get("platforms", ["LINKEDIN", "INSTAGRAM", "FACEBOOK", "TIKTOK"])
    max_results = input_data.get("max_results_per_platform", 3)

    if not alumni_list:
        output = {"results": {}, "errors": ["alumni_list kosong"]}
        print(json.dumps(output, ensure_ascii=False))
        return

    print(
        f"[INFO] Memulai scraping {len(alumni_list)} alumni secara paralel, "
        f"platforms: {platforms}, max_results: {max_results}",
        file=sys.stderr,
    )

    # Inisialisasi scraper
    scraper = GoogleScraper()

    try:
        await scraper.start()
        results = await scraper.scrape_batch(alumni_list, platforms, max_results)
    except Exception as e:
        import traceback
        traceback.print_exc(file=sys.stderr)
        errors.append(f"Scraper error: {str(e)}")
        results = {}
    finally:
        await scraper.stop()

    # Output JSON ke stdout
    output = {"results": results, "errors": errors}
    print(json.dumps(output, ensure_ascii=False))


if __name__ == "__main__":
    asyncio.run(run_scraper())

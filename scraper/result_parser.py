"""
Parser untuk mengekstrak hasil pencarian Google dari halaman HTML (Async version).
"""

import re

# Daftar kota Indonesia untuk ekstraksi lokasi
INDONESIAN_CITIES = [
    "Jakarta", "Surabaya", "Bandung", "Malang", "Yogyakarta", "Semarang",
    "Medan", "Makassar", "Bali", "Denpasar", "Bekasi", "Tangerang", "Depok",
    "Bogor", "Palembang", "Balikpapan", "Samarinda", "Manado", "Padang",
    "Batam", "Pekanbaru", "Banjarmasin", "Pontianak", "Mataram", "Kupang",
    "Lampung", "Solo", "Surakarta", "Cirebon", "Mojokerto", "Sidoarjo",
    "Gresik", "Kediri", "Jember", "Pasuruan", "Probolinggo", "Batu",
]


def extract_instansi(snippet: str) -> str | None:
    """Ekstrak nama instansi dari snippet (simple extraction)."""
    if not snippet:
        return None
    match = re.search(r"(?:at|di|@)\s+([^,.\-]+)", snippet, re.IGNORECASE)
    if match:
        return match.group(1).strip()
    return None


def extract_lokasi(snippet: str) -> str | None:
    """Ekstrak lokasi/kota dari snippet berdasarkan daftar kota Indonesia."""
    if not snippet:
        return None
    for city in INDONESIAN_CITIES:
        if city.lower() in snippet.lower():
            return city
    return None


async def parse_search_results(page, max_results: int = 3) -> list[dict]:
    """
    Parse hasil pencarian organik dari halaman Google yang sudah di-load (Async).
    """
    results = []

    try:
        # Selector untuk hasil pencarian organik Yahoo
        search_items = await page.query_selector_all("div.algo-sr")

        if not search_items:
            # Fallback: coba selector alternatif Yahoo
            search_items = await page.query_selector_all("div.algo")

        for item in search_items[:max_results]:
            try:
                result = await _parse_single_result(item)
                if result and result.get("url_profil"):
                    results.append(result)
            except Exception as e:
                continue

    except Exception as e:
        pass

    return results


async def _parse_single_result(item) -> dict | None:
    """Parse satu item hasil pencarian menjadi dict (Async)."""
    result = {
        "judul_profil": None,
        "url_profil": None,
        "snippet": None,
        "instansi": None,
        "lokasi": None,
        "foto_url": None,
    }

    # Ekstrak link dan judul (Yahoo)
    link_el = await item.query_selector("h3.title a")
    if not link_el:
        link_el = await item.query_selector("a[href]")
        
    if link_el:
        href = await link_el.get_attribute("href")
        if href and href.startswith("http"):
            result["url_profil"] = href
            
    if link_el:
        inner_text = await link_el.inner_text()
        result["judul_profil"] = inner_text.strip() if inner_text else None

    # Snippet: paragraf deskripsi (Yahoo)
    snippet_el = await item.query_selector("div.compText")
    if not snippet_el:
        snippet_el = await item.query_selector("div.compTitle + div")
        
    if snippet_el:
        inner_text = await snippet_el.inner_text()
        result["snippet"] = inner_text.strip() if inner_text else None
    else:
        # Fallback: ambil semua text setelah h3
        all_divs = await item.query_selector_all("div")
        for div in all_divs:
            text = await div.inner_text()
            if text and len(text.strip()) > 40:  # Hanya snippet yang cukup panjang
                result["snippet"] = text.strip()
                break

    # Ekstrak instansi dan lokasi dari snippet
    if result["snippet"]:
        result["instansi"] = extract_instansi(result["snippet"])
        result["lokasi"] = extract_lokasi(result["snippet"])

    # Foto URL (jika ada thumbnail)
    img_el = await item.query_selector("img[src^='http']")
    if img_el:
        result["foto_url"] = await img_el.get_attribute("src")

    # Validasi: harus punya URL minimal
    if not result["url_profil"]:
        return None

    return result

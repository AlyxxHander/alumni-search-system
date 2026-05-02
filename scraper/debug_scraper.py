import asyncio
import sys
import os
from playwright.async_api import async_playwright
from playwright_stealth import Stealth

# Mock config and helper functions
GOOGLE_SEARCH_URL = "https://search.yahoo.com/search"

async def debug_scrape(block_resources=True):
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        # Use a real user agent
        ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
        context = await browser.new_context(user_agent=ua)
        page = await context.new_page()
        
        if block_resources:
            print("Resource blocking is ENABLED")
            await page.route("**/*", lambda route: route.abort() if route.request.resource_type in ["image", "stylesheet", "media", "font"] else route.continue_())
        else:
            print("Resource blocking is DISABLED")
            
        await Stealth().apply_stealth_async(page)
        
        query = "Adi Muhammad site:linkedin.com/in"
        url = f"{GOOGLE_SEARCH_URL}?p={query}"
        
        print(f"Navigating to: {url}")
        try:
            await page.goto(url, wait_until="domcontentloaded", timeout=30000)
            
            # Wait a bit for JS to run
            await asyncio.sleep(3)
            
            # Save screenshot
            suffix = "blocked" if block_resources else "full"
            screenshot_path = f"debug_screenshot_{suffix}.png"
            await page.screenshot(path=screenshot_path)
            print(f"Screenshot saved to {screenshot_path}")
            
            # Save content
            content = await page.content()
            with open(f"debug_page_{suffix}.html", "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Page content saved to debug_page_{suffix}.html")
            
            # Check for results
            results = await page.query_selector_all("div.algo-sr, div.algo")
            print(f"Found {len(results)} result elements with our selectors.")
            
            if len(results) == 0:
                print("Checking for alternative selectors...")
                # Search for typical Yahoo result containers
                items = await page.query_selector_all("li div.compTitle")
                print(f"Found {len(items)} items with li div.compTitle")
                
                if "unusual traffic" in content.lower() or "captcha" in content.lower():
                    print("!!! CAPTCHA OR BOT DETECTION DETECTED !!!")
                
        except Exception as e:
            print(f"Error during debug scrape: {e}")
        finally:
            await browser.close()

async def main():
    print("--- Test 1: With Resource Blocking ---")
    await debug_scrape(block_resources=True)
    print("\n--- Test 2: Without Resource Blocking ---")
    await debug_scrape(block_resources=False)

if __name__ == "__main__":
    asyncio.run(main())

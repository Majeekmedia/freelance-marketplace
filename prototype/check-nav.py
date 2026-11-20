"""Guard: every public page must appear in BOTH the desktop nav and the mobile drawer.

Adding a page to only one menu is an invisible defect (the page becomes
unreachable for half the audience), so it is checked rather than trusted.
"""
import re
import sys
from pathlib import Path

HTML = Path(__file__).resolve().parent / "landing.html"


def links_in(block: str) -> list[str]:
    out = []
    for raw in re.findall(r"<a[^>]*>(.*?)</a>", block, re.S):
        text = re.sub(r"<[^>]+>", " ", raw)
        text = re.sub(r"\s+", " ", text).strip()
        if text:
            out.append(text)
    return out


def main() -> int:
    html = HTML.read_text(encoding="utf-8")

    desktop_m = re.search(r'<nav class="nav"[^>]*>(.*?)</nav>', html, re.S)
    mobile_m = re.search(r'aria-label="Mobile">(.*?)</nav>', html, re.S)

    if not desktop_m or not mobile_m:
        print("FAIL: could not locate both navs")
        return 1

    desktop = links_in(desktop_m.group(1))
    mobile = links_in(mobile_m.group(1))

    print(f"desktop : {desktop}")
    print(f"mobile  : {mobile}")

    missing = [x for x in desktop if x not in mobile]
    extra = [x for x in mobile if x not in desktop]

    ok = True
    if missing:
        print(f"FAIL: in desktop nav but NOT in drawer -> {missing}")
        ok = False
    if extra:
        print(f"NOTE: only in drawer -> {extra}")

    # the hamburger must exist and be wired to the drawer
    if 'aria-controls="mobile-menu"' not in html:
        print("FAIL: toggle is not wired via aria-controls")
        ok = False
    if 'aria-expanded' not in html:
        print("FAIL: toggle does not expose aria-expanded")
        ok = False
    if "matchMedia" not in html:
        print("FAIL: no resize handler; a stale open menu is possible")
        ok = False

    print("PASS: navigation parity holds" if ok else "FAILED")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())

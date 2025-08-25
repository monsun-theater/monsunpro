"""Because php please static:warm runs into rate limits, this script only warms specific urls."""

#!/usr/bin/env python3

# Add urls to warm here
# Examples: ["", "page", "sub/page"]
URLS = [
    "",
    "spielplan",
    "programmhefte",
    "aussicht-festival",
    "karteninfo",
    "repertoire",
    "newsletter",
    "presse",
    "kuenstler-innen",
    "archiv",
    "anfahrt",
    "agb",
    "datenschutz",
    "impressum",
    "kontakt"
]

import os
import urllib3
from concurrent.futures import ThreadPoolExecutor

if not "APP_URL" in os.environ:
    raise RuntimeError('Missing environment var "APP_URL"')

APP_URL = os.environ["APP_URL"]

# set up a global pool manager with sane timeouts
http = urllib3.PoolManager(
    timeout=urllib3.util.Timeout(connect=5.0,
read=20.0),
retries=False,
)

def refresh(path: str):
    path = path.strip("/")
    file = f"../public/static/{path}_.html"
    print(f"Refreshing /{path}")
    try:
        if os.path.exists(file):
            os.remove(file)

        url = f"{APP_URL}/{path}"
        r = http.request("GET",
url)

        if r.status >= 200 and r.status < 300:
            print(f"Done /{path}")
        else:
            print(f"FAILED /{path} → HTTP {r.status}")
    except Exception as e:
        print(f"FAILED /{path} → {e}")

with ThreadPoolExecutor(max_workers=10) as ex:
    ex.map(refresh, URLS)
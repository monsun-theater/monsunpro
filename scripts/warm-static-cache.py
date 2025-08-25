"""Because php please static:warm runs into rate limits, this script only warms specific urls."""

#!/usr/bin/env python3

# paths to crawl for paths to refresh ()
CRAWL_PATHS = [
    ""
]

# Add urls to warm here
# Examples: ["", "page", "sub/page"]
PATHS = [
# Should be crawled by crawl path ""
#    "spielplan",
#    "programmhefte",
#    "aussicht-festival",
#    "karteninfo",
#    "repertoire",
#    "newsletter",
#    "presse",
#    "kuenstler-innen",
#    "archiv",
#    "anfahrt",
#    "agb",
#    "datenschutz",
#    "impressum",
#    "kontakt"
]

import os
import re
from typing import Optional
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

def refresh(path: str, crawl=False, tpe: Optional[ThreadPoolExecutor] = None):
    path = path.strip("/")
    file = f"../public/static/{path}_.html"
    print(f"Refreshing /{path}")
    try:
        if os.path.exists(file):
            os.remove(file)

        url = f"{APP_URL}/{path}"
        r = http.request("GET",url)

        if r.status >= 200 and r.status < 300:
            print(f"Done /{path}")

            if crawl and tpe:
                html = r.data.decode()
                pattern = re.compile(r'href="(/[^"]+)"')
                crawled_paths = list(pattern.findall(html))

                print(f"Found {len(crawled_paths)} same-site urls at /{path}.")

                for crawled_path in crawled_paths:
                    tpe.submit(refresh, path=crawled_path)

        else:
            print(f"FAILED /{path} → HTTP {r.status}")
    except Exception as e:
        print(f"FAILED /{path} → {e}")

with ThreadPoolExecutor(max_workers=10) as ex:
    futs = []
    for crawl_path in CRAWL_PATHS:
        futs.append(ex.submit(refresh, path=crawl_path, crawl=True, tpe=ex))
    for fut in futs:
        fut.result()
    ex.map(refresh, PATHS)
#!/bin/bash
# Read-only checks. Does not create impressions, clicks or accounts.
set -uo pipefail
release_dir="$(cd "$(dirname "$0")/.." && pwd)"
php_binary="${PHP_BINARY:-/opt/cpanel/ea-php84/root/usr/bin/php}"
status=0
cd "$release_dir" || exit 1
"$php_binary" artisan product:health || status=1
curl --fail --silent --show-error --max-time 15 https://reklam.biz/en > /dev/null || status=1
api_status=$(curl --silent --show-error --max-time 15 -o /dev/null -w '%{http_code}' -H 'Accept: application/json' https://api.reklam.biz/api/auth/user) || status=1
[ "$api_status" = 401 ] || status=1
printf '%s product-monitor status=%s\n' "$(date -u +%FT%TZ)" "$status"
exit "$status"

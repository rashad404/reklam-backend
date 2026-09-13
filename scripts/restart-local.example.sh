#!/bin/bash
# Local development only. Refuse to stop processes outside this checkout.
set -euo pipefail
project_dir="$(cd "$(dirname "$0")/../.." && pwd)"
mkdir -p "$project_dir/.local"
for entry in '8059 backend' '3059 frontend'; do
  read -r port service <<< "$entry"
  for pid in $(lsof -tiTCP:"$port" -sTCP:LISTEN 2>/dev/null | sort -u); do
    process_dir=$(lsof -a -p "$pid" -d cwd -Fn 2>/dev/null | sed -n 's/^n//p')
    case "$process_dir" in
      "$project_dir/$service"|"$project_dir/$service/"*) kill -TERM "$pid" ;;
      *) echo "Port $port belongs to another process. No services started." >&2; exit 1 ;;
    esac
  done
done
sleep 2
(cd "$project_dir/backend" && exec php artisan serve --host=127.0.0.1 --port=8059) > "$project_dir/.local/backend.log" 2>&1 &
(cd "$project_dir/frontend" && exec npm run dev -- --hostname 127.0.0.1 --port 3059) > "$project_dir/.local/frontend.log" 2>&1 &
echo 'Frontend: http://localhost:3059'
echo 'API: http://localhost:8059/api'

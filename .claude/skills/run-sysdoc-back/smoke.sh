#!/usr/bin/env bash
# Smoke test for sysdoc_back Laravel API.
# Run from sysdoc_back/ directory.
# Usage: bash .claude/skills/run-sysdoc-back/smoke.sh [port]
set -e

PORT=${1:-8000}
BASE="http://localhost:$PORT"
CPF="${SMOKE_CPF:-08449222699}"
PASS="${SMOKE_PASS:-12345678}"

ok()  { echo "  OK  $1"; }
fail(){ echo "FAIL  $1"; exit 1; }

check_status() {
  local label=$1 url=$2 method=${3:-GET} data=${4:-}
  local args=(-s -o /dev/null -w "%{http_code}" -H "Accept: application/json")
  [ -n "$AUTH_TOKEN" ] && args+=(-H "Authorization: Bearer $AUTH_TOKEN")
  [ "$method" = "POST" ] && args+=(-X POST -H "Content-Type: application/json" -d "$data")
  local code
  code=$(curl "${args[@]}" "$url")
  if [ "$code" -ge 200 ] && [ "$code" -lt 400 ]; then ok "$label ($code)"; else fail "$label ($code)"; fi
}

echo "=== sysdoc_back smoke test — $BASE ==="

# 1. Server up
check_status "Server reachable" "$BASE"

# 2. Login
RESP=$(curl -s -X POST -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"cpf\":\"$CPF\",\"password\":\"$PASS\"}" "$BASE/api/login")
AUTH_TOKEN=$(echo "$RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$AUTH_TOKEN" ]; then
  echo "  LOGIN RESPONSE: $RESP"
  fail "Login (no token returned)"
fi
ok "Login — token ${#AUTH_TOKEN} chars"

# 3. Authenticated endpoints
check_status "GET /api/user"       "$BASE/api/user"
check_status "GET /api/clients"    "$BASE/api/clients"
check_status "GET /api/users"      "$BASE/api/users"

# 4. Public endpoints (no auth)
AUTH_TOKEN="" check_status "GET /api/public/pharmacy/medicines/daily"             "$BASE/api/public/pharmacy/medicines/daily"
AUTH_TOKEN="" check_status "GET /api/public/pharmacy/medicines/monthly-acquisitions" "$BASE/api/public/pharmacy/medicines/monthly-acquisitions"

echo ""
echo "All checks passed."

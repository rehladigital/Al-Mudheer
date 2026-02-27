#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   SITE_URL="https://pm.example.com" bash scripts/deploy-checklist.sh
# Optional authenticated check:
#   LOGIN_USERNAME="user@example.com" LOGIN_PASSWORD="secret" SITE_URL="https://pm.example.com" bash scripts/deploy-checklist.sh

SITE_URL="${SITE_URL:-${1:-}}"
if [[ -z "${SITE_URL}" ]]; then
  echo "ERROR: Set SITE_URL or pass it as first argument."
  echo "Example: SITE_URL=https://pm.example.com bash scripts/deploy-checklist.sh"
  exit 1
fi

SITE_URL="${SITE_URL%/}"
TMP_DIR="$(mktemp -d)"
COOKIE_JAR="${TMP_DIR}/cookie.txt"

cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

pass() { echo "PASS  $*"; }
fail() { echo "FAIL  $*" >&2; exit 1; }

request_code() {
  local url="$1"
  curl -sS -o /dev/null -w "%{http_code}" "${url}"
}

request_headers() {
  local url="$1"
  curl -sS -D - -o /dev/null "${url}"
}

expect_code() {
  local url="$1"
  local expected="$2"
  local actual
  actual="$(request_code "${url}")"
  [[ "${actual}" == "${expected}" ]] || fail "${url} expected ${expected}, got ${actual}"
  pass "${url} -> ${actual}"
}

expect_redirect_contains() {
  local url="$1"
  local needle="$2"
  local headers
  headers="$(request_headers "${url}")"
  echo "${headers}" | rg -qi "^HTTP/.* 30[12]" || fail "${url} expected redirect status (301/302)"
  echo "${headers}" | rg -qi "^location: .*${needle}" || fail "${url} redirect location missing '${needle}'"
  pass "${url} redirects to '${needle}'"
}

echo "==> Running deployment checklist for ${SITE_URL}"

expect_code "${SITE_URL}/auth/login?advanced=1" "200"
expect_code "${SITE_URL}/dist/css/main.2.0.0.min.css" "200"
expect_code "${SITE_URL}/dist/js/compiled-frameworks.2.0.0.min.js" "200"

OIDC_EXPECT_CONTAINS="${OIDC_EXPECT_CONTAINS:-login.microsoftonline.com}"
expect_redirect_contains "${SITE_URL}/oidc/login" "${OIDC_EXPECT_CONTAINS}"

if [[ -n "${LOGIN_USERNAME:-}" && -n "${LOGIN_PASSWORD:-}" ]]; then
  echo "==> Running authenticated dashboard check"

  curl -sS -c "${COOKIE_JAR}" "${SITE_URL}/auth/login?advanced=1" -o /dev/null
  LOGIN_HEADERS="$(curl -sS -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -D - -o /dev/null \
    -X POST "${SITE_URL}/auth/login" \
    --data-urlencode "username=${LOGIN_USERNAME}" \
    --data-urlencode "password=${LOGIN_PASSWORD}" \
    --data-urlencode "redirectUrl=${SITE_URL}/dashboard/home")"

  echo "${LOGIN_HEADERS}" | rg -qi "^HTTP/.* 30[23]" || fail "login POST expected 302/303"
  echo "${LOGIN_HEADERS}" | rg -qi "^location: ${SITE_URL}/dashboard/home" || fail "login POST did not redirect to dashboard/home"
  pass "login POST redirects to dashboard/home"

  DASH_CODE="$(curl -sS -b "${COOKIE_JAR}" -o /dev/null -w "%{http_code}" "${SITE_URL}/dashboard/home")"
  [[ "${DASH_CODE}" == "200" ]] || fail "/dashboard/home expected 200 after login, got ${DASH_CODE}"
  pass "/dashboard/home -> ${DASH_CODE} (authenticated)"
else
  echo "==> Skipping authenticated check (set LOGIN_USERNAME + LOGIN_PASSWORD to enable)"
fi

echo "==> Checklist completed successfully"

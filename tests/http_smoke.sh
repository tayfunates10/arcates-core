#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE_URL="http://127.0.0.1:8088"
COOKIE_JAR="${TMPDIR:-/tmp}/arcates-http-smoke.cookies"
SERVER_LOG="$ROOT/logs/http-smoke-server.log"

cd "$ROOT"
mkdir -p logs uploads
rm -f "$COOKIE_JAR" install/install.lock install/install.running uploads/http-smoke.php

php -r '
$pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "root", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("DROP DATABASE IF EXISTS arcates_http");
$pdo->exec("CREATE DATABASE arcates_http CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
'
cp tests/fixtures/config.http.php config.php

php -S 127.0.0.1:8088 -t public scripts/dev_router.php >"$SERVER_LOG" 2>&1 &
SERVER_PID=$!
cleanup() {
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
    rm -f "$COOKIE_JAR" uploads/http-smoke.php
}
trap cleanup EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$BASE_URL/install" -o /tmp/arcates-install.html; then
        break
    fi
    sleep 1
done

if ! kill -0 "$SERVER_PID" 2>/dev/null; then
    cat "$SERVER_LOG" >&2
    exit 1
fi

extract_csrf() {
    php -r '
$html = file_get_contents($argv[1]);
if (!preg_match("/name=\"_csrf\" value=\"([^\"]+)\"/", $html, $m)) {
    fwrite(STDERR, "CSRF token bulunamadı\n");
    exit(1);
}
echo html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, "UTF-8");
' "$1"
}

assert_code() {
    local expected="$1"
    local actual="$2"
    local label="$3"
    if [[ "$actual" != "$expected" ]]; then
        echo "$label: HTTP $expected bekleniyordu, $actual geldi" >&2
        cat "$SERVER_LOG" >&2
        exit 1
    fi
}

csrf="$(extract_csrf /tmp/arcates-install.html)"
install_code="$(curl -sS -o /tmp/arcates-install-post.html -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/install" \
    --data-urlencode "_csrf=$csrf" \
    --data-urlencode 'email=admin-smoke@example.com' \
    --data-urlencode 'password=SmokePass123!')"
assert_code 200 "$install_code" 'Kurulum POST'
grep -q 'Kurulum tamamlandı' /tmp/arcates-install-post.html
test -f install/install.lock

locked_code="$(curl -sS -o /tmp/arcates-install-locked.html -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/install")"
assert_code 404 "$locked_code" 'İkinci kurulum GET'

auth_code="$(curl -sS -o /tmp/arcates-login.html -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/yonetim-ci/giris")"
assert_code 200 "$auth_code" 'Admin giriş formu'
login_csrf="$(extract_csrf /tmp/arcates-login.html)"
login_code="$(curl -sS -o /tmp/arcates-login-post.html -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/yonetim-ci/giris" \
    --data-urlencode "_csrf=$login_csrf" \
    --data-urlencode 'email=admin-smoke@example.com' \
    --data-urlencode 'password=SmokePass123!')"
assert_code 302 "$login_code" 'Admin login POST'

admin_code="$(curl -sS -o /tmp/arcates-admin.html -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/yonetim-ci")"
assert_code 200 "$admin_code" 'Admin panel'
grep -q 'admin-smoke@example.com' /tmp/arcates-admin.html

home_code="$(curl -sS -o /tmp/arcates-home.html -w '%{http_code}' "$BASE_URL/")"
assert_code 200 "$home_code" 'Ana sayfa'
grep -q 'Sistem hazır.' /tmp/arcates-home.html

asset_code="$(curl -sS -o /tmp/arcates-theme.css -w '%{http_code}' "$BASE_URL/assets/css/theme.css")"
assert_code 200 "$asset_code" 'Statik CSS'
grep -q 'container' /tmp/arcates-theme.css

head_code="$(curl -sS -o /tmp/arcates-head.body -w '%{http_code}' -X HEAD "$BASE_URL/")"
assert_code 200 "$head_code" 'HEAD ana sayfa'
test ! -s /tmp/arcates-head.body

options_code="$(curl -sS -o /dev/null -D /tmp/arcates-options.headers -w '%{http_code}' -X OPTIONS "$BASE_URL/")"
assert_code 204 "$options_code" 'OPTIONS'
grep -qi '^Allow: .*HEAD.*OPTIONS' /tmp/arcates-options.headers

missing_code="$(curl -sS -o /tmp/arcates-missing.html -D /tmp/arcates-missing.headers -w '%{http_code}' "$BASE_URL/en/does-not-exist")"
assert_code 404 "$missing_code" 'Yerelleştirilmiş 404'
grep -q '<html lang="en"' /tmp/arcates-missing.html
grep -q 'Page not found' /tmp/arcates-missing.html
grep -qi '^Content-Type: text/html; charset=UTF-8' /tmp/arcates-missing.headers

printf '<?php echo "must-not-run"; ?>' > uploads/http-smoke.php
upload_code="$(curl -sS -o /tmp/arcates-upload-blocked.html -w '%{http_code}' "$BASE_URL/uploads/http-smoke.php")"
assert_code 403 "$upload_code" 'Uploads PHP engeli'
if grep -q 'must-not-run' /tmp/arcates-upload-blocked.html; then
    echo 'Uploads PHP içeriği çalıştı veya sızdı.' >&2
    exit 1
fi

logout_csrf="$(extract_csrf /tmp/arcates-admin.html)"
logout_code="$(curl -sS -o /dev/null -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/yonetim-ci/cikis" \
    --data-urlencode "_csrf=$logout_csrf")"
assert_code 302 "$logout_code" 'Admin logout'

after_logout_code="$(curl -sS -o /dev/null -w '%{http_code}' \
    -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/yonetim-ci")"
assert_code 302 "$after_logout_code" 'Logout sonrası admin koruması'

echo 'HTTP smoke: OK'

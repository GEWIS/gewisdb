#!/usr/bin/env bash
#
# Shared plumbing for the golden capture and verification scripts.
#
# Everything here is deliberately black-box: the goldens have to be capturable from both the current Laminas stack and
# the Symfony one that replaces it, so nothing may reach into application internals. The only couplings are HTTP, psql,
# and a console entrypoint — and the console entrypoint is abstracted below because that is the one thing that does
# change between the two stacks.

set -euo pipefail

BASE_URL="${GOLDENS_BASE_URL:-http://localhost:9725}"
DB_DEFAULT="${GOLDENS_DB_DEFAULT:-gewisdb}"
DB_REPORT="${GOLDENS_DB_REPORT:-gewisdb_report}"
DB_USER="${GOLDENS_DB_USER:-gewisdb}"

# The API version the versioned endpoints negotiate against. Bump only alongside a deliberate contract change.
API_VERSION="${GOLDENS_API_VERSION:-4.3.3}"
ACCEPT_VERSIONED="application/vnd.gewis.gewisdb+json;version=${API_VERSION}"

# --- stack abstraction -------------------------------------------------------
# The migration replaces `./web` (laminas-cli) and `./orm` (doctrine-orm-module) with `bin/console`. Detect which one
# we are talking to so the same capture produces comparable output on both sides.

stack() {
    if docker compose exec -T web test -f bin/console 2>/dev/null; then
        echo symfony
    else
        echo laminas
    fi
}

# Run an application console command inside the web container.
#
# The user differs with the stack as well as the entrypoint: the Laminas image runs PHP as www-data, the FrankenPHP
# image as the unprivileged user that owns /app.
console() {
    if [ "$(stack)" = symfony ]; then
        docker compose exec -T web bin/console "$@"
        return
    fi

    docker compose exec -u www-data -T web ./web "$@"
}

# Dump the schema DDL that the ORM mapping implies, for one entity manager ("default" or "report").
#
# This is the strongest single gate in the migration: an empty diff here means production tables are already correct
# and the existing migrations stay valid, so no data migration is needed at cutover.
schema_dump() {
    local em="$1"

    # Sorted, because the emission ORDER of the DDL is not something we want to assert. Under Laminas both entity
    # managers are one mapping each; under Symfony the default manager is split into seven per-domain mappings, which
    # reorders the statements without changing a single one of them. Sorting keeps the golden a fingerprint of the
    # schema rather than of the mapping configuration. Nothing ever executes this file, so order is free.
    if [ "$(stack)" = symfony ]; then
        docker compose exec -T web bin/console doctrine:schema:create --dump-sql --em="${em}" 2>/dev/null \
            | LC_ALL=C sort
        return
    fi

    # `./orm` picks its entity manager from EM_ALIAS, not from --object-manager: see the getenv('EM_ALIAS') block in
    # the entrypoint. The migrations commands DoctrineORMModule registers do honour --object-manager (which is why
    # `make migrate` works), but orm:schema-tool:create resolves the plain `em` console helper, so passing the flag
    # here silently dumped orm_default twice. The guard in capture.sh exists to stop that returning.
    local alias="orm_default"
    [ "${em}" = report ] && alias="orm_report"
    docker compose exec -e "EM_ALIAS=${alias}" -u www-data -T web \
        ./orm orm:schema-tool:create --dump-sql 2>/dev/null \
        | LC_ALL=C sort
}

# --- database ----------------------------------------------------------------

psql_q() {
    docker compose exec -T postgresql psql -U "${DB_USER}" -d "$1" -tAc "$2"
}

# Schema-only dump, used together with pg_dump_data to freeze the seed as the golden set's *input*.
#
# Recent pg_dump versions wrap output in \restrict/\unrestrict guards carrying a random nonce, which would make every
# capture differ from the last. Strip them: they are a psql-session safety feature, not part of the schema.
pg_dump_schema() {
    docker compose exec -T postgresql pg_dump -U "${DB_USER}" \
        --schema-only --no-owner --no-privileges --no-comments "$1" \
        | grep -vE '^\\(un)?restrict '
}

# Current value of every sequence, as replayable setval statements.
#
# Sorted INSERT dumps deliberately drop the setval lines pg_dump emits, so without this a restored database would hand
# out colliding ids on the next insert. Verification only reads, but a restorable input is worth the three lines.
pg_dump_sequences() {
    docker compose exec -T postgresql psql -U "${DB_USER}" -d "$1" -tAF$'\t' -c "
        select schemaname, sequencename, last_value
        from pg_sequences where schemaname = 'public' and last_value is not null
        order by sequencename
    " | awk -F'\t' '{printf "SELECT pg_catalog.setval(%s, %s, true);\n", "'\''" $1 "." $2 "'\''", $3}'
}

# Data-only dump, used both to freeze the input seed and to capture the ReportDB projection as an *output*.
#
# Column inserts plus a stable sort make the result diffable and order-independent. Both matter: COPY blocks come out
# in physical row order, which reshuffles after any table rewrite, and that noise would drown a real regression.
# Sorting happens statement-wise rather than line-wise — see sort-inserts.py for why that distinction is load-bearing.
pg_dump_data() {
    docker compose exec -T postgresql pg_dump -U "${DB_USER}" \
        --data-only --column-inserts --no-owner --no-privileges --no-comments "$1" 2>/dev/null \
        | python3 "$(dirname "${BASH_SOURCE[0]}")/sort-inserts.py"
}

# --- output normalisation ----------------------------------------------------

# Canonicalise a JSON body: sorted object keys, stable indentation, preserved array order (element order is part of the
# contract). Non-JSON passes through untouched so error pages still land in the golden as-is.
canon_json() {
    python3 -c '
import json, sys
raw = sys.stdin.read()
try:
    sys.stdout.write(json.dumps(json.loads(raw), indent=2, sort_keys=True, ensure_ascii=False) + "\n")
except Exception:
    sys.stdout.write(raw)
'
}

# --- HTTP --------------------------------------------------------------------

# Perform one API request and emit a diffable record of it: the request line, the status, the headers that carry
# contract meaning, and the canonicalised body.
capture_request() {
    local path="$1" token="${2:-}" accept="${3:-}"
    local -a args=(-s -o /dev/null -D - -w '%{http_code}')

    [ -n "${token}" ] && args+=(-H "Authorization: Bearer ${token}")
    [ -n "${accept}" ] && args+=(-H "Accept: ${accept}")

    local headers status body
    headers="$(curl "${args[@]}" "${BASE_URL}${path}" 2>/dev/null || true)"
    status="${headers##*$'\n'}"
    headers="${headers%$'\n'*}"

    local -a bargs=(-s)
    [ -n "${token}" ] && bargs+=(-H "Authorization: Bearer ${token}")
    [ -n "${accept}" ] && bargs+=(-H "Accept: ${accept}")
    body="$(curl "${bargs[@]}" "${BASE_URL}${path}" 2>/dev/null || true)"

    echo "GET ${path}"
    [ -n "${accept}" ] && echo "Accept: ${accept}"
    echo "--- ${status}"

    # Only the headers that are part of the contract. Date, Server and friends are noise that would break every diff.
    echo "${headers}" \
        | grep -iE '^(content-type|www-authenticate|location):' \
        | LC_ALL=C sort \
        | sed 's/[[:space:]]*$//' || true

    echo "---"
    printf '%s' "${body}" | canon_json
}

# Perform one request against a named virtual host and record only what the host boundary is about: the status, where
# a redirect points, and how the session cookie is scoped.
#
# Bodies are deliberately not recorded. The question is "is this path reachable from this host", which the status
# answers; capturing HTML would add CSRF-token churn to every golden for no extra signal.
capture_host_request() {
    local host="$1" path="$2"

    local headers status
    headers="$(curl -s -o /dev/null -D - -w '%{http_code}' -H "Host: ${host}" "${BASE_URL}${path}" 2>/dev/null || true)"
    status="${headers##*$'\n'}"
    headers="${headers%$'\n'*}"

    echo "GET ${path}"
    echo "Host: ${host}"
    echo "--- ${status}"

    # The session cookie's *value* is random per request; its domain is the thing under test, because that domain is
    # currently rewritten by nginx's proxy_cookie_domain and has to survive the move to per-host session config.
    echo "${headers}" \
        | grep -iE '^(content-type|location|set-cookie):' \
        | sed -E 's/^([Ss]et-[Cc]ookie: *[^=]+=)[^;]*/\1<redacted>/' \
        | LC_ALL=C sort \
        | sed 's/[[:space:]]*$//' || true
}

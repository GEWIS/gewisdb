#!/usr/bin/env bash
#
# Restore the frozen input from goldens/input/ into the databases.
#
# This is destructive: it drops and recreates the public schema in both databases. It exists so that a capture, on
# either stack, starts from exactly the same data — which is the whole reason the goldens are comparable at all.
#
# Usage: scripts/goldens/restore-input.sh

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
# shellcheck source=scripts/goldens/lib.sh
source "${HERE}/lib.sh"

IN="${ROOT}/goldens/input"
cd "${ROOT}"

if [ ! -d "${IN}" ]; then
    echo "!! ${IN} does not exist — run scripts/goldens/freeze-input.sh against a seeded database first" >&2
    exit 1
fi

# `client_min_messages=warning` suppresses the several dozen "drop cascades to ..." notices the schema drop emits.
# They are expected and say nothing useful; leaving them in buries the one line that would matter if a restore failed.
psql_run() {
    docker compose exec -T postgresql psql -q -v ON_ERROR_STOP=1 \
        -c 'SET client_min_messages = warning' -U "${DB_USER}" -d "$1" -f -
}

for db in "${DB_DEFAULT}" "${DB_REPORT}"; do
    echo "--> restoring ${db}"
    echo 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;' | psql_run "${db}"
    psql_run "${db}" < "${IN}/${db}-schema.sql"

    # Foreign keys are circular (subdecision references itself through several paths), so a sorted INSERT stream cannot
    # satisfy them in order. Replica mode suspends the triggers for the load — the same trick the fixtures command uses.
    {
        echo "SET session_replication_role = 'replica';"
        cat "${IN}/${db}-data.sql"
        echo "SET session_replication_role = 'origin';"
        cat "${IN}/${db}-sequences.sql"
    } | psql_run "${db}"
done

echo "==> Restored."

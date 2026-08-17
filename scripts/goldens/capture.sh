#!/usr/bin/env bash
#
# Capture the behavioural goldens: what the application *does*, given the frozen input in goldens/input/.
#
# Run this against the current stack to record the baseline; the migration then has to reproduce it. It does not touch
# goldens/input/ — that is the fixture, frozen separately by freeze-input.sh, and comparing it would be meaningless.
#
# Usage: scripts/goldens/capture.sh [output-dir]   (default: goldens/)

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
# shellcheck source=scripts/goldens/lib.sh
source "${HERE}/lib.sh"

OUT="${1:-${ROOT}/goldens}"
cd "${ROOT}"

echo "==> Capturing goldens from the $(stack) stack into ${OUT}"
rm -rf "${OUT}/api" "${OUT}/schema" "${OUT}/reportdb" "${OUT}/checker"
mkdir -p "${OUT}"/{api,schema,reportdb,checker}

# --- schema ------------------------------------------------------------------
# An empty diff here means production tables are already correct and the existing migrations stay valid, so cutover
# needs no data migration. report.sql carries extra weight: GEWISWEB reads ReportDB directly, table by table.

echo "--> schema: dumping DDL per entity manager"
schema_dump default > "${OUT}/schema/default.sql"
schema_dump report  > "${OUT}/schema/report.sql"

# --- reportdb projection -----------------------------------------------------
# Regenerate the report from scratch and dump what it produced. One diff covers every sub-decision type flowing through
# the Database -> ReportDB projection, which is the part of the system a port can break invisibly.

echo "--> reportdb: regenerating the full report"
console report:generate:full > "${OUT}/reportdb/generate.log" 2>&1 || {
    echo "!! report:generate:full failed; see ${OUT}/reportdb/generate.log" >&2
    exit 1
}
pg_dump_data "${DB_REPORT}" > "${OUT}/reportdb/data.sql"

# --- checker -----------------------------------------------------------------
# The checker commands encode the Articles of Association. Drift here is a governance bug, not a software one.
#
# Only the read-only checks are captured. `check:members:keys` rewrites authentication keys and
# `check:membership:renewal:graduate` sends email; running them here would mutate the frozen input. Those two need
# regression tests instead — see #571.

echo "--> checker: running consistency checks"
for cmd in check:database check:discharges; do
    console "${cmd}" 2>&1 | sed -e 's/\r//g' -e 's/[[:space:]]*$//' > "${OUT}/checker/${cmd//:/-}.txt" || true
done

# --- api ---------------------------------------------------------------------
# The contract with the GEWIS applications that consume it. Captured once per principal, because the response *shape*
# depends on which permissions the caller holds, not just whether the call is allowed at all.

echo "--> api: resolving fixture ids and principal tokens"
MEMBER="$(psql_q "${DB_DEFAULT}" "select lidnr from member where deleted = false order by lidnr limit 1" | tr -d '[:space:]')"
DELETED_MEMBER="$(psql_q "${DB_DEFAULT}" "select lidnr from member where deleted = true order by lidnr limit 1" | tr -d '[:space:]')"

if [ -z "${MEMBER}" ] || [ -z "${DELETED_MEMBER}" ]; then
    echo "!! could not resolve fixture member ids — is the input restored? (need one deleted and one live member)" >&2
    exit 1
fi
echo "    member=${MEMBER} deleted_member=${DELETED_MEMBER}"

capture_principal() {
    local label="$1" token="$2"
    local dir="${OUT}/api/${label}"
    mkdir -p "${dir}"

    while IFS=$'\t' read -r name path accept; do
        case "${name}" in ''|'#'*) continue ;; esac

        path="${path//\{MEMBER\}/${MEMBER}}"
        path="${path//\{DELETED_MEMBER\}/${DELETED_MEMBER}}"
        [ "${accept}" = "-" ] && accept=""

        capture_request "${path}" "${token}" "${accept}" > "${dir}/${name}.txt"
    done < "${HERE}/endpoints.tsv"
}

echo "--> api: unauthenticated"
capture_principal "_anonymous" ""

while IFS='|' read -r description token; do
    [ -z "${description}" ] && continue
    echo "--> api: ${description#golden:}"
    capture_principal "${description#golden:}" "${token}"
done < <(psql_q "${DB_DEFAULT}" \
    "select description, token from apiprincipal where description like 'golden:%' order by description")

# --- manifest ----------------------------------------------------------------
# Deliberately excluded from verification: it records *how* a capture was taken, not what the application did.

cat > "${OUT}/MANIFEST" <<EOF
stack=$(stack)
git=$(git rev-parse HEAD)
api_version=${API_VERSION}
member=${MEMBER}
deleted_member=${DELETED_MEMBER}
principals=$(find "${OUT}/api" -maxdepth 1 -mindepth 1 -type d | wc -l | tr -d ' ')
requests=$(find "${OUT}/api" -name '*.txt' | wc -l | tr -d ' ')
EOF

echo "==> Done."
sed 's/^/    /' "${OUT}/MANIFEST"

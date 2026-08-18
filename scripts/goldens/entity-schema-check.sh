#!/usr/bin/env bash
#
# Compare the DDL implied by the ported entities against the recorded schema.
#
# Both sides go through normalise-schema.py first, which removes the ways DBAL 4 renders the same declarations
# differently from the DBAL 3 that made the recording. Everything it does is listed in that file; anything it does not
# cover is drift and fails here.
#
# Usage: scripts/goldens/entity-schema-check.sh

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
cd "${ROOT}"

TMP="$(mktemp -d)"
trap 'rm -rf "${TMP}"' EXIT

DEFAULT_DOMAINS=(Application Database User)

normalise() { python3 "${HERE}/normalise-schema.py" "$1"; }

status=0

echo "==> default entity manager"
php scripts/goldens/entity-schema.php "${DEFAULT_DOMAINS[@]}" > "${TMP}/default.sql"
if diff <(normalise goldens/schema/default.sql) <(normalise "${TMP}/default.sql") > "${TMP}/default.diff"; then
    echo "    ok"
else
    echo "    CHANGED"
    cat "${TMP}/default.diff"
    status=1
fi

echo "==> report entity manager"
php scripts/goldens/entity-schema.php Report > "${TMP}/report.sql"
if diff <(normalise goldens/schema/report.sql) <(normalise "${TMP}/report.sql") > "${TMP}/report.diff"; then
    echo "    ok"
else
    echo "    CHANGED"
    cat "${TMP}/report.diff"
    status=1
fi

if [ "${status}" -ne 0 ]; then
    echo
    echo "The ported entities no longer imply the recorded schema. Either the mapping drifted, or the change is"
    echo "deliberate and the recording needs updating in the same commit."
fi

exit "${status}"

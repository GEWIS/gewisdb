#!/usr/bin/env bash
#
# Compare the DDL implied by the ported entities against the recorded schema.
#
# One statement is expected to differ and is subtracted before comparing: DBAL 3 emitted
# `COMMENT ON COLUMN Membership.startDate IS '(DC2Type:stringable_datetime)'` for the comment-hinted type, and
# App\Doctrine\Types\StringableDateTimeType no longer declares requiresSQLCommentHint() because DBAL 4 removed it.
# The simple_array comments survive, since that type still declares the hint under the installed DBAL.
#
# Usage: scripts/goldens/entity-schema-check.sh

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
cd "${ROOT}"

TMP="$(mktemp -d)"
trap 'rm -rf "${TMP}"' EXIT

DEFAULT_DOMAINS=(Application Decision Member Join Mailing Query User)
EXPECTED_ABSENT="COMMENT ON COLUMN Membership.startDate IS '(DC2Type:stringable_datetime)'"

normalise() { sed 's/;$//' "$1" | LC_ALL=C sort; }

status=0

echo "==> default entity manager"
php scripts/goldens/entity-schema.php "${DEFAULT_DOMAINS[@]}" > "${TMP}/default.sql"
normalise goldens/schema/default.sql | grep -vF "${EXPECTED_ABSENT}" > "${TMP}/expected-default.sql"
if diff "${TMP}/expected-default.sql" <(normalise "${TMP}/default.sql") > "${TMP}/default.diff"; then
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

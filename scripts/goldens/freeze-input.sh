#!/usr/bin/env bash
#
# Freeze the currently seeded databases as the golden set's input.
#
# The fixtures derive their dates from seed time on purpose, so a seed generated today differs from one generated next
# month. Freezing the result here means both the Laminas and the Symfony stack get verified against byte-identical
# input, and the fixtures stay free to be time-relative.
#
# Run this deliberately — after a fixture change, and then re-capture the goldens. Running it casually rewrites the
# baseline that everything else is measured against.
#
# Usage: scripts/goldens/freeze-input.sh

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
# shellcheck source=scripts/goldens/lib.sh
source "${HERE}/lib.sh"

OUT="${ROOT}/goldens/input"
cd "${ROOT}"

echo "==> Freezing seeded databases into ${OUT}"
rm -rf "${OUT}"
mkdir -p "${OUT}"

for db in "${DB_DEFAULT}" "${DB_REPORT}"; do
    echo "--> ${db}"
    pg_dump_schema    "${db}" > "${OUT}/${db}-schema.sql"
    pg_dump_data      "${db}" > "${OUT}/${db}-data.sql"
    pg_dump_sequences "${db}" > "${OUT}/${db}-sequences.sql"
done

echo "==> Done. Re-run scripts/goldens/capture.sh to rebuild the goldens against this input."

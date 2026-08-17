#!/usr/bin/env bash
#
# Verify the current stack against the committed goldens.
#
# Restores the frozen input, captures fresh output, and diffs it against goldens/. A non-empty diff is the migration
# telling you something changed behaviour — either a regression to fix, or a deliberate change to re-capture and
# review in the same pull request.
#
# Usage: scripts/goldens/verify.sh [--keep]

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${HERE}/../.." && pwd)"
cd "${ROOT}"

TMP="$(mktemp -d)"
[ "${1:-}" = "--keep" ] || trap 'rm -rf "${TMP}"' EXIT

echo "==> Restoring frozen input"
bash "${HERE}/restore-input.sh" > /dev/null

echo "==> Capturing current behaviour"
bash "${HERE}/capture.sh" "${TMP}" > /dev/null

echo "==> Comparing against goldens/"

# MANIFEST records how a capture was taken (stack, commit) rather than what the application did, and generate.log is
# progress-bar noise. Neither is a behavioural golden.
status=0
for section in schema reportdb checker api; do
    if diff -r \
        --exclude=generate.log \
        "${ROOT}/goldens/${section}" "${TMP}/${section}" > "${TMP}/${section}.diff" 2>&1
    then
        echo "    ok       ${section}"
    else
        echo "    CHANGED  ${section}  ($(grep -c '^' "${TMP}/${section}.diff") diff lines)"
        status=1
    fi
done

if [ "${status}" -ne 0 ]; then
    echo
    echo "==> Behaviour differs from the goldens. Full diffs:"
    for section in schema reportdb checker api; do
        [ -s "${TMP}/${section}.diff" ] || continue
        echo
        echo "--- ${section} ---"
        head -200 "${TMP}/${section}.diff"
    done
    [ "${1:-}" = "--keep" ] && echo && echo "Captured output kept in ${TMP}"
    exit 1
fi

echo "==> Behaviour matches the goldens."

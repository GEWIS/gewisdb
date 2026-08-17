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

# The schema section is compared through normalise-schema.py, which removes the ways DBAL 4 renders the same
# declarations differently from the DBAL 3 that made the recording. Everything it removes is enumerated in that file;
# anything it does not cover is drift and fails here. The other sections are compared byte for byte.
compare_schema() {
    local file name status=0

    : > "${TMP}/schema.diff"

    for file in "${ROOT}"/goldens/schema/*.sql; do
        name="$(basename "${file}")"

        if ! diff \
            <(python3 "${HERE}/normalise-schema.py" "${file}") \
            <(python3 "${HERE}/normalise-schema.py" "${TMP}/schema/${name}") \
            >> "${TMP}/schema.diff" 2>&1
        then
            status=1
        fi
    done

    return "${status}"
}

# The recorded exchanges go through normalise-http.py, which folds the two spellings HTTP defines as equivalent —
# header-name case and the charset token's case. Everything else is compared as recorded.
compare_http() {
    local section="$1" file relative status=0

    : > "${TMP}/${section}.diff"

    for file in "${ROOT}/goldens/${section}"/*/*.txt; do
        relative="${file#"${ROOT}/goldens/${section}/"}"

        if [ ! -f "${TMP}/${section}/${relative}" ]; then
            echo "missing: ${relative}" >> "${TMP}/${section}.diff"
            status=1
            continue
        fi

        if ! diff \
            <(python3 "${HERE}/normalise-http.py" "${file}") \
            <(python3 "${HERE}/normalise-http.py" "${TMP}/${section}/${relative}") \
            >> "${TMP}/${section}.diff" 2>&1
        then
            status=1
        fi
    done

    return "${status}"
}

# MANIFEST records how a capture was taken (stack, commit) rather than what the application did, and generate.log is
# progress-bar noise. Neither is a behavioural golden.
status=0
for section in schema reportdb checker hosts api; do
    if [ "${section}" = schema ]; then
        compare_schema && ok=0 || ok=1
    elif [ "${section}" = api ] || [ "${section}" = hosts ]; then
        compare_http "${section}" && ok=0 || ok=1
    else
        diff -r \
            --exclude=generate.log \
            "${ROOT}/goldens/${section}" "${TMP}/${section}" > "${TMP}/${section}.diff" 2>&1 && ok=0 || ok=1
    fi

    if [ "${ok}" -eq 0 ]; then
        echo "    ok       ${section}"
    else
        echo "    CHANGED  ${section}  ($(grep -c '^' "${TMP}/${section}.diff") diff lines)"
        status=1
    fi
done

if [ "${status}" -ne 0 ]; then
    echo
    echo "==> Behaviour differs from the goldens. Full diffs:"
    for section in schema reportdb checker hosts api; do
        [ -s "${TMP}/${section}.diff" ] || continue
        echo
        echo "--- ${section} ---"
        head -200 "${TMP}/${section}.diff"
    done
    [ "${1:-}" = "--keep" ] && echo && echo "Captured output kept in ${TMP}"
    exit 1
fi

echo "==> Behaviour matches the goldens."

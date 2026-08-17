#!/usr/bin/env python3
"""Reassemble and sort the INSERT statements in a pg_dump --column-inserts stream.

Sorting is what makes a data dump diffable: COPY and INSERT output both follow physical row order, which reshuffles
after any table rewrite, and that noise would drown a real regression.

Sorting *lines* is not enough, though. A column value may itself contain newlines — GEWISDB's `savedquery` table
stores multi-line DQL — so one INSERT can span many lines, and a line-wise sort both scrambles and truncates it.

A statement is therefore accumulated until it ends with `);` on an even number of single quotes. PostgreSQL escapes a
quote inside a string by doubling it, which leaves that parity intact, so the rule holds for arbitrary text.
"""

import sys


def statements(lines):
    buffer: list[str] = []
    quotes = 0

    for line in lines:
        if not buffer and not line.startswith("INSERT INTO "):
            continue

        buffer.append(line)
        quotes += line.count("'")

        if quotes % 2 == 0 and line.rstrip().endswith(");"):
            yield "".join(buffer)
            buffer = []
            quotes = 0

    if buffer:
        raise SystemExit(
            "sort-inserts: unterminated INSERT statement — refusing to emit a truncated dump:\n"
            + "".join(buffer)[:500]
        )


def main() -> None:
    sys.stdout.write("".join(sorted(statements(sys.stdin))))


if __name__ == "__main__":
    main()

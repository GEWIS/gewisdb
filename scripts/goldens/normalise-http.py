#!/usr/bin/env python3
"""Normalise a recorded HTTP exchange so it can be compared across the two frameworks.

The recording keeps the bytes each stack put on the wire. Two of the ways they differ are not observable behaviour,
because HTTP defines both spellings to mean the same thing, and comparing them literally would report a change on
every response:

  * Header names. Laminas emitted `WWW-Authenticate`; Symfony's HeaderBag emits `Www-Authenticate`. Field names are
    case-insensitive (RFC 9110 §5.1), so both are the same header.
  * The charset token. `charset=UTF-8` and `charset=utf-8` name the same encoding (RFC 2978 §2.3).

Nothing else is touched: a status line, a body, a cookie or a missing header still fails the comparison.

Usage: normalise-http.py <recording.txt>
"""

import re
import sys

# A header line, as the capture writes it: the name, a colon, then the value.
HEADER = re.compile(r'^([A-Za-z0-9-]+): (.*)$')
CHARSET = re.compile(r'\bcharset=([A-Za-z0-9-]+)')


def normalise(line: str) -> str:
    line = line.rstrip('\n')
    header = HEADER.match(line)

    if not header:
        return line

    name, value = header.group(1).lower(), CHARSET.sub(lambda m: f'charset={m.group(1).lower()}', header.group(2))

    return f'{name}: {value}'


def main() -> int:
    if 2 != len(sys.argv):
        print(__doc__, file=sys.stderr)

        return 2

    with open(sys.argv[1], encoding='utf-8') as handle:
        print('\n'.join(normalise(line) for line in handle))

    return 0


if __name__ == '__main__':
    sys.exit(main())

#!/usr/bin/env python3
"""Normalise a recorded HTTP exchange so it can be compared across the two frameworks.

The recording keeps the bytes each stack put on the wire. Four of the ways they differ are not observable behaviour,
because HTTP defines both spellings to mean the same thing, and comparing them literally would report a change on
every response:

  * Header names. Laminas emitted `WWW-Authenticate`; Symfony's HeaderBag emits `Www-Authenticate`. Field names are
    case-insensitive (RFC 9110 §5.1), so both are the same header.
  * The charset token. `charset=UTF-8` and `charset=utf-8` name the same encoding (RFC 2978 §2.3).
  * Cookie attribute names. Laminas wrote `HttpOnly; SameSite=Lax`, Symfony writes `httponly; samesite=lax`.
    Attribute names are matched case-insensitively (RFC 6265 §5.2), so both set the same flags. The cookie's own name
    keeps its case, because that one is significant.
  * A redirect to the host that was asked. Laminas answered `Location: /login`, Symfony's security entry point builds
    an absolute URL through the router. Both name the same target (RFC 9110 §10.2.2 allows either), and what this
    section is testing is which host lets you reach what, not how the redirect is spelled. A `Location` pointing at
    any *other* host is left alone, because that is a different destination and the whole point of the check.

Nothing else is touched: a status line, a body, a cookie or a missing header still fails the comparison.

Usage: normalise-http.py <recording.txt>
"""

import re
import sys

# A header line, as the capture writes it: the name, a colon, then the value.
HEADER = re.compile(r'^([A-Za-z0-9-]+): (.*)$')
CHARSET = re.compile(r'\bcharset=([A-Za-z0-9-]+)')
# Everything after the cookie's `name=value` pair is attributes, which is what may be re-cased.
COOKIE_ATTRS = re.compile(r'^([^;]*)(;.*)$', re.S)
# The `Host:` line the capture writes above the response, so a redirect can be told from a redirect elsewhere.
HOST_LINE = re.compile(r'^Host: (\S+)$')


def normalise(line: str, host: str | None) -> str:
    line = line.rstrip('\n')
    header = HEADER.match(line)

    if not header:
        return line

    name, value = header.group(1).lower(), header.group(2)
    value = CHARSET.sub(lambda m: f'charset={m.group(1).lower()}', value)

    if 'set-cookie' == name:
        pair = COOKIE_ATTRS.match(value)

        if pair:
            value = pair.group(1) + pair.group(2).lower()

    if 'location' == name and host is not None:
        value = re.sub(r'^https?://' + re.escape(host) + r'(?=/|$)', '', value) or '/'

    return f'{name}: {value}'


def main() -> int:
    if 2 != len(sys.argv):
        print(__doc__, file=sys.stderr)

        return 2

    with open(sys.argv[1], encoding='utf-8') as handle:
        lines = handle.read().split('\n')

    host = next((m.group(1) for m in map(HOST_LINE.match, lines) if m), None)
    print('\n'.join(normalise(line, host) for line in lines))

    return 0


if __name__ == '__main__':
    sys.exit(main())

#!/usr/bin/env python3
"""Normalise a schema dump so it can be compared across DBAL 3 and DBAL 4.

The recording was made by DBAL 3 under Laminas; the ported entities are dumped by DBAL 4. Several differences between
the two are in how the DDL is rendered rather than in what it declares, and comparing them literally would report drift
on every run. Each is removed here, and nothing else is:

  * `PRIMARY KEY(a, b)` is now written `PRIMARY KEY (a, b)`.
  * `NOT DEFERRABLE INITIALLY IMMEDIATE` is now written `NOT DEFERRABLE`, and is omitted altogether on a foreign key
    that carries no other options. Both spell out the PostgreSQL default.
  * `COMMENT ON COLUMN ... IS '(DC2Type:...)'` is no longer emitted: DBAL 4 dropped comment type hints, which existed
    so DBAL 3 could read a column's Doctrine type back out of the database.
  * Columns are emitted in a different order within `CREATE TABLE`.
  * A `#[Version]` integer column is given `DEFAULT 1`. ORM 3 sets this unconditionally (ClassMetadata::
    setVersionMapping reads the deprecated top-level `default` key, not `options.default`), overwriting the mapped
    1000. The value is never read: Doctrine always writes the version column itself on insert.

Usage: normalise-schema.py <schema.sql>
"""

import re
import sys

CREATE_TABLE = re.compile(r'^CREATE TABLE (\w+) \((.*)\)$')


def split_columns(body: str) -> list[str]:
    """Split a CREATE TABLE body on the commas that separate columns, ignoring those inside parentheses."""
    parts, depth, current = [], 0, ''

    for char in body:
        if char == '(':
            depth += 1
        elif char == ')':
            depth -= 1

        if char == ',' and 0 == depth:
            parts.append(current.strip())
            current = ''
        else:
            current += char

    parts.append(current.strip())

    return [part for part in parts if part]


def normalise(line: str) -> str | None:
    line = line.rstrip().removesuffix(';')

    if line.startswith('COMMENT ON COLUMN ') and '(DC2Type:' in line:
        return None

    line = line.replace(' NOT DEFERRABLE INITIALLY IMMEDIATE', '').replace(' NOT DEFERRABLE', '')
    line = line.replace('PRIMARY KEY(', 'PRIMARY KEY (')
    line = re.sub(r'(\bversion INT DEFAULT )\d+( NOT NULL)', r'\g<1>1\g<2>', line)

    table = CREATE_TABLE.match(line)

    if table:
        columns = sorted(split_columns(table.group(2)))
        line = f'CREATE TABLE {table.group(1)} ({", ".join(columns)})'

    return line


def main() -> int:
    if 2 != len(sys.argv):
        print(__doc__, file=sys.stderr)

        return 2

    with open(sys.argv[1], encoding='utf-8') as handle:
        lines = [normalise(line) for line in handle if line.strip()]

    print('\n'.join(sorted(line for line in lines if line is not None)))

    return 0


if __name__ == '__main__':
    sys.exit(main())

# Behavioural goldens

A recording of what GEWISDB does, captured from the Laminas application, so the Symfony port can be checked against it
mechanically instead of by inspection.

Tracked in GH-556, under the migration epic GH-555.

## Why these exist

The test suite is twelve files. That cannot tell you whether a 396-file port is correct, and the migration is being
executed largely unattended — which means the tests that grow alongside it are written by the same agent that wrote the
port, and can happily assert the wrong behaviour with total confidence.

The goldens are the one thing that cannot be argued with: they were recorded from the *old* implementation, before any
Symfony existed. **Where a test and a golden disagree, the golden wins.**

## Layout

| Path | What it is | What a diff means |
|---|---|---|
| `input/` | The frozen seed — schema, sorted data, sequence values, for both databases. **Not a golden.** | Nothing; this is the fixture both stacks are fed. |
| `schema/default.sql` | DDL the ORM mapping implies for the Database. | Entity mapping changed. Production tables would need a migration. |
| `schema/report.sql` | DDL the ORM mapping implies for ReportDB. | **Read this one carefully.** See "The GEWISWEB coupling" below. |
| `reportdb/data.sql` | ReportDB contents after a full regeneration. | The Database → ReportDB projection changed. |
| `checker/*.txt` | Output of the read-only checker commands. | A rule derived from the Articles of Association changed. |
| `api/<principal>/<endpoint>.txt` | Every API response, per permission set. | The API contract changed. |
| `MANIFEST` | How the capture was taken. Excluded from comparison. | — |

## Running

```sh
make goldens-verify     # restore input, capture, diff against the committed goldens
make goldens            # re-record the goldens from the current stack
make goldens-freeze     # re-freeze input/ after a fixture change (then re-record)
make goldens-restore    # just put the frozen input back into the databases
```

`goldens-verify` and `goldens-restore` are **destructive**: they drop and recreate the `public` schema in both
databases. That is the point — a capture is only comparable if it started from identical data.

## Why the input is frozen rather than re-seeded

The fixtures derive their dates from seed time on purpose, so that membership histories stay realistic relative to the
current association year. A useful property for development, and fatal for a committed golden: a capture taken today
would not match one taken next month.

Freezing the seeded databases removes the whole problem. Both stacks are fed byte-identical input, and the fixtures
stay free to be time-relative.

`input/` is therefore never compared. Only the four output sections are.

## The GEWISWEB coupling

`schema/report.sql` is more load-bearing than the rest.

GEWISWEB does **not** read our HTTP API for member and decision data. `App\Command\Decision\ImportGewisdbCommand`
opens a direct read-only PostgreSQL connection to ReportDB and copies twelve tables column-by-column into its own
MariaDB, relying on the two schemas staying compatible — its own comment says so. The only HTTP endpoint it consumes is
`/health`, for the `sync_paused` flag.

So a renamed or retyped ReportDB column breaks GEWISWEB on the next sync run, with no HTTP layer in between to absorb
it, and the failure surfaces over there rather than here. See the discussion on GH-559 and GH-563.

## What the API goldens cover

The API's response *shape* depends on which permissions the calling principal holds, not just on whether the call is
allowed. Three different mechanisms are at work, and all three are captured:

- **Operation gates** — `members_read`, `health_read`, and friends decide whether the endpoint answers at all.
- **Field shaping** — the seven `members_read_*` property claims plus `organs_members_read` add and remove individual
  properties. Two principals calling the same URL legitimately get differently-shaped objects.
- **Row filtering** — `members_deleted` decides whether deleted members are visible.

`UserTest\Seeder\ApiPrincipalFixture` therefore seeds a principal per interesting combination — one per gate, one per
modifier in isolation, and the fully-loaded variants — and the capture walks every endpoint as each of them, plus
unauthenticated. That is 19 principals × 15 requests.

Tokens are still randomly generated; the fixture gives each principal a stable `golden:` prefixed description and the
capture looks the tokens up by that. `ApiPrincipal` deliberately offers no way to set a token and that invariant is
worth keeping.

## Known behaviour recorded here that is wrong

The goldens record what the application *does*, including its bugs. Two are worth knowing about before you read a diff:

- **`GET /api/members` silently truncates at 32 rows** (`Report\Mapper\Member::findNormal()`), while `openapi.yaml`
  documents it as "Get all members" with no pagination. Tracked in GH-575.
- The same query had **no `ORDER BY`** until this baseline was recorded, so it returned an arbitrary 32 of the eligible
  members and a different arbitrary 32 on the next call. Ordering by `lidnr` was added as a prerequisite — without it
  there is no stable baseline to record at all. `findActive()` got the same treatment.

## Changing a golden

A diff is not automatically a failure. When a change is deliberate, re-record it in the **same** pull request as the
change, so a reviewer sees the behavioural delta next to the code that caused it. A pull request that changes
`goldens/` and nothing else, or code and no goldens where a diff was expected, is the thing to be suspicious of.

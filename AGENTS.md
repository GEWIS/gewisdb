# AGENTS.md

Guidance for AI coding agents working in this repository. Humans should read `README.md` first.

## What this project is

GEWISDB is the GEWIS decision and membership database. It records meetings, decisions (the immutable history of what
the association decided), members, bodies and mailing lists, and exposes them to other GEWIS systems through an API.

The pre-migration application lives on in the `main` branch's history. It is a reference for *behaviour* when a
question about intent comes up, and never a place to copy patterns from.

## Stack

- **PHP 8.5** with `declare(strict_types=1)` in every file.
- **Symfony 8.1**, standard structure: `src/`, `config/`, `templates/`, `assets/`, `migrations/`.
- **Doctrine ORM 3** with attribute mapping, on **PostgreSQL**.
- **FrankenPHP** in worker mode behind Caddy.
- Twig with **UX Live Components** for the overviews; asset-mapper with SWC and dart-sass for the front end.
- Runs under `docker compose`; most `make` targets shell into the `web` container.

**GEWISWEB is the reference for conventions.** It is the sister application, already on Symfony, and where this
project and that one solve the same problem they should solve it the same way — form themes, page layout, Live
Component shape, Makefile targets, mail, dev tooling. Read `../gewisweb` before inventing an approach, and prefer
copying its arrangement whole over approximating it.

## Two databases

Two entity managers, and the distinction matters for anything that touches data:

- **`default`** — the **ledger**. The editable source of truth: members, prospective members, meetings, decisions,
  bodies, mailing lists. Entities under `src/Entity/Database`, migrations in `migrations/database`.
- **`report`** — **ReportDB**. A projection of the ledger, read by other GEWIS systems. Entities under
  `src/Entity/Report`, migrations in `migrations/report`.

GEWISWEB does **not** call our API for member and decision data: `ImportGewisdbCommand` opens a read-only connection
straight to ReportDB and copies twelve tables column by column. The ReportDB schema is therefore a contract with
another application, not an internal detail.

`DatabaseUpdateListener` and `DatabaseDeletionListener` keep the projection level with the ledger as it is written, so
a change to a ledger entity usually needs the matching change on the Report side and in the projection services under
`src/Service/Report`. `report:generate:full` rebuilds the whole projection from the ledger and is what to run after a
bulk change; the listeners stand down while fixtures load, for exactly that reason.

**The two migration sets are separate, and the bundle only holds one.** ReportDB's migrations must be run with its own
configuration:

```
bin/console doctrine:migrations:migrate --em=report --configuration=migrations/report.yaml
```

Without `--configuration` the ledger's migrations are applied to the report connection and ReportDB fills up with the
ledger's tables. `make migrate` and `make migration-diff` pass it; anything else you write must too.

## Layout

```
src/
  ApiResource/    what the API hands out
  Command/        console commands, by domain
  Controller/     Application, Database, Report, User
  DataFixtures/   the development seed, by concern
  Entity/         Application, Database, Report, User
  EventListener/  request, projection and API listeners
  Form/           form types
  Repository/     one per entity
  Service/        the domain services
  Twig/           Live Components, extensions
  ViewModel/      what a template is handed, when an entity is the wrong shape for it
```

The four domains are **Application**, **Database**, **Report** and **User**, plus **Checker** under `Service`/
`ViewModel`. `Database` is the ledger and `Report` is the projection of it; Member, Decision, Meeting, Organ,
MailingList and the rest are entities *in* one of those, never domains of their own. There is no `Api` domain: the API
is a delivery mechanism, and its parts belong to what they serve.

## Dependency injection

Autowiring and autoconfiguration, with scalar bindings in `config/services.yaml`. No factory classes. Constructor
property promotion, `readonly` where it holds.

## Coding style

- `declare(strict_types=1);` immediately after `<?php`.
- Native types everywhere the parent signature allows.
- `GEWISPHPCodingStandards` (`phpcs.xml.dist`); `make lint` is authoritative, `make lint-fix` fixes a subset.
- Doctrine entities use attribute mapping.
- Comments explain decisions and non-obvious mechanics, not what the code already says.

## Checks

| Command | What it does |
|---|---|
| `make lint` | Coding standard. |
| `make lint-fix` | Auto-fix what it can. |
| `make lint-twig` | Validate the Twig templates. |
| `make phpstan` | PHPStan at level 8, baseline in `phpstan-baseline.neon`. |
| `make igor` | Check what the FrankenPHP worker's persistent memory model forbids. |
| `make test` | PHPUnit; `c=` passes options through. |
| `make test-prepare` | Build both test schemas and load the seed. Run once, and after a schema or fixture change. |
| `bin/console check:database` | The consistency checks from the Articles of Association and Internal Regulations. |

Fix what a new error reports rather than extending the baseline. The checker is worth running after anything that
changes decisions or installations — it is the only thing that will tell you the seed or a decision path is producing
data the regulations forbid.

## Local development

- `make start` — build and start the stack. Creates `.env.local` from `.env.local.dist` if it is missing.
- `make migrate` — migrations for both entity managers.
- `make seed` — load fixtures, rebuild ReportDB, prepare Mailman and Listmonk.
- `make translations` — extract into `translations/*.xlf`. **Fill every new target before committing**: an empty
  `<target/>` is used *as* the translation, so the interface renders blank rather than falling back.
- `make cc` — clear the cache and restart the worker. Rarely needed in development: the file watcher restarts it
  on a change.
- `make exec cmd="..."` — run something in the `web` container; `make bash` opens a shell, `make logs` follows the logs.
- `make sf c="..."` and `make composer c="..."` — the console and Composer inside the container.

The checks above all run *inside* the `web` container, as GEWISWEB's do, so the stack has to be up. `vendor/` lives
in the image rather than the bind mount; `make getvendordir` copies it out for the IDE to index.

`.env` is committed and holds development defaults; `.env.local` is yours and is not. Seven names are deliberately
absent from `.env` (`APP_SECRET`, the `STRIPE_*` keys, `SMTP_PASSWORD`, `LDAP_BINDUSER_PASS`) and are written in
the compose files without a value, so they are injected only when set where compose runs: nothing in development, the
orchestrator in production. Giving them a value in `.env` would shadow `.env.local`.

Hot reload is on: `FRANKENPHP_WORKER_CONFIG=watch` restarts the workers on a file change and
`FRANKENPHP_SITE_CONFIG=hot_reload` reloads the page over the Mercure hub in the Caddyfile.

Development mail is caught by Mailpit on <http://localhost:8025>.

## Translatable strings

`t()` and `TranslatableMessage` are extracted by their literal argument. An enum that builds a label must therefore
put the constructor **inside** each match arm:

```php
return match ($this) {
    self::Chair => new TranslatableMessage('Voorzitter'),
};
```

Not `new TranslatableMessage(match ($this) { ... })` — the extractor cannot see through that, and
`translation:extract --clean` then deletes every translation for those labels.

## Things to be careful about

- **Decisions are the historical record.** They are amended by further decisions, never edited. Subdecision `sequence`
  is part of the primary key and is copied downstream, so subdecisions cannot be reordered.
- **The three hosts are a boundary.** `database.gewis.nl` serves the register; `join.gewis.nl` serves the sign-up form
  and its checkout; `member.gewis.nl` serves graduate renewal under `/renew/{token}`. `HostFirewallListener` refuses
  anything else with a 404 — a redirect would tell an anonymous visitor the page exists.
- **ReportDB is a contract** with GEWISWEB. Changing its schema is changing another application's input.
- **Commits must be signed** (see `README.md`).

## When you do not know

Look at GEWISWEB first, then at the nearest sibling here. If it concerns the ledger ↔ ReportDB boundary, ask rather
than guess: getting the projection wrong corrupts what other systems read.

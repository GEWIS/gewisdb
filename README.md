<div align="center">
    <h1>GEWISDB - The GEWIS Decision and Membership Database</h1>

<!-- Shield group -->
[![Latest Release](https://img.shields.io/github/v/release/GEWIS/gewisdb)](https://github.com/GEWIS/gewisdb/releases)
[![Build](https://img.shields.io/github/check-runs/GEWIS/gewisdb/main)](https://github.com/GEWIS/gewisdb/actions)
[![Uptime](https://uptime.gewis.nl/api/badge/18/uptime)](https://database.gewis.nl/)
[![Issues](https://img.shields.io/github/issues/GEWIS/gewisdb)](https://github.com/GEWIS/gewisdb/issues)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/GEWIS/gewisdb/main)](https://github.com/GEWIS/gewisdb/commits/main)
[![License](https://img.shields.io/github/license/GEWIS/gewisdb.svg)](./LICENSE.txt)

<p>GEWISDB is the decision and membership database for GEWIS — <em>GEmeenschap van Wiskunde en Informatica Studenten</em>.</p>
</div>

> [!IMPORTANT]
> **This repository is archived.** GEWISDB and [GEWISWEB](https://github.com/GEWIS/gewisweb) have been combined into
> [`radix`](https://github.com/GEWIS/radix), a single application that carries both the decision and membership
> database and the website. All further development, issues, and pull requests happen there.
>
> The code here remains available for reference, but it no longer receives updates or security fixes.

## Features
The GEWIS decision and membership database provides the board and other GEWIS systems with lots of functionality:

- **Management of Decisions**:
    - Organise and manage various types of meetings.
    - Handle a range of decisions, from financial budgets and statements to the installation of members in various organs, along with customisable decisions.
    - While decisions can be altered to reflect changes, they remain more or less immutable to maintain historical accuracy.

- **Management of Memberships**:
    - The join page, located at [join.gewis.nl](https://join.gewis.nl), facilitates new memberships and can automatically collect membership fees through Stripe.
    - Validation of student information ensures that all member information is accurate.
    - Allows for detailed and precise editing of member information.

- **Checker Module**:
    - Ensures that the database remains in a consistent state by enforcing many constraints derived from the Articles of Association and Internal Regulations.
    - For instance, it prevents members from being installed in an organ if their membership has expired, ensuring adherence to (regulatory) requirements.

- **ReportDB**:
    - Provides a consistent "materialised view" of the real database, enabling easy querying of decisions and membership information through an API.
    - Used by most GEWIS systems as a single, reliable source of truth, ensuring consistency and accuracy across all systems.

And there is plenty more! All of this now lives on in [radix](https://github.com/GEWIS/radix).

## Getting Started
GEWISDB is built on PHP and the [Symfony framework](https://symfony.com/). The Symfony framework provides a solid foundation for building scalable and maintainable web applications.

### Prerequisites
We recommend developing natively on a Linux machine or through WSL2 on Windows (note: Arch-based distributions are **not** recommended) with the [PhpStorm](https://www.jetbrains.com/phpstorm/) IDE or another IDE with good IntelliSense support for PHP.<br/>
Alternatively, you can use [GitHub Codespaces](https://github.com/codespaces/new?hide_repo_select=false&repo=gewis/gewisdb&geo=EuropeWest&machine=basicLinux32gb).

You will need at least:
- `docker` and `docker compose` (make sure that you have enabled [Buildkit](https://docs.docker.com/build/buildkit/#getting-started))
- `git`
- `make`
- A `.xlf` file editor (e.g. POEdit)

PHP, Composer, and all other runtime tooling live inside the Docker image, no need to install them yourself.

It is possible to use [rootless docker](https://docs.docker.com/engine/security/rootless/) on many Linux systems. For this, install `uidmap`, ensure IP forwarding is enabled, run `dockerd-rootless-setuptool.sh install` and set the `DOCKER_HOST` variable in your profile (e.g. `.bashrc`).

### Installation
To set up GEWISDB locally, follow these steps:

1. [Fork the repository](https://github.com/GEWIS/gewisdb/fork).
2. Clone your fork (`git clone git@github.com:{username}/gewisdb.git`).
3. Run `make start` to build and serve the application (a `.env.local` will be created for you; alter it to your needs). This may take 5-10 minutes.
4. Run `make migrate` and `make seed` to get some test data.
5. Go to [`http://localhost:9725/`](http://localhost:9725/) in your browser and you are greeted with the GEWIS decision and membership database.
6. Log in with username `admin` and the password `gewisdbgewis`.

#### Other Accessible Services
During development, several other services are accessible on your local machine:

- **pgAdmin** - Database management interface at [`http://localhost:8080/`](http://localhost:8080/).
- **MailPit** - Email testing at [`http://localhost:8025/`](http://localhost:8025/).
- **Mailman** - Mailing list management at [`http://localhost:8021/`](http://localhost:8021/) (its REST API is on `8020`).
- **Listmonk** - Newsletter management at [`http://localhost:8022/`](http://localhost:8022/).

### Contributing
We welcome contributions from the community, especially GEWIS members! To contribute:

1. Perform the steps from [Installation](#installation).
2. Create your feature of bug fix branch (`git switch -c feature/my-amazing-feature`).
3. Commit your changes (`git commit -m 'feat: added my amazing feature'`). <ins>**NOTE:** GEWISDB requires commits to be signed, see [this GitHub article](https://docs.github.com/en/authentication/managing-commit-signature-verification/signing-commits) for more information on how to sign commits.</ins>
4. Push to the branch (`git push origin feature/my-amazing-feature`).
5. Open a pull request.

More detailed information on GEWIS' contribution guidelines, including conventions on branch names and commit messages, can be found [here](https://github.com/GEWIS/.github/blob/main/CONTRIBUTING.md).

### Useful Commands During Development
While developing, use these commonly used commands from the Makefile:

- `make bash` - Shell into the FrankenPHP `web` container.
- `make sf c='...'` - Run a Symfony console command inside the container (e.g. `make sf c=check:database`).
- `make composer c='...'` - Run a Composer command inside the container (e.g. `make composer c=update`).
- `make translations` - Extract translatable strings into the `.xlf` files. Run this whenever you add or edit a user-facing string in PHP, Twig, or a form type.
- `make lint` / `make lint-fix` - Run PHP_CodeSniffer (or PHPCBF to autofix) against the project's coding standard.
- `make phpstan` - Perform static analysis using PHPStan.
- `make test` - Run the test suite with PHPUnit.
- `make test-prepare` - Build the isolated test databases and load the seed into them. Run this once, and again after a schema or fixture change; the tests roll back their own writes, so the seed survives a run.
- `make igor` - Run Igor to validate the codebase for FrankenPHP's worker mode.

For a complete list of available commands, run `make help`.

> [!TIP]
> If you are using AI coding tools (Claude Code, Copilot, Cursor, ...), they will pick up `AGENTS.md` automatically. It documents architecture, conventions, and gotchas in more depth than this README. However, it is not only for AI coding tools, have a look too if you are interested.

### Project Structure
A general overview of important folders required for the functioning of the application:

```txt
./
├── assets                  # Front-end sources (SCSS, TypeScript, Stimulus controllers).
├── config                  # Global configuration files for the application.
├── docker                  # Docker-related files to construct the containers.
├── migrations              # Doctrine migrations, split per entity manager.
├── src                     # The application itself, grouped by the role each class plays.
├── templates               # Twig templates.
├── translations            # The `.xlf` translation files.
└── public                  # Publicly accessible files, including the entry point (index.php).
```

Within `src`, classes are grouped by what they do rather than by feature: `Controller`, `Service`, `Entity`, `Repository`, `Form`, `Twig`, and so on. Each of those is then split by domain — `Database` for the ledger of what the association decided, `Report` for the materialised view of it, `User` for accounts and the API, `Checker` for the consistency checks, and `Application` for what is shared.

### Testing stripe behaviour
Some additional configuration needs to be done to set up the Stripe API:

* Create a restricted key on https://dashboard.stripe.com/test/apikeys and set it in the `STRIPE_SECRET_KEY` environment variable (check `.env.local.dist` for the permissions to set)
* Copy the publishable key from https://dashboard.stripe.com/test/apikeys and set it in the `STRIPE_PUBLISHABLE_KEY` environment variable
* Copy the webhook signing secret from the output of `make stripewebhooksecret`
* Create a product with a one-off price on https://dashboard.stripe.com/test/products?active=true and copy its price ID to `STRIPE_MEMBERSHIP_PRICE_ID`

Tip: to reduce waiting time for checkout sessions to expire, you can speed up this process by invoking `docker compose exec stripe stripe checkout sessions expire cs_test_fromcheckoutsessionstable`.
This will enable cash payment and send the retry email.

Note: the links in the e-mails do not resolve in the development setup. Replace the host with `http://localhost:9725/` to follow them.

### Using the API
To experiment with the API, import the openapi.yaml file into your favourite REST client.

Alternatively, you can use PowerShell, for example:

```powershell
((Invoke-WebRequest -Uri http://localhost:9725/api/organFunctions -Headers @{"Authorization" = "Bearer APITOKEN"; "Accept" = "application/vnd.gewis.gewisdb+json;version=4.3.3"}).Content | ConvertFrom-Json).data | Format-List
```

## License
This software is licensed under the GNU General Public License v3.0 (GPL-3.0), see [LICENSE](./LICENSE.txt).

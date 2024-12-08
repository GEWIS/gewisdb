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

And there is plenty more! GEWISDB continuously evolves to meet the needs of the association.

## Getting Started
GEWISDB is built on PHP and the [Laminas MVC framework](https://getlaminas.org/). The Laminas MVC framework provides a solid foundation for building scalable and maintainable web applications.

### Prerequisites
We recommend developing natively on a Linux machine or through WSL2 on Windows (note: Arch-based distributions are **not** recommended) with the [PhpStorm](https://www.jetbrains.com/phpstorm/) IDE or another IDE with good IntelliSense support for PHP.<br/>
Alternatively, you can use [GitHub Codespaces](https://github.com/codespaces/new?hide_repo_select=false&repo=gewis/gewisdb&geo=EuropeWest&machine=basicLinux32gb).

You will need at least:
- `docker` and `docker compose` (make sure that you have enabled [Buildkit](https://docs.docker.com/build/buildkit/#getting-started))
- `gettext` utilities
- `git`
- `make`
- A `.po` file editor (e.g. POEdit)

Some of the `make` commands run natively on your machine; as such, you may also need to install PHP itself (use the `ondrej/php` PPA for `apt` to get the latest version) and [`composer`](https://getcomposer.org/download/).

### Installation
To set up GEWISDB locally, follow these steps:

1. [Fork the repository](https://github.com/GEWIS/gewisdb/fork).
2. Clone your fork (`git clone git@github.com:{username}/gewisdb.git`).
3. Copy the `.env.dist` file to `.env` and alter the file to your needs.
4. Run `make rundev` to build and serve the website (this may take 5-10 minutes).
5. Run `make migrate` and `make seed` to get some test data.
6. Go to `http://localhost/` in your browser and you are greeted with the GEWIS decision and membership database.
7. Log in with username `admin` and the password `gewisdbgewis`.

### Contributing
We welcome contributions from the community, especially GEWIS members! To contribute:

1. Perform the steps from [Installation](#installation).
2. Create your feature of bug fix branch (`git switch -c feature/my-amazing-feature`).
3. Commit your changes (`git commit -m 'feat: added my amazing feature'`). <ins>**NOTE:** GEWISDB requires commits to be signed, see [this GitHub article](https://docs.github.com/en/authentication/managing-commit-signature-verification/signing-commits) for more information on how to sign commits.</ins>
4. Push to the branch (`git push origin feature/my-amazing-feature`).
5. Open a pull request.

More detailed information on GEWIS' contribution guidelines, including conventions on branch names and commit messages, can be found [here](https://github.com/GEWIS/.github/blob/main/CONTRIBUTING.md).

### Project Structure
A general overview of important folders required for the functioning of the website:

```txt
./
├── config                  # Global configuration files for the website.
├── data                    # Persistent private data-related files, such as cryptographic keys and logs.
├── docker                  # Docker-related files to construct the containers.
├── module                  # Contains the modules that make up the website, each providing specific features.
└── public                  # Publicly accessible files, including the entry point (index.php).
```

We make use of the Model-View-Controller framework. Generally speaking, the model layer is responsible for the interaction with the database and data manipulation. Next, the view layer is responsible for rendering data into a web page. The controller is responsible for processing the request and interacts with the model and view layer to provide a response.

To make development easier (and due to how the Laminas MVC framework works) we add some extra layers and arrive at a structure for each module that looks like this:

```txt
./
├── config
│   └── module.config.php   # Contains routing information and other module specific configurations.
├── src
│   ├── Command             # CLI commands.
│   │   └── ...
│   ├── Controller          # Entrypoint for requests to the website, some light processing takes place here before using a specific service.
│   │   └── ...
│   ├── Form                # Specification and validation of forms based on entities.
│   │   └── ...
│   ├── Mapper              # Doctrine ORM repositories to access the underlying database and mapping entities to that data.
│   │   └── ...
│   ├── Model               # Doctrine ORM entities.
│   │   └── ...
│   └── Service             # Services contain the core logic related to specific entities (or sets of entities) and do most of the processing.
│   │   └── ...
├── test                    # Test files for this module, such as unit tests.
│   ├── Seeder              # Data fixtures to seed the database with data for this module.
│   │   └── ...
│   └── ...
└── view                    # All template files ("views") made out of HTML and PHP code, used by controllers for output.
    └── ...
```

The `Application` module has one additional folder:
- `language` containing the translation files (`make translations` to update them).

The `Database` and `Report` modules have one additional folder:
- `migrations` containing database migrations.

## License
This software is licensed under the GNU General Public License v3.0 (GPL-3.0), see [LICENSE](./LICENSE.txt).

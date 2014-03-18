GEWIS Database
==============

Please note that this is all experimental and might or might not be used in production.


Installation
------------

- Clone the repositories.
- Install dependencies using composer: `php composer.phar install`
- Create new PostgreSQL database
- Copy `config/autoload/doctrine.local.php.dist` to
  `config/autoload/doctrine.local.php` and configure the database settings in
  there.
- Give the webserver's user read and write permissions to the `data/`
  directory.
- Run `./vendor/bin/doctrine-module orm:schema-tool:create` to populate the
  database.

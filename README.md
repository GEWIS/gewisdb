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
- Run `./db orm:schema-tool:create` to populate the
  database.
- Create a new database with the name `gewisdb_report`
- Run `EM_ALIAS=orm_report ./db orm:schema-tool:create`
- Create a `.htpasswd` file, and add a user to it (`htpasswd -c .htpasswd
  <user>`)
- Copy `public/.htaccess.dist` to `public/.htaccess` and configure the path to
  the `.htpasswd` file (the `AuthUserFile` directive).

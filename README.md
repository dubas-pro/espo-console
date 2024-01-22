# Unofficial Console for EspoCRM

A command line utility for working with [EspoCRM](https://github.com/espocrm/espocrm).

## ⚠️ Disclaimer ⚠️

**This project is not associated with the EspoCRM project nor Letrium LTD. Espo CRM® is a registered trademark of Letrium LTD. Any use by DUBAS S.C. is for referential purposes only and does not indicate any sponsorship, endorsement, or affiliation between Letrium LTD.**

## Environment requirements

* PHP `>=8.0.0` with `ext-mbstring` extension enabled
* [Composer](https://getcomposer.org/)

## Install

Via composer as a local dependency:

``` bash
composer require --dev dubas/espo-console
```

**OR:**

Via composer as a global dependency:

``` bash
composer global require dubas/espo-console
```

## Running Commands

Depending on install location:

```bash
vendor/bin/espo
```

**OR:**

```bash
espo
```

Which should get you something like:

```diff
Dubas\Console 0.0.15

Usage:
  command [options] [arguments]

Options:
  -h, --help                     Display help for the given command. When no command is given display help for the list command
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory [default: "/var/www/default"]
  -I, --instance=INSTANCE        Path to EspoCRM instance [default: "site"]
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  completion            Dump the shell completion script
  help                  Display help for a command
  list                  List commands
 admin
  admin:clear-cache     [cc] Clear all backend cache
  admin:rebuild         [rb] Rebuild backend and clear cache
 config
  config:create         Create config
  config:merge          Merge configs
 core
  core:download         Download core EspoCRM files
  core:install          Run the standard EspoCRM installation process
 db
  db:create             Create database
  db:drop               Delete database
  db:query              Executes a SQL query against the database
 ext
  ext:build             Build an installable extension package
  ext:composer-install  Composer dependencies for an extension
  ext:copy              Copy extension files to EspoCRM instance
  ext:init              Setup a complete environment for developing an extension
  ext:install           Install a single or multiple EspoCRM extensions
  ext:npm-install       Install Node.js dependencies for an extension
  ext:path              Return path to the backend directory
 import
  import:test-data      Import test data
```

## Roadmap

The project is in its early stages and some feature either are missing or unstable. The things we want to focus on:

* Writing unit tests
* Backup command
* Creating an extension skeleton
* Compiling a PHAR file with an ability to self-update

## Contributing

Pull requests are welcome. Keep it simple. Keep it minimal. For major changes, please open an issue first to discuss what you would like to change.

## Acknowledgements

The project is inspired by other great CLI tools. Some of the commands were ported to PHP from [espocrm/ext-template](https://github.com/espocrm/ext-template) which were written in JavaScript. Commands structure and naming are inspired by [wp-cli](https://github.com/wp-cli/wp-cli) and [concrete5/console](https://github.com/concretecms/console). Originally the project was based on [mnapoli/silly micro-framework](https://github.com/mnapoli/silly) which has provided a handy [Application::runCommand](https://github.com/mnapoli/silly/blob/1.8.0/src/Application.php#L165) method.

Huge thank you to all who are part of the open source community!

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

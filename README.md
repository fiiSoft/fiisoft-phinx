# FiiSoft Phinx

Console-commands to use Phinx's library console-commands in custom console application (based on symfony/console package).

My advice is - do not use it unless you are enough strong mentally to immune for such bad code. 

---------------------------------

It contains wrappers for Phinx's commands:

* breakpoint
* create
* migrate
* rollback
* seed:create
* seed:run
* status
* test

It also contains six special commands (not available in Phinx):
* mark - marks migration as migrated without migrating it (adds entry log to phinxlog so migration seems to be already migrated)
* unmark - removes entry log from phinxlog, without rollback on migration (so migration will be migrated on next migrate)
* repeat - repeats single migration (specified by its version number) even if migrated
* revoke - performs rollback on single migration (specified by its version number) 
* remove - removes entry log from phinxlog and deletes corresponding migration file from disk 
* cleanup - removes entries of missing migrations from phinxlog (if files are not available)

In addition, it comes with special generic-purpose command phinx which allows to run other Phinx-specific commands in special way.

---------------------------------

Keep in mind that this library uses its own configuration-schema as well as template for migrations classes and base abstract class for them.

The main goal of this custom configuration is to manage migrations for many destiny databases (in various environments).
Therefore configurations are grouped in something called "destiny".

Here are some examples.

- The simplest possible (aka minimal required) configuration:

```php
$config = new PhinxConfig([
    'local' => [
        'environments' => [
            'dev' => [
                'adapter' => 'pgsql',
                'host' => 'localhost',
                'name' => 'dbname',
                'user' => 'username',
                'pass' => 'password',
            ]
        ],
    ]
]);
```
This creates config for one destiny called _local_ with one environment _dev_.
Migration files for this destiny will be stored in `(current working directory)/phinx/local/migrations` directory.
Notice that the name of destiny becames part of path. 

- Similar configuration but with path to files with migrations in specified directory without name of destiny:

```php
$config = new PhinxConfig([
    'local' => [
        'paths_root' => __DIR__ . DIRECTORY_SEPARATOR . 'phinx',
        'environments' => [
            'dev' => [
                'adapter' => 'pgsql',
                'host' => 'localhost',
                'name' => 'dbname',
                'user' => 'username',
                'pass' => 'password',
            ]
        ],
    ]
]);
```
In this case path to migration files is `__DIR__/phinx/migrations` - there is no name of destiny _local_ in path.

- Third way to create the simplest configuration is to specify path to directory with migrations like this:

```php
$config = new PhinxConfig([
    'defaults' => [
        'phinx_files' => __DIR__ . DIRECTORY_SEPARATOR . 'dev',
    ],
    'local' => [
        'environments' => [
            'dev' => [
                'adapter' => 'pgsql',
                'host' => 'localhost',
                'name' => 'dbname',
                'user' => 'username',
                'pass' => 'password',
            ]
        ],
    ]
]);
```
This defines root folder for all _destinations_. The path for migration files for _local_ is now `__DIR__/dev/local/migrations`.

---

To handle various different sets of migrations reffered to the same database, use:

```php
$config = new PhinxConfig([
    'defaults' => [
        'adapter' => 'pgsql',
        'host' => 'localhost',
        'name' => 'dbname',
        'user' => 'username',
        'pass' => 'password',
    ],
    'alpha' => [
      'environments' => [
          'dev' => [
          ],
      ],
    ],
    'bravo' => [
      'environments' => [
          'dev' => [
          ],
          'default_migration_table' => 'phinx_migrations',
      ],
    ],
];
```

This configuration defines two _destinations_: _alpha_ and _bravo_.

Migration files for _alpha_ will be stored in `(current working directory)/phinx/alpha/migrations`
and the name of the table used by Phinx will be `phinxlog` (as default).

Migration files for _bravo_ will be stored in `(current working directory)/phinx/bravo/migrations`
and the name of the table used by Phinx will be `phinx_migrations`.

Because there are no other data specified, both _destinations_ will share the same database, so it gives possibility
to handle various sets of migrations for the same database.

-----------------------

There is also a bin/finx executable file provided with library.
Usage of it is a bit hard to explain because it uses its own schema of arguments.

Some examples (lets assume the _local_ destination is defined in configuration):

- `bin/finx list` show list of available commands
- `bin/finx` the same as above (command list is default)
- `bin/finx phinx` show help for generic command (phinx)
- `bin/finx phinx local status` show status of migrations from destiny _local_
- `bin/finx phinx local` the same as above (command status is default)
- `bin/finx local` the same as above (status is default, keyword phinx can be omitted)
- `bin/finx phinx:status local` the same as above (by direct call of command status)

Some examples of call command _migrate_ with environment _dev_ from destination _local_,
in different ways (and all do exactly the same!):

- `bin/finx phinx local migrate -e dev` 
- `bin/finx phinx local dev migrate` 
- `bin/finx phinx local migrate` 
- `bin/finx phinx:migrate local -e dev` 
- `bin/finx phinx:migrate local dev` 
- `bin/finx phinx:migrate local` 
- `bin/finx local migrate -e dev` 
- `bin/finx local dev migrate` 
- `bin/finx local migrate` (this one I prefer)

As you can see, it's a bit crazy.
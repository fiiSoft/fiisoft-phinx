# FiiSoft Phinx

Console-commands to use Phinx's library console-commands in custom console application (based on symfony/console package).

My advice is - do not use it unless you are enough strong mentally to immune for such bad code. 
But of course you can if you want.

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

In addition, it comes with special generic-purpose command phinx which allows to run other Phinx-specific commands in special way.

Keep in mind that this library uses its own configuration-schema.
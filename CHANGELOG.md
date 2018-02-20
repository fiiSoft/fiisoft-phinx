# Changelog

All important changes to `fiisoft-phinx` will be documented in this file

## 1.9.1

* fixed bug in Cleanup command
* removed unnecessary second call of $app->run() in bin/finx  

## 1.9.0

* new method PhinxAbstractMigration::dropConstraintByName
* new method PhinxCommonTrait::isTableEmpty
* fixed bug - table witch schema in PhinxAbstractSeed was not handled properly
* removed file composer.lock 

## 1.8.0

* added two new commands: revoke and repeat
* added bunch of options specific for sub-commands to base command (phinx)
* fixed passing the options to sub-commands from base command
* changed the way how app is run though call bin/finx (name of command phinx is now optional)
* errors codes have been unified
* some errors have been fixed

## 1.7.2

Yep, fixed autoloading.

## 1.7.1

Of course file bin/phinx is already occupied by Phinx, so... let's bin/finx!

## 1.7.0

Added bin/phinx

## 1.6.0

Added file app.php as a handy way to run commands directly (with local configuration provided by user).

## 1.5.0

Four new commands has been added. They are:
* mark
* unmark
* remove 
* cleanup

## 1.4.0

* added new methods to PhinxAbstractMigration and PhinxCommonTrait
* added handy, shortened documentation as a comment to default template for migrations
* removed two defaults from PhinxConfig

## 1.3.0

* added new abstract class PhinxAbstractSeed
* modified command PhinxSeedCreateCmd
* added trait PhinxCommonTrait
* changed class PhinxConfig
* readme updated with example of the simplest configuration 
* some other fixes and improvements

## 1.2.0

Fixed bug with dropView and added new method hasView() to PhinxAbstractMigration.

## 1.1.0

Added custom abstract class (PhinxAbstractMigration) as default base class for created migration classes.

Added util class PhinxTemplatePath with nice method path(). 

## 1.0.0

Initial version.

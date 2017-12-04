# Changelog

All important changes to `fiisoft-phinx` will be documented in this file

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

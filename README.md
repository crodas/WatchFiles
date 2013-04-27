WatchFiles
==========

Stateless way of watching files and directory for changes

It is useful when you compile files, and you would like a simple and efficient way of watching files and directories for changes to avoid re-compilation.

How to install
-------------

You can install it using composer.

```bash
composer require crodas/watch-files *
```

How to use it
--------------

```php
require "vendor/autoload.php";

use WatchFiles\Watch;

// we'd like to watch some files
// and to save its state in foobar.php
$foobar = new Watch("foobar.php");
if ($foobar->isWatching()) {
  if (!$foobar->hasChanged()) {
    // somebody else before us started watching files/dirs
    // on foobar.php and *nothing* changed since last 
    // time
    return;
  }
  // do heavy stuff here (Recompile it?)
  // we need to tell the watch that we're aware of lastest
  // changes and we'd like to update the file modification time
  $foobar->rebuild();
  return;
}

// we'd love to see when a new file has been added or deleted
$foobar->watchDir("foodir");
$foobar->watchDirs(array("foodir", 'foobar'));

// or monitor changes inside file or files
$foobar->watchFile("foodir.php");
$foobar->watchFiles(array("foodir.php", 'foobar.php'));

// start watching!
$foobar->watch();
```
